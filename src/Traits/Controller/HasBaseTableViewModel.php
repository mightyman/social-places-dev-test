<?php

namespace App\Traits\Controller;

use App\ViewModels\BaseTableViewModel;


trait HasBaseTableViewModel
{
    private ?BaseTableViewModel $instanceOfBaseTableViewModel = null;

    public function baseTableViewModelSetup(): void {
        if ($this->isBaseTableViewModel($this->entity)) {
            if (property_exists(self::class, 'sessionPrefix')) {
                $this->sessionPrefix = $this->entity::SESSION;
            }
            if (property_exists(self::class, 'alias')) {
                $this->alias = $this->entity::ALIAS;
            }
        }
    }

    public function isBaseTableViewModel(?string $class): bool {
        return !($class === null) && is_a($class, BaseTableViewModel::class, true);
    }

    public function resolveBaseViewModel(string $class): BaseTableViewModel {
        return new $class($this->entityManager, $this->router, $this->security, $this->getUser());
    }

    public function getInstanceOfBaseTableViewModel(string $class): BaseTableViewModel {
        if (!$this->instanceOfBaseTableViewModel || $class !== get_class($this->instanceOfBaseTableViewModel)) {
            $this->instanceOfBaseTableViewModel = $this->resolveBaseViewModel($class);
            $this->baseTableViewModelSetup();
        }
        return $this->instanceOfBaseTableViewModel;
    }

    public function baseTableViewModelProcessing($items): array {
        $data = [];
        $viewModel = $this->getInstanceOfBaseTableViewModel($this->entity);
        foreach ($items ?? [] as $item) {
            $data[] = $viewModel->processResult($item);
        }
        return $viewModel->postProcessResult($data);
    }

    public function getBaseTableViewModelRelatingEntity(): ?string {
        return $this->isBaseTableViewModel($this->entity) ? $this->entity::RELATING_ENTITY : $this->entity;
    }

    public function baseTableViewModelHasAccess(): void {
        if ($this->isBaseTableViewModel($this->entity)) {
            $viewModel = $this->resolveBaseViewModel($this->entity);
            if (!$viewModel->hasAccess()) {
                throw new /*AccessDenied*/\Exception();
            }
        }
    }

    public function changeBaseTableViewModel($class): void {
        $this->entity = $class;
        $this->baseTableViewModelSetup();
    }

}
