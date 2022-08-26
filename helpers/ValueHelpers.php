<?php

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @param $value
 * @return mixed|string
 */
function sp_convert_parameter_to_sql_value($value): mixed {
    if ($value instanceof DateTime) {
        $value->setTimezone(new \DateTimeZone('UTC'));
        $value = $value->format('Y-m-d H:i:s');
    }
    return $value;
}

function sp_is_docker(): bool {
    return strtolower($_SERVER['INSTANCE_TYPE']) === 'docker';
}

function sp_is_dev(): bool {
    return strtolower($_SERVER['INSTANCE_TYPE']) === 'dev';
}

function sp_is_test(): bool {
    return strtolower($_SERVER['INSTANCE_TYPE']) === 'test';
}

function sp_is_prod(): bool {
    return strtolower($_SERVER['INSTANCE_TYPE']) === 'live';
}

function sp_convert_filter_to_list($values, bool $convertToInt = true): array {
    if (empty($values)) {
        return [];
    }
    $valueList = [];
    if (!is_array($values)) {
        $values = [$values];
    }
    array_walk($values, static function (&$value) use (&$valueList) {
        if (str_contains($value, ',')) {
            $valueList = array_merge($valueList, explode(',', $value));
        } else {
            $valueList[] = $value;
        }
    });
    if (!$convertToInt) {
        return $valueList;
    }
    return array_map('intval', $valueList);
}

function sp_setter($string, $shouldStrip = true): string {
    $string = ucwords($string);
    $string = $shouldStrip ? str_replace(" ", '', $string) : $string;
    return "set" . $string;
}

function sp_getter($string, $shouldStrip = true): string {
    $string = ucwords($string);
    $string = $shouldStrip ? str_replace(" ", '', $string) : $string;
    return "get" . $string;
}

function sp_extract_errors(ConstraintViolationListInterface $constraintViolationList): array {
    $errors = [];
    /** @var ConstraintViolationInterface $constraintViolation */
    foreach ($constraintViolationList as $constraintViolation) {
        $errors[$constraintViolation->getPropertyPath()][] = $constraintViolation->getMessage();
    }
    return $errors;
}

function sp_extract_errors_as_string(ConstraintViolationListInterface $constraintViolationList): string {
    $values = [];
    foreach (sp_extract_errors($constraintViolationList) as $property => $errors) {
        $values[] = "{$property} => " . implode('; ',$errors);
    }
    return implode("\n", $values);
}
