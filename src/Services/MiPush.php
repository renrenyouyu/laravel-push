<?php

namespace Renrenyouyu\LaravelPush\Services;

use Renrenyouyu\LaravelPush\Exceptions\APIRequestException;
use Renrenyouyu\LaravelPush\Exceptions\PushException;
use Renrenyouyu\LaravelPush\Sdk\Xiaomi\Builder;
use Renrenyouyu\LaravelPush\Sdk\Xiaomi\Constants;
use Renrenyouyu\LaravelPush\Sdk\Xiaomi\Region;
use Renrenyouyu\LaravelPush\Sdk\Xiaomi\Sender;

class MiPush extends BasePush
{

    /**
     * MiPush constructor.
     *
     * @param null $config
     * @throws \Exception
     */
    public function __construct ($config = null)
    {
        parent::__construct($config);
    }

    /**
     * 发送推送通知。
     *
     * @param string $title 标题
     * @param string $content 内容
     * @param string $type 发送类型 1:all 2:regId 3:alias 4:topic 5:tag
     * @param array $id 目标id regId/alias/tag
     * @param array $extrasData 扩展数据
     * @return mixed
     * @throws PushException
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::sendMessage()
     */
    public function sendMessage ($title, $content, $type, $id = null, $extrasData = null)
    {
        Constants::setPackage($this->pkgName);
        Constants::setSecret($this->appSecret);

        $sender = new Sender();
        $sender->setRegion(Region::China);// 支持海外

        // message自定义的点击行为
        $message = new Builder();
        $message->title($title);  // 通知栏的title
        $message->description($content); // 通知栏的descption
        $message->passThrough(0);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $message->payload(json_encode($extrasData['extras'] ?? $extrasData)); // 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $message->extra(Builder::notifyForeground, 1); // 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0
        $message->notifyId(2); // 通知类型。最多支持0-4 5个取值范围，同样的类型的通知会互相覆盖，不同类型可以在通知栏并存`
        $message->build();

        //发送目标
        switch ($type){
            case 1:
                $sender = $sender->broadcastAll($message);
                break;
            case 2:
                $sender = $sender->sendToIds($message, $id);
                break;
            case 3:
                $sender = $sender->sendToAliases($message, $id);
                break;
            case 4:
                $sender = $sender->broadcast($message, $id);
                break;
            default:
                throw new PushException(400, 'miPush no exist type='. $type);
        }

        $result = $sender->getRaw();
        if (!isset($result["code"]) || $result["code"] != 0) {
            throw new APIRequestException($result["code"], json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }
}
