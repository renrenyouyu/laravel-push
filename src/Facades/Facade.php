<?php

namespace Renrenyouyu\LaravelPush\Facades;;


class Facade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'push';
    }
}
