<?php

namespace App\Traits\Enums;

trait HasEnumTrait
{
    public function getName(): string {
        return ucwords(strtolower(str_replace('_', ' ', $this->name)));
    }

    public static function fromName(string $name): self {
        $caseName = strtoupper(str_replace(' ', '_', $name));
        foreach (self::cases() as $case) {
            if ($case->name === $caseName) {
                return $case;
            }
        }
        throw new \RuntimeException('Unable to match case: ' . $name);
    }

    public static function mapping(): array {
        $mapping = [];
        foreach (self::cases() as $case) {
            $mapping[$case->value] = $case->getName();
        }
        return $mapping;
    }

    public static function selectMapping(): array {
        $mapping = [];
        foreach (self::cases() as $case) {
            $mapping[] = [
                'id' => $case->value,
                'name' => $case->getName(),
            ];
        }
        return $mapping;
    }
}
