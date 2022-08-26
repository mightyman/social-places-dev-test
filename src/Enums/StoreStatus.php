<?php

namespace App\Enums;

use App\Traits\Enums\HasEnumTrait;

enum StoreStatus: int
{
    use HasEnumTrait;

    case OPEN = 0;
    case PERMANENTLY_CLOSED = 1;
    case ONBOARDING = 2;
    case REQUIRES_ADMIN_INTERVENTION = 3;
}
