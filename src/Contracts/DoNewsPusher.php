<?php

namespace Renrenyouyu\LaravelPush\Contracts;

interface DoNewsPusher
{
    public function send($deviceToken, $title, $message, $platform, $type, $id, $customize);

    public function setToken ($platform, $app_id, $user_id, $deviceToken);

    public function getToken ($app_id, $user_id);

    public function setDeviceToken ($app_id, $list_name, $device_id, $deviceToken);

    public function getDeviceToken ($app_id, $list_name, $page = 1, $pageSize = 100);

    public function getListLen ($app_id, $list_name);

}
