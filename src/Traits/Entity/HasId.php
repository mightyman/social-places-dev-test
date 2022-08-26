<?php

namespace App\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;

trait HasId
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    public function getId(): ?int {
        try {
            return $this->id;
        } catch (\Error $e) { // Typically get an \Error stating unable to access property until entity is instantiated however returning null is a check used to determine existence
            return null;
        }
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }
}
