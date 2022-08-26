<?php

namespace App\Constants;

class ApplicationConstants
{
    public const NO = 0;
    public const YES = 1;
    public const NO_TEXT = 'No';
    public const YES_TEXT = 'Yes';
    public const YES_NO_CHOICES = [
        self::NO => self::NO_TEXT,
        self::YES => self::YES_TEXT
    ];
}
