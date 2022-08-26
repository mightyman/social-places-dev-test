<?php

namespace App\Traits\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait HasDateCreated
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $dateCreated;

    public function setDateCreated(?DateTime $dateCreated): void {
        $this->dateCreated = $dateCreated;
        if (method_exists($this, 'setDateUpdated')) {
            $this->setDateUpdated($dateCreated);
        }
    }

    public function getDateCreated(): ?DateTime {
        return $this->dateCreated;
    }
}
