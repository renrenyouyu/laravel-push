<?php

namespace Renrenyouyu\LaravelPush\Facades;

use Illuminate\Support\Facades\Facade;

class Push extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'push';
    }
}
