<?php

namespace App\ViewModels;

use App\Classes\Column;
use App\Classes\Filter;
use App\Entity\Store;
use App\Entity\User;
use App\Enums\StoreStatus;
use App\ViewModels\Options\QueryOptions;
use Doctrine\ORM\QueryBuilder;

class StoreViewModel extends BaseTableViewModel
{
    public const SESSION = 'admin_stores';
    public const ALIAS = 's';
    public const RELATING_ENTITY = Store::class;

    public function hasAccess(?array $options = []): bool {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    public function getFilters(?array $options = [], $filterOptions = null): array {
        return [
            Filter::createFilter('Brand', 'brand', 'api_brands_filter')
                ->setField('brand')
                ->setMultiple(true)
                ->setExpression(Filter::AND),
            Filter::createFilter('Status', 'status')
                ->setField('status')
                ->setMultiple(true)
                ->setData(StoreStatus::selectMapping())
                ->setExpression(Filter::AND)
        ];
    }

    public function getColumns(?array $options = [], $columnOptions = null): array {
        return [
            Column::createColumn('Name', 'center', true, 'name')->setDefaultASC(),
            Column::createColumn('Brand', 'center', true, 'brand'),
            Column::createColumn('Industry', 'center', true, 'industry'),
            Column::createColumn('Status', 'center', true, 'status'),
            Column::actionColumn(),
        ];
    }

    public function getQuery(?array $options = [], ?QueryOptions $queryOptions = null): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(self::getAlias())->from(self::RELATING_ENTITY, self::getAlias());
        return $qb;
    }

    /**
     * @param Store $item
     * @param array|null $options
     * @return array
     */
    public function processResult($item, ?array $options = []): array {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'brand' => $item->getBrand()->getName(),
            'industry' => $item->getIndustry(),
            'status' => $item->getStatus(),
            'statuses' => StoreStatus::selectMapping()
        ];
    }

    public function getSearchableFields(?array $options = [], $searchableFieldOptions = null): array {
       return [];
    }

    public function getExportHeadings(?array $options = []): array {
        return [];
    }

    public function getExportRow($item, ?array $options = [], $exportRowOptions = null): array {
        return [];
    }
}
