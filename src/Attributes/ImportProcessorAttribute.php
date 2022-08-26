<?php

namespace App\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ImportProcessorAttribute
{
    private ?string $service;
    private string $function;

    public function __construct(string $function, ?string $service = null) {
        $this->service = $service;
        $this->function = $function;
    }

    public function getService(): ?string {
        return $this->service;
    }

    public function getFunction(): string {
        return $this->function;
    }
}
