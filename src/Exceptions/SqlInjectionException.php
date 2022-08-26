<?php

namespace App\Exceptions;

class SqlInjectionException extends \Exception
{
    /**
     * @inheritDoc
     */
    public function __construct($message = "Sql injection detected", $code = 500, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

