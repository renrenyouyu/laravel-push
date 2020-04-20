<?php

namespace Renrenyouyu\LaravelPush;

use \Illuminate\Redis\RedisManager as Redis;
use Renrenyouyu\LaravelPush\Contracts\DoNewsPusher;
use Renrenyouyu\LaravelPush\Exceptions\PushException;
use Renrenyouyu\LaravelPush\Support\Config;

class Push implements DoNewsPusher
{

    protected $config;
    protected $redis;
    protected $platform;

    public function __construct(array $config)
    {
        $this->config =  new Config($config);
        $this->platform = $this->config->get('platform');
        $this->redis = new Redis(app(), $this->config->get('redis.client'), $this->config->get('redis'));
    }

    private function getService($platform)
    {
        switch ($platform) {
            case 'apple':
                $service = "ApnsPush";
                break;
            case 'mi':
                $service = "MiPush";
                break;
            case 'huawei':
                $service = "HmsPush";
                break;
            case 'umeng':
                $service = "UmengPush";
                break;
            case 'vivo':
                $service = "VivoPush";
                break;
            case 'meizu':
                $service = "MeizuPush";
                break;
            default:
                throw new PushException("platform 参数错误", 405);
                return null;
                break;
        }

        return "Renrenyouyu\\LaravelPush\\Services\\" . $service;
    }

    /**
     * 统一推送接口。
     *
     * @param
     *            $deviceToken
     * @param
     *            $title
     * @param
     *            $message
     * @param string $platform
     *            平台名称apple mi huawei umeng vivo meizu
     * @param string $after_open
     *            go_custom/go_app/go_url
     * @return mixed
     */
    public function send($deviceToken, $title, $message, $platform, $type, $after_open, $customize)
    {
        $platform=strtolower($platform);
        // 得到相关的类名称
        $service = $this->getService($platform);
        if(empty($service)){
            return false;
        }
        $config = $this->config->get("platform.$platform");
        // 获得redis配置，有些不需要
        $config["redis"] = $this->config->get('redis.default');
        /**
         * @var \Renrenyouyu\LaravelPush\Services\BasePush $push
         */
        $push = new $service($config);
        $push->setPkgName($this->config["pkgname"]);
        if (! is_array($after_open)) {
            // 也许存在多个参数
            $after_open = [
                $after_open,
            ];
        }
        return $push->sendMessage($deviceToken, $title, $message, $type, $after_open, $customize);
    }

    /**
     * 根据用户ID设置用户token
     * @param $platform
     * @param $app_id
     * @param $user_id
     * @param $deviceToken
     * @return bool
     */
    public function setToken($platform, $app_id, $user_id, $deviceToken)
    {
        if (!$app_id || !$user_id || !$deviceToken || !$platform) {
            return false;
        }
        $this->redis->set($app_id . ":" . $user_id . ":regid:", $platform .":". $deviceToken);
        return true;
    }


    /**
     * 根据用户ID获取用户token
     */
    public function getToken($app_id, $user_id)
    {
        return $this->redis->get($app_id . ":" . $user_id . ":regid:");
    }

    /**
     * 根据用户ID设置用户token
     */
    public function setDeviceToken($app_id, $list_name, $platform, $deviceToken)
    {
        return $this->redis->lpush($app_id.$list_name, $platform.':'.$deviceToken);
    }

    /**
     * 根据用户ID设置用户token
     */
    public function getDeviceToken($app_id, $list_name, $page = 1, $pageSize = 100)
    {
        return $this->redis->lrange($app_id.$list_name, ($page-1)*$pageSize ,$pageSize);
    }

    //返回列表长度
    public function getListLen($app_id, $list_name)
    {
        return $this->redis->llen($app_id.$list_name);
    }

}
