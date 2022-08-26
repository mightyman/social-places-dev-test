<?php

namespace App\Traits\Controller;

use App\Classes\Filter;
use Exception;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

trait HasSession
{
    use HasBaseTableViewModel;

    protected ?string $entity;
    protected ?string $sessionPrefix;
    protected ?string $sessionCase = null;

    public function setSession($data, bool $fromIndex = false): bool {
        $filters = $this->getFiltersFromEntity($this->sessionCase);
        if (isset($data['filterData'])) {
            $data = $data['filterData'];
        }
        $filterSessionData = [];
        foreach ($filters as $filter) {
            $session = $this->getSessionNameForFilter($filter->getSession()) ?? $this->getSessionName();
            $name = $filter->getName();
            if (!isset($filterSessionData[$session])) {
                $filterSessionData[$session] = [];
            }
            $filterSessionData[$session][$name] = $data[$name] ?? $filter->getValues();
        }
        if (!$fromIndex) {
            return true;
        }
        $isSame = true;
        foreach ($filterSessionData as $sessionKey => $newSessionData) {
            $oldSession = $this->session->get("{$sessionKey}_encoded", "");
            $this->session->set($this->getSessionName(), $newSessionData);
            $encoded = base64_encode(json_encode($newSessionData));
            $this->session->set("{$this->getSessionName()}_encoded", $encoded);
            if (empty($oldSession) || !$isSame) {
                continue;
            }
            if ($oldSession !== $encoded) {
                $isSame = false;
            }
        }
        return $isSame;
    }

    /**
     * @param Filter[] $filters
     * @return Filter[]
     */
    public function getSession(array $filters): array {
        foreach ($filters as $filter) {
            $name = $filter->getName();
            $session = $this->getSessionNameForFilter($filter->getSession()) ?? $this->getSessionName();
            $sessionData = $this->session->get($session, []);
            if (isset($sessionData[$name])) {
                $filter->setValues($sessionData[$name]);
            }
        }
        return $filters;
    }

    public function getSessionData(): ?array {
        return $this->session->get($this->getSessionName(), []);
    }

    public function setSearchTermSession($data, bool $fromIndex = false) {
        $sessionKey = $this->getSessionName();
        $encoded = $data ? base64_encode($data) : null;
        $this->session->set("{$sessionKey}_search_term_encoded", $encoded);
    }

    public function getSearchTermSession() {
        $sessionKey = $this->getSessionName();
        return $this->session->get("{$sessionKey}_search_term_encoded", "");
    }

    public function setSessionPagingInformation(int $currentPage, int $maxResults): void {
        $sessionKey = $this->getSessionName();
        $this->session->set("{$sessionKey}_paging_information", compact('currentPage', 'maxResults'));
    }

    public function getSessionPagingInformation(): array {
        $sessionKey = $this->getSessionName();
        return $this->session->get("{$sessionKey}_paging_information", []);
    }

    public function setSortBy($data, bool $desc = false) {
        $sessionKey = $this->getSessionName();
        $encoded = json_encode($data);
        $descKey = $desc ? 'desc' : 'asc';
        $this->session->set("{$sessionKey}_sort_by_{$descKey}", $encoded);
    }

    public function getSortBy($desc = false) {
        $sessionKey = $this->getSessionName();
        $descKey = $desc ? 'desc' : 'asc';
        $data = $this->session->get("{$sessionKey}_sort_by_{$descKey}", json_encode([]));
        return json_decode($data, false);
    }

    private function getSessionName(): string {
        return !empty($this->sessionPrefix) ? "{$this->sessionPrefix}_filter_session_data" : 'filter_session_data';
    }

    private function getSessionNameForFilter(?string $sessionPrefix): ?string {
        return !empty($sessionPrefix) ? "{$sessionPrefix}_filter_session_data" : null;
    }

    /**
     * @param string|null $specialCase
     * @return array|Filter[]
     */
    protected function getFiltersFromEntity(?string $specialCase = null): array {
        if ($this->isBaseTableViewModel($this->entity)) {
            return $this->getInstanceOfBaseTableViewModel($this->entity)->getFilters();
        }
        $function = 'get' . ucfirst(($specialCase ?? '')) . 'Filters';
        try {
            new ReflectionMethod($this->entity, $function);
        } catch (Exception $exception) {
            return [];
        }
        return $this->entity::$function($this->get('router'), $this->getDoctrine()->getManager(), $this->getUser(), null, $this->security);
    }

    protected function getSessionDataAndSave(Request $request, bool $fromIndex = false) {
        $data = $this->getRequestData($request);
        $this->setSession($data, $fromIndex);
        return $data;
    }

}
