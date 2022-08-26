<?php

namespace App\Traits\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait HasDateUpdated
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $dateUpdated;

    public function setDateUpdated(?DateTime $date = null): void {
        if ($date === null) {
            $this->dateUpdated = new DateTime('now');
            return;
        }
        $this->dateUpdated = $date;
    }

    public function getDateUpdated(): ?DateTime {
        return $this->dateUpdated;
    }
}
