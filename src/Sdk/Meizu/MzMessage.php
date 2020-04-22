<?php
namespace Renrenyouyu\LaravelPush\Sdk\Meizu;

/**
 * 打包
 *
 * User: even
 * Date: 2016/8/25
 * Time: 18:05
 */
class MzMessage {
    public function pack(&$message) {
        foreach ($message as $key => &$value) {
            if (is_array($value)) {
                $this->pack($value);
            } else {
                if (is_null($value)) {
                    unset($message[$key]);
                }

            }
        }
        unset($value);
        return $message;
    }

    public function toJson(&$message) {
        return json_encode($this->pack($message), JSON_UNESCAPED_UNICODE);
    }

}
