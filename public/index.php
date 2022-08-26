<?php

use App\Kernel;

foreach (glob(dirname(__DIR__) . '/helpers/*Helpers.php') as $helperFile) {
    require_once($helperFile);
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
