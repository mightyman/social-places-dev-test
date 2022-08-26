<?php

namespace App\Attributes;


#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ImportExportAttribute
{
    private ?string $columnName;

    /**
     * @var string|null
     * The function to retrieve information from the entity will default to using get{propertyName} if undefined
     */
    private ?string $getter;

    /**
     * @var string|null
     * The function to retrieve information from the entity will default to using set{propertyName} if undefined
     */
    private ?string $setter;

    private bool $isIdentifierField;

    public function __construct(?string $columnName = null, ?string $getter = null, ?string $setter = null, bool $isIdentifierField = false) {
        $this->columnName = $columnName;
        $this->getter = $getter;
        $this->setter = $setter;
        $this->isIdentifierField = $isIdentifierField;
    }

    public function getColumnName(): ?string {
        return $this->columnName;
    }
    public function getGetter(): ?string {
        return $this->getter;
    }

    public function getSetter(): ?string {
        return $this->setter;
    }

    public function getIsIdentifierField(): bool {
        return $this->isIdentifierField;
    }
}
