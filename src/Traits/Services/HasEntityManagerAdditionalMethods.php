<?php

namespace App\Traits\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Exception;
use RuntimeException;
use App\Services\EntityManager;

trait HasEntityManagerAdditionalMethods
{
    public static bool $STRIP_UNWANTED_CHARS = false;

    /**
     * @return EntityManagerInterface
     */
    abstract protected function getManager(): EntityManagerInterface;

    /**
     * @param string $query
     * @param array $params
     * @return Result|null
     * @throws \Doctrine\DBAL\Exception
     */
    private function prepareQuery(string $query, array $params = []): ?Result {
        try {
            $connection = $this->getManager()->getConnection();
            $prepared = $connection->prepare($query);
            foreach ($params ?? [] as $keyParam => $param) {
                if ($param instanceof Parameter) {
                    $prepared->bindValue($param->getName(), sp_convert_parameter_to_sql_value($param->getValue()));
                } else {
                    if ($keyParam[0] !== ':') {
                        $keyParam = ':' . $keyParam;
                    }
                    $prepared->bindValue($keyParam, sp_convert_parameter_to_sql_value($param));
                }
            }
            return $prepared->executeQuery();
        } catch (Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return null;
        }
    }

    /**
     * @param $query
     * @param ArrayCollection|array|null $params
     * @param string|null $column
     * @param string|null $columnAsIndex
     * @return array
     * @throws Exception
     */
    public function query($query, $params = [], ?string $column = null, ?string $columnAsIndex = null): array {
        try {
            $all = $this->prepareQuery($query, $params)?->fetchAllAssociative() ?? [];
            if (self::$STRIP_UNWANTED_CHARS) {
                foreach ($all as $rowIndex => $row) {
                    $all[$rowIndex] = array_map('sp_strip_db_chars', $row);
                }
            }
        } catch (Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return [];
        }

        if ($column && $columnAsIndex) {
            return array_combine(array_column($all, $columnAsIndex), array_column($all, $column));
        }

        if ($column) {
            return array_column($all, $column);
        }

        if ($columnAsIndex) {
            return sp_array_column_to_index($all, $columnAsIndex);
        }
        return $all;
    }

    /**
     * @param $query
     * @param array $params
     * @param int $fetchType
     * @param bool $forceLimit
     * @return mixed
     * @throws Exception
     */
    public function querySingle($query, $params = [], int $fetchType = EntityManager::COLUMN_FETCH_TYPE, bool $forceLimit = true) {
        try {
            if ($forceLimit && stripos($query, ' limit ') === false) {
                $query .= ' limit 1';
            }
            $functionToUse = 'fetchOne';
            if ($fetchType === EntityManager::ROW_FETCH_TYPE) {
                $functionToUse = 'fetchAllAssociative';
            }
            $all = $this->prepareQuery($query, $params)?->$functionToUse();
            if (self::$STRIP_UNWANTED_CHARS) {
                if ($functionToUse === 'fetchOne') {
                    $all = array_map('sp_strip_db_chars', $all);
                } else {
                    foreach ($all as $rowIndex => $row) {
                        $all[$rowIndex] = array_map('sp_strip_db_chars', $row);
                    }
                }
            }
        } catch (Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return null;
        }
        if ($fetchType === EntityManager::ROW_FETCH_TYPE) {
            if (count($all) > 1) {
                throw new Exception('More than one result returned from the query');
            } elseif (isset($all[0])) {
                $all = $all[0];
            }
        }
        return $all;
    }

    /**
     * @param $query
     * @param array|null $params
     * @return false|int
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function statement($query, ?array $params = []): bool|int {
        try {
            foreach ($params ?? [] as $keyParam => $param) {
                $params[$keyParam] = sp_convert_parameter_to_sql_value($param);
            }
            return $this->getManager()->getConnection()->prepare($query)->executeStatement($params);
        } catch (Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @param $table
     * @param $fields
     * @param bool $returnInsertedId
     * @return false|int
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert($table, $fields, bool $returnInsertedId = false): bool|int {
        try {
            foreach ($fields as $field => $fieldValue) {
                $fields[$field] = sp_convert_parameter_to_sql_value($fieldValue);
            }
            $result = $this->getConnection()->insert($table, $fields);
            return $returnInsertedId ? $this->getConnection()->lastInsertId() : $result;
        } catch (\Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @param $table
     * @param $fields
     * @param $id
     * @return false|int
     * @throws \Doctrine\DBAL\Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function update($table, $fields, $id): bool|int {
        try {
            $updateStatement = "update $table set ";
            foreach ($fields as $field => $value) {
                if ($field !== array_key_first($fields)) {
                    $updateStatement .= ", ";
                }
                $updateStatement .= "$field = :$field";
            }
            $updateStatement .= " where id = $id limit 1;";
            return $this->statement($updateStatement, $fields);
        } catch (\Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @param string $table
     * @param array $searchFields
     * @param array $fields
     * @return false|int
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function replace(string $table, array $searchFields, array $fields): bool|int {
        try {
            if (count($searchFields) === 0) {
                return false;
            }

            $searchFieldsAndValues = [];
            foreach ($fields as $field => $fieldValue) {
                $fields[$field] = sp_convert_parameter_to_sql_value($fieldValue);
                if (in_array($field, $searchFields, true)) {
                    $searchFieldsAndValues[$field] = $fields[$field];
                }
            }
            $id = $this->exists($table, $searchFieldsAndValues);
            if ($id === false) {
                return $this->insert($table, $fields);
            }
            return $this->update($table, $fields, $id);
        } catch (\Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @param string $table
     * @param array $searchFields
     * @param bool $returnId
     * @return false|mixed|null
     * @throws Exception
     */
    public function exists(string $table, array $searchFields, bool $returnId = true): mixed {
        if (count($searchFields) === 0) {
            return false;
        }
        $existsQuery = "select " . ($returnId ? 'id' : 'count(*) as `count`') . " from $table where ";
        $parameters = [];
        foreach ($searchFields as $fieldToSearch => $fieldValue) {
            if ($fieldToSearch !== array_key_first($searchFields)) {
                $existsQuery .= " and ";
            }
            $existsQuery .= "$fieldToSearch = :$fieldToSearch";
            $parameters[$fieldToSearch] = $fieldValue;
        }
        return $this->querySingle($existsQuery, $parameters);

    }

    /**
     * @param $variable
     * @return false|mixed|null
     * @throws Exception
     */
    public function getVariable($variable): mixed {
        try {
            return $this->querySingle("select @@GLOBAL.$variable;");
        } catch (\Exception $exception) {
            if (sp_is_dev() || sp_is_docker()) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @param $variable
     * @param $value
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function setVariable($variable, $value): void {
        if (!is_numeric($value)) {
            $value = "'$value'";
        }
        $this->statement("SET SESSION $variable=$value;");
    }

    /**
     * @param $field
     * @param bool $excludeEntityLog
     * @param array $additionalExcludeTables
     * @return array|null
     * @throws Exception
     */
    public function getAllTablesWithField($field, bool $excludeEntityLog = true, array $additionalExcludeTables = []): ?array {
        if (empty($field)) {
            return null;
        }

        $database = $this->getManager()->getConnection()->getDatabase();
        $tableQuery = "SELECT TABLE_NAME FROM information_schema.columns c WHERE c.TABLE_SCHEMA = '{$database}' AND c.COLUMN_NAME = '{$field}' " . (($excludeEntityLog) ? "AND c.TABLE_NAME <> 'entity_log'" : "");
        if (count($additionalExcludeTables) > 0) {
            $tableQuery .= " and c.TABLE_NAME not in (" . sp_value_implode_values($additionalExcludeTables, false, true, true) . ")";
        }
        return $this->query($tableQuery, null, 'TABLE_NAME');

    }
}
