<?php

namespace App\ViewModels;

use App\Entity\User;
use App\Services\EntityManager;
use App\ViewModels\Options\QueryOptions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

abstract class BaseTableViewModel
{
    /** @override $alias to be used for queries */
    public const ALIAS = '';
    public const SESSION = '';
    public const RELATING_ENTITY = '';
    public const EXPORT_LIMIT = null;
    protected EntityManagerInterface|EntityManager $entityManager;
    protected RouterInterface $router;
    protected Security $security;
    protected ?User $user;
    private string $class;

    /**
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param Security $security
     * @param User|null $user
     */
    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, Security $security, ?User $user) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->security = $security;
        $this->user = $user;
        $this->class = get_class($this);
        if ($this->class::ALIAS === '' || $this->class::SESSION === '' || $this->class::RELATING_ENTITY === '') {
            throw new \RuntimeException('Please override ALIAS or SESSION or RELATING_ENTITY');
        }
    }

    abstract public function hasAccess(?array $options = []): bool;

    abstract public function getFilters(?array $options = [], $filterOptions = null): array;

    abstract public function getColumns(?array $options = [], $columnOptions = null): array;

    abstract public function getQuery(?array $options = [], ?QueryOptions $queryOptions = null): QueryBuilder;

    abstract public function processResult($item, ?array $options = []): array;

    public function postProcessResult(array $items, ?array $options = []): array {
        return $items;
    }

    abstract public function getSearchableFields(?array $options = [], $searchableFieldOptions = null): array;

    abstract public function getExportHeadings(?array $options = []): array;

    abstract public function getExportRow($item, ?array $options = [], $exportRowOptions = null): array;

    public function getClass(): string {
        return $this->class;
    }

    public static function getSession($suffix = null): string {
        if ($suffix) {
            return get_called_class()::SESSION . '_' . $suffix;
        }
        return get_called_class()::SESSION;
    }

    public static function getAlias(): string {
        return get_called_class()::ALIAS;
    }

    public static function getRelatingEntity(): string {
        return get_called_class()::RELATING_ENTITY;
    }
}
