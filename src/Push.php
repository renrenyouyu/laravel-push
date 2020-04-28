<?php

namespace Renrenyouyu\LaravelPush;

use \Illuminate\Redis\RedisManager as Redis;
use Renrenyouyu\LaravelPush\Contracts\DoNewsPusher;
use Renrenyouyu\LaravelPush\Exceptions\PushException;
use Renrenyouyu\LaravelPush\Support\Config;

class Push implements DoNewsPusher
{

    protected $config;
    protected $platform;

    public function __construct(array $config)
    {
        $this->config =  new Config($config);
        $this->platform = $this->config->get('platform');
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
            case 'oppo':
                $service = "OppoPush";
                break;
            case 'meizu':
                $service = "MeizuPush";
                break;
            default:
                throw new PushException(405, "platform 参数错误");
                break;
        }

        return "Renrenyouyu\\LaravelPush\\Services\\" . $service;
    }

    /**
     * 统一推送接口。
     *
     * @param string $platform 平台名称 apple mi huawei oppo vivo umeng meizu
     * @param string $title 标题
     * @param string $content 内容
     * @param string $type 发送类型 1:all 2:regId 3:alias 4:topic 5:tag
     * @param array $id    目标id regId/alias/tag
     * @param array $extrasData 扩展数据
     * @return mixed
     * @throws PushException
     */
    public function send($platform, $title, $content, $type, $id = null, $extrasData = null)
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
        $push->setPkgName($this->config["pkgName"]);

        return $push->sendMessage($title, $content, $type, $id, $extrasData);
    }


}
