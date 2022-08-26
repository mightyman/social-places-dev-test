<?php

namespace App\Classes;

use JetBrains\PhpStorm\ArrayShape;

class Column
{
    private string $text;
    private string $align = 'center';
    private bool $sortable = false;
    private string $value = '';
    private string $customClass = '';
    private ?string $default = null;
    private ?array $columns = null;
    private ?\Closure $custom = null;
    private ?string $tooltip = null;
    private ?string $width = null;
    private int $order = 0;


    public function __construct(
        $text,
        ?string $align = null,
        ?bool $sortable = null,
        ?string $value = null,
        ?string $customClass = null,
        ?string $width = null
    ) {
        $this->text = $text ?? $this->text;
        $this->align = $align ?? $this->align;
        $this->sortable = $sortable ?? $this->sortable;
        $this->value = $value ?? $this->value;
        $this->customClass = $customClass ?? $this->customClass;
        $this->width = $width ?? $this->width;
    }

    public static function createColumn(
        $text,
        ?string $align = null,
        ?bool $sortable = null,
        ?string $value = null,
        ?string $customClass = null,
        ?string $width = null
    ) {
        return new Column($text,
            $align,
            $sortable,
            $value,
            $customClass,
            $width);
    }

    public function getText() {
        return $this->text;
    }

    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    public function getAlign(): string {
        return $this->align;
    }

    public function setAlign(string $align): Column {
        $this->align = $align;
        return $this;
    }

    public function isSortable(): bool {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): Column {
        $this->sortable = $sortable;
        return $this;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function setValue(string $value): Column {
        $this->value = $value;
        return $this;
    }

    public function getCustomClass(): string {
        return $this->customClass;
    }

    public function setCustomClass(string $customClass): Column {
        $this->customClass = $customClass;
        return $this;
    }

    public function getDefault(): ?string {
        return $this->default;
    }

    public function setDefault(?string $default): Column {
        $this->default = $default ? strtoupper($default) : $default;
        return $this;
    }

    public function getColumns(): ?array {
        return $this->columns;
    }

    public function setColumns($columns): Column {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->columns = $columns;
        return $this;
    }

    public function getCustom(): ?\Closure {
        return $this->custom;
    }

    public function setCustom(?\Closure $custom): Column {
        $this->custom = $custom;
        return $this;
    }

    public function getTooltip(): ?string {
        return $this->tooltip;
    }

    public function setTooltip(?string $tooltip): Column {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function getWidth(): ?string {
        return $this->width;
    }

    public function setWidth(?string $width): Column {
        $this->width = $width;
        return $this;
    }

    public function getOrder(): int {
        return $this->order;
    }

    public function setOrder(int $order): Column {
        $this->order = $order;
        return $this;
    }

    public function setDefaultASC(): Column {
        $this->default = 'ASC';
        return $this;
    }

    public function setDefaultDESC(): Column {
        $this->default = 'DESC';
        return $this;
    }

    #[ArrayShape(['text' => "string", 'align' => "string", 'sortable' => "bool", 'value' => "string", 'class' => "string", 'tooltip' => "null|string",
        'width' => "null|string"])]
    public function toArray(): array {
        return [
            'text' => $this->text,
            'align' => $this->align,
            'sortable' => $this->sortable,
            'value' => $this->value,
            'class' => $this->customClass,
            'tooltip' => $this->tooltip,
            'width' => $this->width,
        ];
    }

    public static function actionColumn(): Column {
        return self::createColumn('Actions', 'center', false, 'actions');
    }
}
