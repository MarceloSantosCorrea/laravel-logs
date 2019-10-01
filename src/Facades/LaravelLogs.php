<?php

namespace MarceloCorrea\src\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelLogs extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'logs';
    }
}
