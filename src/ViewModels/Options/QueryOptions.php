<?php

namespace App\ViewModels\Options;

use Symfony\Component\HttpFoundation\Request;

class QueryOptions
{

    private ?array $filterData;

    /**
     * @param array|null $filterData
     */
    public function __construct(?array $filterData) {
        $this->filterData = $filterData;
    }

    /**
     * @return array|null
     */
    public function getFilterData(): ?array {
        return $this->filterData;
    }

    /**
     * @param array|null $filterData
     */
    public function setFilterData(?array $filterData): void {
        $this->filterData = $filterData;
    }


}
