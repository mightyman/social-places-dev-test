<?php

namespace App\Services;

use App\Constants\PhpSpreadsheetConstants;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ExportService
{
    /** @var Worksheet[] */
    private array $worksheetList = [];
    private ?Spreadsheet $sheet = null;
    private array $length = [];
    private int $activeIndex = 0;
    public const CREATOR = 'setCreator';
    public const LAST_MODIFIED_BY = 'setLastModifiedBy';
    public const TITLE = 'setTitle';
    public const SUBJECT = 'setSubject';
    public const DESCRIPTION = 'setDescription';
    public const KEYWORDS = 'setKeywords';
    public const CATEGORY = 'setCategory';
    public const SHEET_PROPERTIES = [
        self::CREATOR,
        self::LAST_MODIFIED_BY,
        self::TITLE,
        self::SUBJECT,
        self::DESCRIPTION,
        self::KEYWORDS,
        self::CATEGORY,
    ];
    public const AUTO_SIZE_IGNORE_VALUES = [
        'Response Data',
        'Content Copy',
    ];
    public const AUTO_SIZE_SKIP = [
        'Media'
    ];

    public function createWorksheet(string $title = 'Worksheet'): self {
        $this->worksheetList[] = new Worksheet(null, sp_replace_accented_characters($title));
        ++$this->activeIndex;
        return $this;
    }

    public function &getWorksheet(int $activeIndex = null) {
        return $this->worksheetList[$this->getActiveIndex($activeIndex)];
    }

    public function setSheetProperties(array $properties = []): self {
        $this->getSheet();
        foreach ($properties as $property => $value) {
            if (in_array($property, self::SHEET_PROPERTIES, true)) {
                $this->sheet->getProperties()->{$property}($value);
            }
        }
        return $this;
    }

    public function setDefaultSheetProperties(string $exportName, ?User $user = null): self {
        $name = $user?->getFullname() ?? 'System';
        return $this->setSheetProperties(
            [
                self::CREATOR => $name,
                self::TITLE => $exportName,
                self::LAST_MODIFIED_BY => $name,
                self::SUBJECT => $exportName,
                self::DESCRIPTION => $exportName
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function writeRow(array $row, int $start = 1, int $activeIndex = null, bool $shouldWrap = false): self {
        $activeIndex = $this->getActiveIndex($activeIndex);
        /** @var Worksheet $worksheet */
        $worksheet = $this->worksheetList[$activeIndex];
        $this->setSheet($worksheet, $activeIndex);
        foreach ($row as $index => $item) {
            $position = $index;
            if (!is_int($position)) {
                $position = Coordinate::columnIndexFromString($position);
            } else {
                ++$position;
            }
            $column = Coordinate::stringFromColumnIndex($position);
            $worksheet->setCellValueExplicit("$column$start", $item, DataType::TYPE_STRING);
            if ($shouldWrap) {
                $worksheet->getStyle("$column$start")->getAlignment()->setWrapText(true);
            }
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function writeRowsWithOptions(array $rows, int $start = 1, int $activeIndex = null, bool $shouldWrap = false): self {
        $activeIndex = $this->getActiveIndex($activeIndex);
        /** @var Worksheet $worksheet */
        $worksheet = $this->worksheetList[$activeIndex];
        $this->setSheet($worksheet, $activeIndex);

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $index => $item) {
                $position = $index;
                if (!is_int($position)) {
                    $position = Coordinate::columnIndexFromString($position);
                } else {
                    ++$position;
                }
                $column = Coordinate::stringFromColumnIndex($position);
                $coordinate = "$column$start";
                $worksheet->setCellValueExplicit($coordinate, $item['value'] ?? $item, DataType::TYPE_STRING);
                if ($shouldWrap) {
                    $worksheet->getStyle("$column$start")->getAlignment()->setWrapText(true);
                }
                if (is_array($item) && count($item['options']['styles']) > 0) {
                    $worksheet->getCell($coordinate)->getStyle()->applyFromArray($item['options']['styles']);
                }
                if (is_array($item) && isset($item['options']['comment'])) {
                    $worksheet->getComment($coordinate)->getText()->createTextRun($item['options']['comment']);
                }
                if (is_array($item) && isset($item['options']['comments'])) {
                    foreach ($item['options']['comments'] as $comment) {
                        $worksheet->getComment($coordinate)->getText()->createTextRun($comment);
                    }
                }
            }
            $start++;
        }
        return $this;
    }

    public function fromArray(
        array $rows,
        int $activeIndex = null,
        $nullValue = null,
        string $startCell = 'A1',
        bool $strictNullComparison = false,
        bool $autoWidth = false
    ): self {
        /** @var Worksheet $worksheet */
        $worksheet = $this->worksheetList[$this->getActiveIndex($activeIndex)];
        $worksheet->fromArray($rows, $nullValue, $startCell, $strictNullComparison);
        if ($autoWidth === true) {
            $iterator = $worksheet->getColumnIterator(sp_strip_numeric($startCell), $worksheet->getHighestDataColumn());
            while ($iterator->valid()) {
                $col = $iterator->current()->getColumnIndex();
                $worksheet
                    ->getColumnDimension($col)
                    ->setAutoSize(true);
                $iterator->next();
            }
        }

        $this->length[$this->getActiveIndex($activeIndex)] = is_array($rows[0]) ? count($rows[0]) : count($rows);
        return $this;
    }

    public function quickExportFromArray(array $rows, bool $useKeysAsHeaders = true): Writer\Csv|Writer\Xlsx|Writer\Html {
        $this->getSheet()
            ->getDefaultStyle()
            ->applyFromArray(PhpSpreadsheetConstants::DEFAULT_FONT)
            ->getAlignment()
            ->setWrapText(true);
        $counter = 1;

        if ($useKeysAsHeaders && !empty($rows)) {
            $this->writeRow(array_keys($rows[0]), $counter);
        }
        foreach ($rows as $row) {
            $counter++;
            $this->writeRow($row, $counter);
        }
        $this->applyStyleToRow(1, array_merge(PhpSpreadsheetConstants::HEADING, PhpSpreadsheetConstants::HEADING_FILL));
        $this->applyAutoWidth(1);
        return $this->generateDocument();
    }

    public function processRow(\Closure $closure, int $row = 1, string $startCol = 'A', int $activeIndex = null): self {
        $this->setSheetBasedOnActiveIndex($activeIndex);
        $worksheet = $this->sheet->getActiveSheet();
        $iterator = $worksheet->getColumnIterator($startCol, $worksheet->getHighestDataColumn());
        while ($iterator->valid()) {
            $col = $iterator->current()->getColumnIndex();
            $closure($worksheet->getCell("{$col}{$row}"), $row);
            $iterator->next();
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function processAllRows(\Closure $closure, $row = 1, $startCol = 'A', int $activeIndex = null): self {
        $this->setSheetBasedOnActiveIndex($activeIndex);
        $worksheet = $this->sheet->getActiveSheet();
        $maxRow = $worksheet->getHighestRow();
        foreach (range($row, $maxRow) as $currentRow) {
            $iterator = $worksheet->getColumnIterator($startCol, $worksheet->getHighestDataColumn());
            while ($iterator->valid()) {
                $col = $iterator->current()->getColumnIndex();
                $closure($worksheet->getCell("{$col}{$currentRow}"), $currentRow);
                $iterator->next();
            }
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function generateDocument(bool $applyStyles = true, int $activeIndex = null, string $documentType = PhpSpreadsheetConstants::XLSX_FILE_TYPE) {
        $this->setSheetBasedOnActiveIndex($activeIndex);
        if ($documentType === PhpSpreadsheetConstants::CSV_FILE_TYPE) {
            $writer = new Writer\Csv($this->sheet);
        } elseif ($documentType === PhpSpreadsheetConstants::HTML_FILE_TYPE) {
            $writer = new Writer\Html($this->sheet);
        } else {
            $writer = new Writer\Xlsx($this->sheet);
        }
        $this->clear();
        return $writer;
    }

    /**
     * @throws Exception
     */
    public function enableAutoFilter(int $activeIndex = null): self {
        $this->setSheetBasedOnActiveIndex($activeIndex);
        $this->sheet->getActiveSheet()->setAutoFilter(
            $this->sheet->getActiveSheet()
                ->calculateWorksheetDimension()
        );
        return $this;
    }

    /**
     * @throws Exception
     */
    private function setSheetBasedOnActiveIndex(int $activeIndex = null): void {
        $this->getSheet();
        $sheets = $this->sheet->getSheetCount();
        foreach ($this->worksheetList as $index => $worksheet) {
            if ($sheets > $index) {
                $this->sheet->removeSheetByIndex($index);
            }
            $this->sheet->addSheet($worksheet, $index);
        }
        $this->sheet->setActiveSheetIndex($this->getActiveIndex($activeIndex));
    }


    public function getActiveIndex(int $activeIndex = null): int {
        if ($activeIndex === null) {
            $activeIndex = ($this->activeIndex === 0) ? $this->activeIndex : $this->activeIndex - 1;
        }
        return $activeIndex;
    }

    private function clear(): void {
        $this->worksheetList = [];
        $this->activeIndex = 0;
        $this->sheet = null;
    }

    /**
     * @throws Exception
     */
    private function setSheet(Worksheet $sheet, int $activeIndex): void {
        $this->getSheet();
        try {
            $this->sheet->removeSheetByIndex($activeIndex);
        } catch (\Exception $e) {
            //Ignore Empty Sheet
        }
        $this->sheet->addSheet($sheet, $activeIndex);
    }

    /**
     * @throws Exception
     */
    public function addExternalWorksheet(Worksheet $worksheet, int $activeIndex = 0): self {
        $this->getSheet();
        $this->sheet->addExternalSheet($worksheet, $activeIndex);
        $this->worksheetList[$activeIndex] = $this->sheet->getSheet($activeIndex);
        return $this;
    }

    public function getSheet(): Spreadsheet {
        if ($this->sheet === null) {
            $this->sheet = new Spreadsheet();
        }
        return $this->sheet;
    }

    public static function generateResponse(
        $writer,
        string $filename,
        int $statusCode = 200,
        string $documentType = PhpSpreadsheetConstants::XLSX_FILE_TYPE
    ): StreamedResponse {
        ob_end_clean();
        if (!sp_string_contains_any($filename, PhpSpreadsheetConstants::EXTENSIONS)) {
            $filename = "{$filename}.{$documentType}";
        }
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, $statusCode);
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
        $response->headers->set('Content-Type',
            'text/' . ($documentType === PhpSpreadsheetConstants::HTML_FILE_TYPE ? PhpSpreadsheetConstants::HTML_FILE_TYPE : 'vnd.ms-excel') . ';charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . sp_replace_accented_characters($filename) . ';');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        return $response;
    }

    public static function saveFileToTemp($writer, $path, $filePrefix): string {
        $path = "/tmp/".$path;
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        $path = "/tmp/{$path}" . DIRECTORY_SEPARATOR . sp_unique_string_based_on_uniqid($filePrefix) . ".xlsx";
        $writer->save($path);
        return $path;
    }

    /**
     * @throws Exception
     */
    public function applyStyleToRow(int $row, array $styles, int $activeIndex = null): self {
        $activeIndex = $this->getActiveIndex($activeIndex);
        /** @var Worksheet $worksheet */
        $worksheet = $this->worksheetList[$activeIndex];
        $highest = $worksheet->getHighestColumn($row);
        $this->setSheetBasedOnActiveIndex($activeIndex);
        $worksheet->getStyle("A{$row}:${highest}{$row}")->applyFromArray($styles);
        return $this;
    }

    public function applyAutoWidth(int $row, int $activeIndex = null): self {
        $activeIndex = $this->getActiveIndex($activeIndex);
        /** @var Worksheet $worksheet */
        $worksheet = $this->worksheetList[$activeIndex];

        $highestDataColumn = $worksheet->getHighestDataColumn($row);
        $highest = Coordinate::columnIndexFromString($highestDataColumn);

        for ($columnCount = 1; $columnCount <= $highest; $columnCount++) {
            $column = Coordinate::stringFromColumnIndex($columnCount);
            try {
                if (
                    !in_array($worksheet->getCell($column . $row)?->getValue(),
                        array_merge(self::AUTO_SIZE_IGNORE_VALUES, self::AUTO_SIZE_SKIP), true)
                ) {
                    $worksheet->getColumnDimension($column)?->setAutoSize(true);
                } elseif (in_array($worksheet->getCell($column . $row)?->getValue(), self::AUTO_SIZE_IGNORE_VALUES, true)) {
                    $worksheet->getColumnDimension($column)?->setWidth(PhpSpreadsheetConstants::DEFAULT_MAX_COLUMN_WIDTH);
                }
            } catch (\Exception $exception) {
            }
        }
        return $this;
    }
}
