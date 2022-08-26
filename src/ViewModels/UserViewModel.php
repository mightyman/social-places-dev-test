<?php

namespace App\ViewModels;

use App\Classes\Column;
use App\Classes\Filter;
use App\Entity\User;
use App\ViewModels\Options\QueryOptions;
use Doctrine\ORM\QueryBuilder;

class UserViewModel extends BaseTableViewModel
{
    public const SESSION = 'admin_users';
    public const ALIAS = 'u';
    public const RELATING_ENTITY = User::class;

    public function hasAccess(?array $options = []): bool {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    public function getFilters(?array $options = [], $filterOptions = null): array {
        return [
            Filter::createFilter('Primary Role', 'primary-role')
                ->setData(sp_convert_assoc_arr_to_id_name_arr(['ROLE_ADMIN' => 'Admin', 'ROLE_USER' => 'User']))
                ->setSession(self::getSession())
                ->setMultiple(true)
                ->setExpression(Filter::CUSTOM)
                ->setCustom(static function (string $alias, QueryBuilder $qb, array $values, array $data) {
                    if (empty($values)) {
                        return $qb;
                    }
                    foreach ($values as &$value) {
                        $value = $qb->expr()->like("{$alias}.roles", $qb->expr()->literal("%{$value}%"));
                    }
                    unset($value);
                    $qb->andWhere($qb->expr()->orX($qb->expr()->orX(...$values)));
                    return $qb;

                })
        ];
    }

    public function getColumns(?array $options = [], $columnOptions = null): array {
        return [
            Column::createColumn('Full Name', 'start', true, 'fullname')->setColumns(['name', 'surname'])->setDefaultASC(),
            Column::createColumn('Primary Role', 'center', false, 'role'),
            Column::actionColumn(),
        ];
    }

    public function getQuery(?array $options = [], ?QueryOptions $queryOptions = null): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(self::getAlias())->from(User::class, self::getAlias());
        return $qb;
    }

    /**
     * @param User $item
     * @param array|null $options
     * @return array
     */
    public function processResult($item, ?array $options = []): array {
        return [
            'id' => $item->getId(),
            'fullname' => $item->getFullname(),
            'username' => $item->getUserIdentifier(),
            'role' => sp_string_humanize_role($item->getPrimaryRole()),
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
