<?php

namespace App\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait HasName
{

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\NotNull]
    private ?string $name;

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function getName(): ?string {
        return $this->name;
    }
}
