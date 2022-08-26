<?php

namespace App\Entity;

use App\Traits\Entity\HasId;
use App\Traits\Entity\HasName;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Brand
{
    use HasId;
    use HasName;
}
