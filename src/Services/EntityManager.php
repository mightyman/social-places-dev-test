<?php

namespace App\Services;

use App\Traits\Services\HasEntityManagerAdditionalMethods;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Exception;


class EntityManager extends EntityManagerDecorator
{
    use HasEntityManagerAdditionalMethods;

    public const ENTITY_MANAGER_CLOSED = 'The EntityManager is closed.';
    public const COLUMN_FETCH_TYPE = 0;
    public const ROW_FETCH_TYPE = 1;

    private $exceptions = [];
    private $exceptionsToTrack = [
        self::ENTITY_MANAGER_CLOSED
    ];

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function flush($entity = null) {
        try {
            parent::flush($entity);
        } catch (Exception $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function refresh($object) {
        try {
            parent::refresh($object);
        } catch (Exception $exception) {
            $this->handleException($exception);
        }
    }

    public function reset(): void {
        if (!$this->isOpen()) {
            $this->wrapped = $this->wrapped::create($this->getConnection(), $this->getConfiguration());
        }
    }

    /**
     * @param Exception $exception
     * @throws Exception
     */
    private function handleException(Exception $exception): void {
        if (count($this->exceptions) > 0 && in_array($exception->getMessage(), $this->exceptionsToTrack, true)) {
            $lastException = end($this->exceptions);
            $this->exceptions = [];
            throw new Exception($exception->getMessage(), $exception->getCode(), $lastException);
        }
        $this->exceptions[] = $exception;
        throw $exception;
    }

    /**
     * @inheritdoc
     */
    protected function getManager(): EntityManagerInterface {
        return $this;
    }

    /**
     * @param $table
     * @param $returnName
     * @return ClassMetadata|mixed|string
     */
    public function getClassMetadataFromTable($table, $returnName = false) {
        foreach ($this->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($table === $metadata->getTableName()) {
                if ($returnName) {
                    return $metadata->getName();
                }
                return $metadata;
            }
        }
        throw new \RuntimeException('No entity is associated to the specified table');
    }
}
