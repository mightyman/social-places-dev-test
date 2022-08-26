<?php

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

/**
 * @param QueryBuilder $qb
 * @param $field
 * @return bool
 */
function sp_dql_has_select(QueryBuilder $qb, $field): bool {
    $parts = $qb->getDQLPart('select');
    $alreadyAdded = false;
    /** @var Expr\Select $part */
    foreach ($parts as $part) {
        if (in_array($field, $part->getParts(), false)) {
            $alreadyAdded = true;
            break;
        }
    }
    return $alreadyAdded;
}

/**
 * @param QueryBuilder $qb
 * @param $field
 * @return bool
 */
function sp_dql_has_join(QueryBuilder $qb, $field): bool {
    $joinsList = $qb->getDQLPart('join');
    $alreadyAdded = false;
    foreach ($joinsList as $joins) {
        /** @var Expr\Join $join */
        foreach ($joins as $join) {
            if ($field === $join->getAlias()) {
                $alreadyAdded = true;
                break 2;
            }
        }
    }
    return $alreadyAdded;
}

/**
 * @param $qb
 * @return array
 */
function sp_dql_flatten_parameters($qb): array {
    $flattened = [];
    $parameters = $qb->getParameters()->toArray();
    foreach ($parameters as $key => $value) {
        $param = $key;
        if ($value instanceof Parameter) {
            $param = $value->getName();
            $value = $value->getValue();
        }
        $flattened[$param] = $value;
    }
    return $flattened;
}

/**
 * @param $old
 * @param $new
 * @return mixed
 */
function sp_dql_apply_other_parameters($old, $new) {
    $parameters = $old->getParameters()->toArray();
    foreach ($parameters as $key => $value) {
        $param = $key;
        if ($value instanceof Parameter) {
            $param = $value->getName();
            $value = $value->getValue();
        }
        $new->setParameter($param, $value);
    }
    return $new;
}

/**
 * @param Query $query
 * @return bool
 */
function sp_dql_has_distinct(Query $query): bool {
    return stripos($query->getDQL(), 'select distinct') !== false;
}
