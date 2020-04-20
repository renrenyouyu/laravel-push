<?php

namespace Renrenyouyu\LaravelPush\Exceptions;

class PushException extends \Exception
{
    function __construct ($code, $message = '')
    {
        parent::__construct($message, $code);
    }
}
