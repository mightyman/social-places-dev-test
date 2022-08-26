<?php

namespace App\Controller\Application;

use App\Entity\User;
use App\Exceptions\SqlInjectionException;
use App\Traits\Controller\HasSession;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use ReflectionFunction;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Classes\Column;
use App\Classes\Filter;
use Closure;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Base Vue Controller
 * Class BaseVueController
 * @package Controller\Application
 */
abstract class BaseVueController extends AbstractController
{
    use HasSession;

    private const REQUIRES_TRANSLATE_RELATION = [
    ];
    public const SEARCH_OR = 'OR';
    public const SEARCH_AND = 'AND';
    public const MAX_LIMIT_FOR_ONES_WITHOUT = 1000;
    protected int $defaultPageSize = 10;
    protected array $defaultPageSizeOptions = [10, 25, 50, 100];

    protected string $alias = '';

    protected array $sortBy = [];
    protected array $sortByDesc = [];
    protected ?string $searchTerm = null;
    protected bool $keywordsSearch = false;


    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SessionInterface $session,
        protected EntityManagerInterface $entityManager,
        protected RouterInterface $router,
    ) {
        $this->baseTableViewModelSetup();
    }

    /**
     * @param QueryBuilder $qb
     * @param array|Filter[] $filters
     * @param array $data
     * @return QueryBuilder
     * @throws Exception
     */
    public function generateQueryBasedOnFilters(QueryBuilder $qb, array $filters, array $data): QueryBuilder {
        $softDeletedHandled = false;
        $groupFilters = [];
        $now = date('Y-m-d H:i:s');
        $dateFilters = array_map(static fn(Filter $filter) => $filter->getName(), array_filter($filters, static function (Filter $filter) {
            return $filter->getType() === Filter::DATE_TYPE && !in_array(Filter::DATE_EMPTY, $filter->getOptions()?->rangeOptions ?? [], false);
        }));
        foreach ($filters as $filter) {
            $name = $filter->getName();
            $field = $filter->getField();
            $expression = $filter->getExpression();
            if ($field === Filter::SOFT_DELETED_FIELD && count($data[$name] ?? []) > 0) {
                $softDeletedHandled = true;
            }
            if (empty($expression)) {
                throw new RuntimeException("Please specify an expression for {$name}");
            }
            if ($expression === Filter::NONE) {
                continue;
            }
            if (empty($data[$name] ?? []) && in_array($name, $dateFilters, true)) {
                $data[$name] = $filter->getExpression() === Filter::BETWEEN ? [$now] : $now;
            }
            if (!isset($data[$name])) {
                continue;
            }
            $value = $data[$name];
            if (empty($value) && $value !== 0) {
                continue;
            }
            if ($filter->getGroup() !== null) {
                $groupFilters[$filter->getGroup()][] = ['filter' => $filter, 'field' => $field, 'value' => $value, 'data' => $data];
            } else {
                $this->processFilter($qb, $filter, $field, $value, $data);
            }
        }

        foreach ($groupFilters as $groupName => $groupFieldFilters) {
            $groupQb = new QueryBuilder($qb->getEntityManager());
            $joinExpression = Filter:: AND;
            foreach ($groupFieldFilters as $filter) {
                if (!empty($filter['filter']->getGroupExpression())) {
                    $joinExpression = $filter['filter']->getGroupExpression();
                }
                $this->processFilter($groupQb, $filter['filter'], $filter['field'], $filter['value'], $filter['data']);
            }

            $parameters = $qb->getParameters();
            foreach ($groupQb->getParameters() as $groupParameter) {
                $parameters->add($groupParameter);
            }
            switch ($joinExpression) {
                case Filter:: OR:
                    $joinExpression = 'orWhere';
                    break;
                case Filter:: AND:
                default:
                    $joinExpression = 'andWhere';
                    break;
            }
            $qb->$joinExpression($groupQb->getDQLPart('where'))->setParameters($parameters);
        }
        $entity = $this->getBaseTableViewModelRelatingEntity();
        //@todo - this can come from the BaseTableViewModel entity
        if (($softDeletedHandled === false) && method_exists($entity, 'getSoftDeleted')) {
            if (!defined($entity . "::HIDE_SOFT_DELETED") || $entity::HIDE_SOFT_DELETED === true) {
                $qb->andWhere("{$this->alias}.softDeleted = false");
            }
        }
        return $qb;
    }

    /**
     * @param $qb
     * @param $filter
     * @param $field
     * @param $value
     * @param $data
     * @throws Exception
     */
    private function processFilter(&$qb, $filter, $field, $value, $data): void {
        if ($this->keywordsSearch && $field !== Filter::SOFT_DELETED_FIELD) {
            return;
        }
        $inEq = $this->generateInEq($value);
        switch ($filter->getExpression()) {
            case Filter:: AND:
                if (is_array($field)) {
                    $qb->andWhere($this->generateOrX($qb, $field, $value));
                    break;
                }
                $qb->andWhere($qb->expr()->{$inEq}("{$this->alias}.{$field}", ":$field"))
                    ->setParameter(":$field", $value);
                break;
            case Filter:: OR:
                if (is_array($field)) {
                    $qb->orWhere($this->generateOrX($qb, $field, $value));
                    break;
                }
                $qb->orWhere($qb->expr()->{$inEq}("{$this->alias}.{$field}", ":$field"))
                    ->setParameter(":$field", $value);
                break;
            case Filter::LIST_AND:
            case Filter::LIST_OR:
                $andOrOr = 'andWhere';
                if ($filter->getExpression() === Filter::LIST_OR) {
                    $andOrOr = 'orWhere';
                }
                $qb->{$andOrOr}($qb->expr()->in("{$this->alias}.{$field}", ":$field"))
                    ->setParameter(":$field", sp_convert_filter_to_list($value));
                break;
            case Filter::BETWEEN:
                try {
                    ['a' => $a, 'b' => $b] = $this->processBetweenValue($value);
                } catch (Exception $e) {
                    break;
                }

                if ($filter->getType() === Filter::DATE_TYPE) {
                    ['a' => $a, 'b' => $b] = $this->processDateTypeValues(compact('a', 'b'), false);
                }
                $qb->andWhere($qb->expr()->between("{$this->alias}.{$filter->getField()}", ":{$field}a", ":{$field}b"))
                    ->setParameter(":{$field}a", $a)
                    ->setParameter(":{$field}b", $b);
                break;
            case Filter::CUSTOM:
                if ($filter->getType() === Filter::DATE_TYPE) {
                    $qb = $filter->getCustom()($this->alias, $qb, $this->processDateTypeValues($this->processBetweenValue($value)), $data, $filter,
                        $this->user());
                    break;
                }

                $parameters = (new ReflectionFunction($filter->getCustom()))->getParameters();
                foreach ($parameters as $parameter) {
                    if ($parameter->getPosition() === 2 && $parameter->getType()) {
                        if (!is_array($value) && $parameter->getType()->getName() === 'array') {
                            $value = [$value];
                        } elseif (is_array($value) && $parameter->getType()->getName() !== 'array') {
                            $value = reset($value);
                        }
                    }
                }
                $qb = $filter->getCustom()($this->alias, $qb, $value, $data, $filter, $this->_getUser());
                break;
            default:
                break;
        }
    }

    /**
     * @param $value
     * @return array
     * @throws Exception
     */
    protected function processBetweenValue($value): array {
        if (!is_array($value)) {
            $value = [$value];
        }
        if (count($value) === 0) {
            throw new Exception('Empty Value');
        }
        if (count($value) === 1) {
            [$a] = $value;
            $b = $a;
        } else {
            [$a, $b] = $value;
        }

        return compact('a', 'b');
    }

    /**
     * @param array $values
     * @param bool $returnEscaped
     * @return array
     * @throws Exception
     */
    protected function processDateTypeValues(array $values, bool $returnEscaped = true): array {
        ['a' => $a, 'b' => $b] = $values;

        $a = new DateTime($a);
        $a->setTime(0, 0, 0);
        $b = new DateTime($b);
        $b->setTime(23, 59, 59);
        if ($this->user() !== null) {
            $a = $this->user()->convertDateToUtcStartOfDay($a);
            $b = $this->user()->convertDateToUtcEndOfDay($b);
        }
        if ($returnEscaped) {
            $a = "'$a'";
            $b = "'$b'";
        }
        return compact('a', 'b');
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getBaseResults(Request $request): array {
        return [];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    protected function _index(Request $request): JsonResponse {
        [
            'firstResult' => $firstResult,
            'maxResults' => $maxResults,
            'qb' => $qb,
            'currentPage' => $currentPage,
            'columns' => $columns,
        ] = $this->getBaseResults($request);

        if ($qb) {
            $qb->setFirstResult($firstResult)
                ->setMaxResults($maxResults);
        }

        $response = $this->paginate(
            $qb,
            $request->attributes->get('_route'),
            $maxResults,
            $currentPage,
            false,
            $columns,
            $request->attributes->get('_route_params')
        );

        return $this->json($response);
    }

    /**
     * @throws Exception
     */
    protected function _getBaseResults(Request $request): array {
        [
            'filterData' => $filterData,
            'data' => $data,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortDesc' => $sortDesc,
            'currentPage' => $currentPage,
            'maxResults' => $maxResults,
            'firstResult' => $firstResult,
        ] = $this->getPaginateRequestData($request);
        $viewModel = $this->resolveBaseViewModel($this->entity);
        $qb = $viewModel->getQuery();

        $filters = $viewModel->getFilters();
        $searchableFields = $viewModel->getSearchableFields();
        $columns = $viewModel->getColumns();
        $this->generateQueryBasedOnFilters($qb, $filters, $filterData);
        $this->generateQueryBasedOnSearchableFields($qb, $searchableFields, $searchTerm);
        $this->generateQueryBasedOnOrderBy($qb, $sortBy, $sortDesc, $columns);
        return compact(
            'firstResult',
            'maxResults',
            'qb',
            'currentPage',
            'columns',
            'filterData'
        );
    }

    protected function _getFilters(): JsonResponse {
        return $this->json( $this->getSession($this->getFiltersFromEntity()));
    }

    /**
     * @param QueryBuilder $qb
     * @param array $searchableFields
     * @param string|null $searchTerm
     * @param string $mode
     * @return QueryBuilder
     */
    public function generateQueryBasedOnSearchableFields(
        QueryBuilder $qb,
        array $searchableFields,
        ?string $searchTerm,
        string $mode = self::SEARCH_OR
    ): QueryBuilder {
        if (empty($searchTerm)) {
            return $qb;
        }
        if (empty($searchableFields)) {
            return $qb;
        }
        if ($mode !== self::SEARCH_OR || $mode === self::SEARCH_AND) {
            throw new RuntimeException('Invalid search mode provided');
        }
        $splitSearchTerm = explode('|', trim($searchTerm));  // multiple search terms ???
        $qbOrXParent = [];
        foreach ($splitSearchTerm as $idx => $newSearchTerm) {
            $splitSearchTerm[$idx] = sp_strip_4byte_emojis($newSearchTerm);
        }

        foreach ($splitSearchTerm as $idx => $newSearchTerm) {
            $newSearchTerm = trim($newSearchTerm);
            $qbOrX = [];
            $this->keywordsSearch = false;
            $condition = null;
            if (in_array('keywordsIdentifier', $searchableFields, true)) {
                $qbOrX[] = $qb->expr()->eq("{$this->alias}.keywordsIdentifier", ":searchTerm{$idx}");
                $this->keywordsSearch = true;
            } else {
                if (isset($searchableFields['condition'], $searchableFields['condition'])) {
                    $condition = $searchableFields['condition'];
                    unset($searchableFields['condition']);
                }
                foreach ($searchableFields as $searchableField) {
                    if ($searchableField instanceof Closure) {
                        $qb = $searchableField($this->alias, $qb, $newSearchTerm);
                        continue;
                    }
                    if (is_array($searchableField) && count($searchableField) === 0) {
                        throw new RuntimeException('Array search fields require more than one entry');
                    }
                    if (is_array($searchableField)) {
                        $alias = $this->alias;
                        $itemCondition = null;
                        if (isset($searchableField['condition'])) {
                            $itemCondition = $searchableField['condition'];
                            unset($searchableField['condition']);
                        }
                        foreach ($searchableField as &$item) {
                            $split = explode(".", $item);
                            $splitCount = count($split);
                            if ($splitCount > 1) {
                                ['finalRelation' => $finalRelation, 'finalField' => $finalField] = $this->generateSearchableRelationships(
                                    $split,
                                    $splitCount,
                                    $qb
                                );
                                $alias = $finalRelation;
                                $item = $finalField;
                            }
                        }
                        unset($item);
                        $searchableFieldWithSpaces = [];
                        foreach ($searchableField as $item) {
                            $searchableFieldWithSpaces[] = "COALESCE($alias.$item, '')";
                            $searchableFieldWithSpaces[] = "' '";
                        }
                        array_pop($searchableFieldWithSpaces);

                        $firstSearchableField = $searchableFieldWithSpaces[0];
                        if (count($searchableFieldWithSpaces) > 1) {
                            $qbItem = $qb->expr()->like($qb->expr()->concat($firstSearchableField, ...array_splice($searchableFieldWithSpaces, 1)),
                                ":searchTerm{$idx}");
                        } else {
                            $qbItem = $qb->expr()->like($firstSearchableField, ":searchTerm{$idx}");
                        }
                        if ($itemCondition !== null) {
                            $qbItem = $qb->expr()->andX($qbItem, "$alias.$itemCondition");
                        }
                        $qbOrX[] = $qbItem;
                        continue;
                    }

                    if (array_key_exists($searchableField, self::REQUIRES_TRANSLATE_RELATION)) {
                        $qb = $this->generateRelation($qb, $searchableField);
                        $qbOrX[] = $qb->expr()->like("$searchableField.name", ":searchTerm{$idx}");
                        continue;
                    }

                    $split = explode(".", $searchableField);
                    $splitCount = count($split);
                    if ($splitCount > 1) {
                        ['finalRelation' => $finalRelation, 'finalField' => $finalField] = $this->generateSearchableRelationships(
                            $split,
                            $splitCount,
                            $qb
                        );
                        $qbOrX[] = $qb->expr()->like("{$finalRelation}.{$finalField}", ":searchTerm{$idx}");
                        continue;
                    }

                    if ($searchableField !== 'keywordsIdentifier') {
                        $qbOrX[] = $qb->expr()->like("{$this->alias}.$searchableField", ":searchTerm{$idx}");
                    }
                }
            }
            if (empty($qbOrX)) {
                continue;
            }
            $qbItem = $qb->expr()->orX($qb->expr()->orX(...$qbOrX));
            if ($condition !== null) {
                $qbItem = $qb->expr()->andX($qbItem, "$this->alias.$condition");
            }
            $qbOrXParent[] = $qbItem;
            if ($this->keywordsSearch === false) {
                $newSearchTerm = "%$newSearchTerm%";
            }
            $qb->setParameter("searchTerm{$idx}", (string)$newSearchTerm, ParameterType::STRING);

        }
        if (empty($qbOrXParent)) {
            return $qb;
        }
        if ($mode === self::SEARCH_OR) {
            $qb->andWhere($qb->expr()->orX($qb->expr()->orX(...$qbOrXParent)));
        } else {
            $qb->andWhere($qb->expr()->andX($qb->expr()->andX(...$qbOrXParent)));
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $orderByFields
     * @param array $orderByOrder
     * @param array $columns
     * @return QueryBuilder
     */
    public function generateQueryBasedOnOrderBy(
        QueryBuilder $qb,
        array $orderByFields,
        array $orderByOrder,
        array $columns
    ): QueryBuilder {
        if (empty($orderByFields)) {
            /** @var Column $column */
            $columns = array_filter($columns,
                static fn(Column $item) => !empty($item->getDefault()));
            usort($columns, static fn(Column $a, Column $b) => $a->getOrder() > $b->getOrder());
            $orderByFields = array_map(static fn(Column $item) => $item->getValue(), $columns);
            $orderByOrder = array_map(static fn(Column $item) => $item->getDefault() !== 'ASC', $columns);
        }
        foreach ($orderByFields as $index => $byField) {
            /** @var Column $column */
            $column = sp_array_find($columns, static function (Column $item) use ($byField) {
                return $item->getValue() === $byField;
            });
            if ($column === null) {
                continue;
            }
//            $orderByDirection = $orderByOrder[$index]
            if (!empty($column->getColumns())) {
                foreach ($column->getColumns() as $field) {
                    $qb = $this->generateOrderBy($qb, $field, !($orderByOrder[$index] ?? false) ? 'ASC' : 'DESC');
                }
                continue;
            }

            if ($column->getCustom()) {
                $qb = $column->getCustom()($this->alias, $qb, !($orderByOrder[$index] ?? false) ? 'ASC' : 'DESC');
                continue;
            }
            $qb = $this->generateOrderBy($qb, $byField, !($orderByOrder[$index] ?? false) ? 'ASC' : 'DESC');
        }
        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $field
     * @param $ascOrDesc
     * @return QueryBuilder
     */
    private function generateOrderBy(QueryBuilder $qb, $field, $ascOrDesc): QueryBuilder {
        if (array_key_exists($field, self::REQUIRES_TRANSLATE_RELATION)) {
            $qb = $this->generateRelation($qb, $field);
            $qb->addOrderBy("{$field}.name", $ascOrDesc);
        } elseif (str_contains($field, '.')) {
            $qb->addOrderBy($field, $ascOrDesc);
        } else {
            $qb->addOrderBy("{$this->alias}.$field", $ascOrDesc);
        }
        return $qb;
    }


    /**
     * @param QueryBuilder $qb
     * @param $field
     * @param string|null $alias
     * @return QueryBuilder
     */
    private function generateRelation(QueryBuilder $qb, $field, ?string $alias = null): QueryBuilder {
        if (sp_dql_has_select($qb, $field)) {
            return $qb;
        }
        if (sp_dql_has_join($qb, $field)) {
            return $qb;
        }
        $alias = $alias ?? $this->alias;
        $qb->leftJoin("{$alias}.$field", $field);
        return $qb;
    }


    /**
     * @param QueryBuilder $qb
     * @param mixed $fields
     * @param $value
     * @return Expr\Orx
     */
    private function generateOrX(QueryBuilder $qb, $fields, $value): Expr\Orx {
        $qbOrX = [];
        foreach ($fields as $index => $field) {
            $qbOrX[] = $qb->expr()->{$this->generateInEq($value)}("{$this->alias}.{$field}", ":{$field}{$index}");
            $qb->setParameter(":{$field}{$index}", $value);
        }
        return $qb->expr()->orX($qb->expr()->orX(...$qbOrX));
    }

    /**
     * @param $value
     * @return string
     */
    private function generateInEq($value): string {
        return is_array($value) ? 'in' : 'eq';
    }


    public function getPaginateRequestData(Request $request, bool $checkSearchTerm = true, bool $setSession = true): array {
        $data = $request->request->all();
        $filterData = json_decode($data['filters'] ?? null, true);
        if (!is_array($filterData)) {
            $filterData = [];
        }
        $searchTerm = empty($data['search']) ? null : $data['search'];
        $sortBy = $data['sortBy'] ?? [];
        $sortDesc = array_map(static fn ($item) => $item == 'true', $data['sortDesc'] ?? []);

        $currentPage = $data['page'] ?? 1;
        $maxResults = (int)($data['rowsPerPage'] ?? $this->defaultPageSize);
        $initial = $data['initial'] ?? false;
        $filtersTheSame = false;
        if ($setSession) {
            $filtersTheSame = $this->setSession($filterData, true);
        }
        $savedSearchTerm = $this->getSearchTermSession();
        if (empty($searchTerm) && $initial) {
            $searchTerm = $savedSearchTerm ? base64_decode($savedSearchTerm) : null;
        } else {
            $encodedSearch = base64_encode($searchTerm);
            $encodedSearch = empty($encodedSearch) ? null : $encodedSearch;
            $savedSearchTerm = empty($savedSearchTerm) ? null : $savedSearchTerm;
            if ($encodedSearch !== $savedSearchTerm) {
                $this->setSearchTermSession($searchTerm);
                $currentPage = 1;
            }
        }
        $this->searchTerm = $searchTerm;
        if ($checkSearchTerm === true && !empty($searchTerm)) {
            $filterData = [];
            $this->keywordsSearch = true;
        }

        if (!empty($sortBy) || !$initial) {
            $this->setSortBy($sortBy, false);
        }
        $sortBy = $this->getSortBy(false);
        if (!empty($sortDesc) || !$initial) {
            $this->setSortBy($sortDesc, true);
        }
        $sortDesc = $this->getSortBy(true);
        $this->sortBy = $sortBy;
        $this->sortByDesc = $sortDesc;
        if (!in_array($maxResults, $this->defaultPageSizeOptions, true)) {
            $maxResults = $this->defaultPageSize;
        }
        if (!$filtersTheSame) {
            $currentPage = 1;
        }
        if ($request->attributes->get('ignoreSessionPage') !== true) {
            if ($filtersTheSame && $initial && !$this->keywordsSearch) {
                $pagingInformation = $this->getSessionPagingInformation();
                $currentPage = $pagingInformation['currentPage'] ?? $currentPage;
                $maxResults = $pagingInformation['maxResults'] ?? $maxResults;
            }
            $this->setSessionPagingInformation($currentPage, $maxResults);
        }
        $firstResult = $maxResults * ($currentPage - 1);
        return compact('filterData', 'data', 'searchTerm', 'sortBy', 'sortDesc', 'currentPage', 'maxResults', 'firstResult');
    }

    /**
     * @param QueryBuilder|mixed $query
     * @param string $url
     * @param int $maxResults
     * @param int $currentPage
     * @param bool $fetchJoinCollection
     * @param array $columns
     * @param array $routeParameters
     * @return array
     */
    public function paginate(
        $query,
        string $url,
        int $maxResults = 10,
        int $currentPage = 1,
        $fetchJoinCollection = false,
        $columns = [],
        $routeParameters = []
    ) {
        $paginator = null;
        $data = [];
        if ($query !== null) {
            if (($query instanceof QueryBuilder || $query instanceof Query) && $query->getMaxResults() === null) {
                throw new RuntimeException('Query is missing a limit');
            }
            $paginator = new Paginator($query, $fetchJoinCollection);
            if (!$this->isBaseTableViewModel($this->entity)) {
                foreach ($paginator as $item) {
                    $data[] = $this->processResult($item);
                }
                $data = $this->postProcessResult($data);
            } else {
                $data = $this->baseTableViewModelProcessing($paginator);
            }
        }

        $routeParametersNextPage = [
            'page' => $currentPage + 1,
        ];

        $routeParametersPreviousPage = [
            'page' => $currentPage - 1,
        ];

        if ($routeParameters !== [] && $routeParameters !== null) {
            $routeParametersNextPage = array_merge($routeParametersNextPage, $routeParameters);
            $routeParametersPreviousPage = array_merge($routeParametersPreviousPage, $routeParameters);
        }

        return $this->getPaginatorResults(
            $paginator,
            $data,
            $this->generateUrl($url, $routeParametersNextPage),
            $this->generateUrl($url, $routeParametersPreviousPage),
            $maxResults,
            $currentPage,
            $columns,
            $this->sortBy,
            $this->sortByDesc,
            $this->searchTerm
        );
    }

    /**
     * @param Paginator $paginator
     * @param $data
     * @param $nextPageUrl
     * @param $prevPageUrl
     * @param int $maxResults
     * @param int $currentPage
     * @param array $columns
     * @param array $sortBy
     * @param array $sortByDesc
     * @param null $search
     * @return array
     */
    public function getPaginatorResults(
        $paginator,
        $data,
        $nextPageUrl,
        $prevPageUrl,
        int $maxResults = 10,
        int $currentPage = 1,
        array $columns = [],
        array $sortBy = [],
        array $sortByDesc = [],
        $search = null
    ): array {
        $firstResult = $maxResults * ($currentPage - 1);
        $total = 0;
        if ($paginator !== null) {
            $paginator->setUseOutputWalkers(false);
            if (!sp_dql_has_distinct($paginator->getQuery())) {
                $paginator->getQuery()->setHint(CountWalker::HINT_DISTINCT, false);
            }
            $total = $paginator->count();
        }
        $pagesCount = ceil($total / $maxResults);
        $columns = array_map(static function (Column $column) {
            return (object)$column->toArray();
        }, $columns);

        return [
            'total' => $total,
            'per_page' => $maxResults,
            'current_page' => (int)$currentPage,
            'last_page' => $pagesCount,
            'next_page_url' => $currentPage !== $pagesCount ? $nextPageUrl : null,
            'prev_page_url' => $currentPage !== 1 ? $prevPageUrl : null,
            'from' => $firstResult + 1,
            'to' => min($firstResult + $maxResults, $total),
            'data' => $data,
            'rows_per_page_item' => $this->defaultPageSizeOptions,
            'columns' => $columns,
            'sort_by' => $sortBy,
            'sort_by_desc' => $sortByDesc,
            'search' => $search
        ];
    }

    protected function _getUser(): ?User {
        return $this->getUser();
    }

    protected function _getSerializer(): ?SerializerInterface {
        return $this->serializer;
    }

    protected function _getSession(): ?SessionInterface {
        return $this->session;
    }

    /**
     * @param $item
     * @param array $options
     * @return array
     */
    protected function processResult($item, array $options = []) {
        if ($this->isBaseTableViewModel($this->entity)) {
            return $this->getInstanceOfBaseTableViewModel($this->entity)->processResult($item, $options);
        }
        return $item;
    }

    /**
     * @param array $items
     * @param array $options
     * @return array
     */
    protected function postProcessResult(array $items, array $options = []): array {
        if ($this->isBaseTableViewModel($this->entity)) {
            return $this->getInstanceOfBaseTableViewModel($this->entity)->postProcessResult($items, $options);
        }
        return $items;
    }

    /**
     * @param $filterData
     * @param $associatedFilters
     * @return string
     */
    public function limitOnParameters($filterData, $associatedFilters): string {
        $return = '';
        foreach ($associatedFilters as $filter => $databaseField) {
            if (isset($filterData[$filter])) {
                if (is_array($filterData[$filter])) {
                    if (count($filterData[$filter]) > 0) {
                        $parameterLimit = implode(',', $filterData[$filter]);
                    }
                } elseif ($filterData[$filter] !== '') {
                    $parameterLimit = $filterData[$filter];
                }
                if (isset($parameterLimit)) {
                    $return .= " and $databaseField in ($parameterLimit)";
                    break;
                }
            }
        }
        return $return;
    }

    /**
     * @param $ids
     * @param $filterData
     * @param $fieldToCheck
     * @return array
     * @throws SqlInjectionException
     */
    public function generateLimitsOnParameters($ids, $filterData, $fieldToCheck): array {
        $append = true;
        if (count($ids) > 0) {
            $ids = array_map('intval', $ids);
            if (isset($filterData[$fieldToCheck]) && !empty($filterData[$fieldToCheck])) {
                if (!is_array($filterData[$fieldToCheck])) {
                    $filterData[$fieldToCheck] = [$filterData[$fieldToCheck]];
                }
                $intFields = array_map('intval', $filterData[$fieldToCheck]);
                $allFieldsInList = true;
                foreach ($intFields as $parameterFields) {
                    if (!in_array($parameterFields, $ids)) {
                        $allFieldsInList = false;
                        break;
                    }
                }
                $append = !$allFieldsInList;
            }
        }
        $idList = sp_value_implode_values($ids);
        return compact('append', 'idList');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function setFilterSession(Request $request): JsonResponse {
        $data = $request->request->all();
        try {
            $this->setSession($data, true);
            return $this->json([]);
        } catch (Exception $e) {
            return $this->json([],Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    /**
     * @param $split
     * @param int $splitCount
     * @param $qb
     * @return array
     */
    private function generateSearchableRelationships($split, int $splitCount, $qb): array {
        [$finalRelation, $finalField] = array_slice($split, -2, $splitCount);
        $relations = array_splice($split, 0, $splitCount - 2);
        $lastRelation = null;
        foreach ($relations as $relation) {
            $qb = $this->generateRelation($qb, $relation, $lastRelation ?? $this->alias);
            $lastRelation = $relation;
        }
        $this->generateRelation($qb, $finalRelation, $lastRelation);
        return compact('finalRelation', 'finalField');
    }
}
