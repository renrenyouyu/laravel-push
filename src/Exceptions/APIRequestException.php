<?php
namespace Renrenyouyu\LaravelPush\Exceptions;

class APIRequestException extends PushException {
    private $http_code;
    private $headers;

    private static $expected_keys = array('code', 'message');

    function __construct($code, $message = ''){
        $this->code = $code;
        $this->message = $message;
    }

    public function __toString() {
        return "\n" . __CLASS__ . " -- [{$this->code}]: {$this->message} \n";
    }

    public function getHttpCode() {
        return $this->http_code;
    }
    public function getHeaders() {
        return $this->headers;
    }

}
