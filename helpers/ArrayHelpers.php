<?php

use App\Exceptions\SqlInjectionException;

function sp_array_delete_column(array &$array, string|int $key): bool {
    return array_walk($array, static function (&$v) use ($key) {
        unset($v[$key]);
    });
}

function sp_array_column_to_index(array $array, string|int $column): array {
    $array = array_combine(array_column($array, $column), $array);
    sp_array_delete_column($array, $column);
    return $array;
}

function sp_array_find_index(array $array, $comparator, $strict = true): int|string|null {
    if (!$comparator instanceof Closure) {
        $index = array_search($comparator, $array, $strict);
        if ($index === false) {
            return null;
        }
        return $index;
    }
    foreach ($array as $key => $item) {
        $whatIamLookingFor = $comparator($item, $key);
        if ($whatIamLookingFor === true) {
            return $key;
        }
    }
    return null;
}

function sp_array_find(array $array, $comparator, $strict = true) {
    $key = sp_array_find_index($array, $comparator, $strict);
    return $key !== null ? $array[$key] : $key;
}

function sp_array_is_numeric(array $values = [], bool $skipEmpty = false) {
    if ($skipEmpty && empty($values)) {
        return true;
    }
    $allNumeric = $values == array_filter($values, 'is_numeric');
    if (!$allNumeric) {
        throw new SqlInjectionException();
    }
    return true;
}

function sp_value_implode_values(
    array $values = [],
    bool $checkIfNumeric = true,
    bool $convertToBlankString = true,
    bool $wrapStrings = false,
    bool $preserveZero = false
): string {
    if ($preserveZero) {
        $values = array_filter($values, 'is_numeric');
    } else {
        $values = array_filter($values);
    }
    if ($checkIfNumeric) {
        sp_array_is_numeric($values, true);
    }

    if (!$checkIfNumeric && $wrapStrings) {
        array_walk($values, static function (&$value) {
            $value = "'" . addslashes($value) . "'";
        });
    }
    $returnValue = implode(',', $values);

    if ($convertToBlankString && ((!$preserveZero && empty($returnValue)) || ($preserveZero && $returnValue === ""))) {
        $returnValue = "''";
    }
    return $returnValue;
}

function sp_convert_assoc_arr_to_id_name_arr(array $assocArr, $hasExtras = false): array {
    $data = [];
    if (!$hasExtras) {
        foreach ($assocArr as $id => $name) {
            $data[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
    } else {
        foreach ($assocArr as $id => $extras) {
            $data[] = array_merge([
                'id' => $id,
            ], $extras);
        }
    }
    return $data;
}
