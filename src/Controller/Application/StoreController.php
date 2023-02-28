<?php

namespace App\Controller\Application;

use App\Attributes\ImportExportAttribute;
use App\Attributes\ImportProcessorAttribute;
use App\Constants\PhpSpreadsheetConstants;
use App\Entity\Brand;
use App\Entity\Store;
use App\Enums\StoreStatus;
use App\Services\ExportService;
use App\Services\FileUploadService;
use App\Services\ImportService;
use App\Services\StoreService;
use App\ViewModels\StoreViewModel;
use App\ViewModels\UserViewModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Exception;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class StoreController extends BaseVueController
{
    protected ?string $entity = StoreViewModel::class;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SessionInterface $session,
        protected EntityManagerInterface $entityManager,
        protected RouterInterface $router,
        private readonly StoreService $storeService,
    ) {
        parent::__construct(
            $serializer,
            $security,
            $parameterBag,
            $session,
            $entityManager,
            $router
        );
    }

    #[Route('/api/stores/index', name: 'api_stores', methods: ['POST', 'GET'])]
    public function index(Request $request): JsonResponse {
        return $this->_index($request);
    }

    /**
     * @throws \Exception
     */
    protected function getBaseResults(Request $request): array {
        return $this->_getBaseResults($request);
    }

    #[Route('/api/stores/filters', name: 'api_stores_filters', methods: ['POST', 'GET'])]
    public function getFilters(): JsonResponse {
        return $this->_getFilters();
    }

    #[Route('/api/filters/brands', name: 'api_brands_filter')]
    public function getBrandsFilter(): JsonResponse {
        $brands = $this->entityManager->query('select id, name from brand order by name');
        return $this->json($brands);
    }

    #[Route('/api/stores/temporary-folder', name: 'api_stores_temporary_folder', methods: 'GET')]
    public function getTemporaryUploadFolder(): JsonResponse {
        return $this->json(['folder' => sp_unique_string_based_on_uniqid('store')]);
    }

    #[Route('/api/stores/import/upload', name: 'api_stores_upload', methods: 'POST')]
    public function storeImportFileUpload(Request $request, FileUploadService $fileUploadService): JsonResponse {
        $files = $request->files;
        $temporaryFolder = $request->headers->get('X-Folder');
        if (!$files) {
            return $this->json([],Response::HTTP_PRECONDITION_FAILED);
        }

        $mapFiles = static function (UploadedFile $item) use ($temporaryFolder, $fileUploadService) {
            return [
                'size' => $item->getSize(),
                'type' => $item->getClientMimeType(),
                'name' => $item->getClientOriginalName(),
                'tempName' => $fileUploadService->storeTempFile(FileUploadService::OUTSIDE_CONTENT, $item, $temporaryFolder),
                'tempFolder' => $temporaryFolder
            ];
        };

        $uploadedFiles = [];
        foreach ($files as $file) {
            if (!is_array($file)) {
                $file = [$file];
            }
            foreach ($file as $subFile) {
                $uploadedFiles[] = $mapFiles($subFile);
            }
        }
        return $this->json(compact('uploadedFiles'));
    }

    /**
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/api/stores/export', name: 'api_store_export', methods: 'POST')]
    public function export(Request $request) {
        /** @var QueryBuilder $qb */
        [
            'qb' => $qb
        ] = $this->getBaseResults($request);
        $exportQb = clone $qb;
        $distinct = sp_dql_has_distinct($qb->getQuery()) ? 'DISTINCT' : '';
        $rowCount = (int)$qb->select("COUNT($distinct {$this->alias}.id)")->getQuery()->getSingleScalarResult();
        if ($rowCount === 0) {
            throw new NoResultException();
        }

        $attributeInformation = $this->extractImportExportAttributeInformation();

        $query = $this->entityManager->createQuery($exportQb->getDQL());
        sp_dql_apply_other_parameters($exportQb, $query);
        $exportService = (new ExportService())
            ->createWorksheet('Stores');
        $exportService->setDefaultSheetProperties('Store export', $this->getUser());
        $exportService->getSheet()
            ->getDefaultStyle()
            ->applyFromArray(PhpSpreadsheetConstants::DEFAULT_FONT)
            ->getAlignment()
            ->setWrapText(true);
        $counter = 1;
        $iterator = $query->toIterable();
        $exportService->writeRow(array_column($attributeInformation, 'columnName'));
        foreach ($iterator as $row) {
            $counter++;
            $rowValues = [];
            foreach ($attributeInformation as ['getterFunction' => $getterFunction, 'exportProcessor' => $exportProcessor]) {
                $value = $row->{$getterFunction}();
                if ($exportProcessor !== null) {
                    $value = $exportProcessor($value);
                }
                $rowValues[] = $value;
            }
            $exportService->writeRow($rowValues, $counter);
        }
        $exportService->applyStyleToRow(1, array_merge(PhpSpreadsheetConstants::HEADING, PhpSpreadsheetConstants::HEADING_FILL));
        $exportService->applyAutoWidth(1);
        $document = $exportService->generateDocument();
        return ExportService::generateResponse($document, 'Export');
    }

    private function extractImportExportAttributeInformation(): array {
        $concernedClass = Store::class;

        $reflectionClass = new ReflectionClass($concernedClass);
        $properties = $reflectionClass->getProperties();
        $storeMetadata = $this->entityManager->getClassMetadata(Store::class);

        $extractedProperties = [];
        foreach ($properties as $property) {
            $importExportAttributes = $property->getAttributes(ImportExportAttribute::class);
            if (count($importExportAttributes) !== 1) {
                continue;
            }
            /** @var ImportExportAttribute $importExportAttribute */
            $importExportAttribute = reset($importExportAttributes)->newInstance();
            $columnName = $importExportAttribute->getColumnName();
            $propertyName = $property->getName();
            if ($columnName === null) {
                $columnName = sp_string_normalize_camel_snake_kebab_to_words($propertyName);
            }

            $getterFunction = $importExportAttribute->getGetter();
            if ($getterFunction === null) {
                $getterFunction = sp_getter($propertyName);
            }
            if ($getterFunction !== null && !method_exists($concernedClass, $getterFunction)) {
                throw new \RuntimeException('Method ' . $getterFunction . ' does not exist on ' . $concernedClass);
            }
            $getterReflectionMethod = new \ReflectionMethod($concernedClass, $getterFunction);
            $exportProcessor = null;
            if ($getterReflectionMethod->getReturnType()?->getName() === 'bool') {
                $exportProcessor = static function (?bool $value) {
                    return match($value) {
                        null => '',
                        false => 'No',
                        true => 'Yes',
                    };
                };
            }

            $setterFunction = $importExportAttribute->getSetter();
            if ($setterFunction === null) {
                $setterFunction = sp_setter($propertyName);
            } elseif (!method_exists($concernedClass, $setterFunction)) {
                throw new \RuntimeException('Method ' . $setterFunction . ' does not exist on ' . $concernedClass);
            }
            $setterReflectionMethod = new \ReflectionMethod($concernedClass, $setterFunction);
            $importProcessor = null;
            $setterParameter = $setterReflectionMethod->getParameters()[0];
             if ($setterParameter->getType()?->getName() === 'bool') {
                 $allowsNulls = $setterParameter->getType()?->allowsNull() ?? false;
                 $importProcessor = static function ($mixed, ?string $value) use ($allowsNulls) {
                     return match(strtolower($value ?? '')) {
                         '' => $allowsNulls ? null : false,
                         'no' => false,
                         'yes' => true,
                     };
                 };
             }
            if (!empty($importProcessors = $property->getAttributes(ImportProcessorAttribute::class))) {
                /** @var ImportProcessorAttribute $importProcessor */
                $importProcessorAttr = reset($importProcessors)->newInstance();
                $importProcessor = static function (self $_this, mixed $value) use ($importProcessorAttr): mixed {
                    if ($importProcessorAttr->getService()) {
                        return $_this->{$importProcessorAttr->getService()}->{$importProcessorAttr->getFunction()}($value);
                    }
                    return $_this->{$importProcessorAttr->getFunction()}($value);
                };
            }

            $dbColumnName = array_flip($storeMetadata->fieldNames)[$propertyName] ?? null;
            if ($dbColumnName === null) {
                $array = array_values($storeMetadata->associationMappings[$propertyName]['joinColumnFieldNames'] ?? []);
                $dbColumnName = reset($array);
            }

            $isIdentifier = $importExportAttribute->getIsIdentifierField();

            $extractedProperties[] = compact('columnName', 'propertyName', 'getterFunction', 'setterFunction', 'dbColumnName', 'isIdentifier', 'exportProcessor','importProcessor');
        }
        return $extractedProperties;
    }

    /**
     * Developer Test
     * Implement the import function, accepting a URL and request from the frontend
     * Process the import in a timely manner using the tools available to you
     * @see ImportService
     * @see StoreController::extractImportExportAttributeInformation
     * or creating new tools
     *
     * Conditions:
     * Imports 20000 excel rows by 26 columns swiftly
     * Validates data notifying the user of all errors for each row in a simple easy to understand way
     * Validation should use default entity validation (already existing - in part)
     * Compromise on one field that may not ever change to be the identifier, noting the name may change in the provided export
     *
     * Feel free to update any supporting code to the table to help speed things up
     */
    #[Route('/api/stores/import/process', name: 'api_store_process_import', methods: 'POST')]
    public function import(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): StreamedResponse|JsonResponse {
        $folder = $request->get('folder');
        $fileName = $request->get('fileName');
        $filePath = FileUploadService::CONTENT_PATH . "/temp-uploads/$folder/$fileName";

        //impor the excel file
        $reader  = new Xlsx();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        //get row count 
        $row_count = $worksheet->getHighestRow();
        
        $errors = [];
        $batch_size = $row_count;
        // Start from row 2, as the first row is assumed to be a header
        for($row = 2; $row <= $row_count; $row ++ )
        {
            $data = [];
            // Assume 26 columns (A-Z)
            for ($col = 1; $col <= 27; $col++) {  
                $cellValue = $worksheet->getCell("$col$row")->getValue();
                $data[] = $cellValue;
            }

            // Validate the row data using the Store entity validation
            $store = new Store();
            $store->setName($data[1]);
            $store->setBrand($data[2]);
            $store->setIndustry($data[3]);
            $store->setStatus($data[4]);
            $store->setStatusFromName($data[5]);
            $store->setApiId($data[6]);
            $store->setFacebookVerified($data[7]);
            $store->setFacebookId($data[8]);
            $store->setFacebookPageName($data[9]);
            $store->setFacebookUrl($data[10]);
            $store->setGoogleVerified($data[11]);
            $store->setGooglePlaceId($data[12]);
            $store->setGoogleLocationId($data[13]);
            $store->setGoogleMapsUrl($data[14]);
            $store->setTripAdvisorVerified($data[15]);
            $store->setTripAdvisorId($data[16]);
            $store->setTripAdvisorPartnerPropertyId($data[17]);
            $store->setTripAdvisorUrl($data[18]);
            $store->setZomatoVerified($data[19]);
            $store->setZomatoId($data[20]);
            $store->setZomatoUrl($data[21]);
            $store->setInstagramVerified($data[22]);
            $store->setInstagramId($data[23]);
            $store->setInstagramUrl($data[24]);
            $store->setLatitude($data[25]);
            $store->setLongitude($data[26]);

            $violations = $validator->validate($store);
            if ($violations->count() > 0) {
                //If there are validation errors, add them to the errors array
                $rowErrors = [];
                foreach ($violations as $violation) {
                    $rowErrors[] = $violation->getMessage();
                }
                $errors[$row] = $rowErrors;
            } else {
                // If the row data is valid, save it to the database
                $entityManager->persist($store);
                // flush the batch every $batchSize entities
                if (($row % $batch_size) === 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }            
        }

        // Save any remaining changes to the database
        $entityManager->flush();





        // If there are errors, return them as a JSON response
        if (!empty($errors)) {
            return $this->json($errors);
        }

        return $this->json([]);
    }
}
