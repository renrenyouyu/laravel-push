<?php

namespace Renrenyouyu\LaravelPush\Services;

use Renrenyouyu\LaravelPush\Exceptions\APIRequestException;
use Renrenyouyu\LaravelPush\Exceptions\PushException;

class HmsPush extends BasePush
{

    /**
     * 获取token地址
     *
     * @var string
     */
    var $_authUrl = "https://oauth-login.cloud.huawei.com/oauth2/v2/token";

    /**
     * 发送推送地址
     *
     * @var string
     */
    var $_sendUrl = "https://push-api.cloud.huawei.com/v1/%s/messages:send";

    var $_authCacheKey = "huawei_authtoken";

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct ($config = null)
    {
        parent::__construct($config);
        // 连接redis服务器，用来存储accessToken
        $this->getRedisConnection();
    }

    /**
     * 发送华为推送消息 暂时只支持批量token或topic发送
     *
     * {@inheritdoc}
     *
     * @param string $title 标题
     * @param string $content 内容
     * @param string $type 发送类型 1:all 2:regId 3:alias 4:topic 5:tag
     * @param array $id 目标id regId/alias/tag
     * @param array $extrasData 扩展数据
     * @throws \Exception
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::sendMessage()
     */
    public function sendMessage ($title, $content, $type, $id = null, $extrasData = null)
    {
        // 获取token
        $accessToken = $this->getAccessToken();

        //发送数据
        switch ($type){
            case 2:
                if (!is_array($id)) {
                    $id = [$id];
                }
                $data['token'] = $id;
                break;
            case 4:
                if (is_array($id)) {
                    $id = $id[0];
                }
                $data['topic'] = $id;
                break;
            default:
                throw new PushException(400, 'hmsPush no exist type='. $type);
        }
        $data['notification'] = [
            'title' => $title,
            'body' => $content,
            //'image'=> '',
        ];
        $data['android'] = [
            'collapse_key' => -1,
            'data' => '',
            'notification' => [
                'title' => $title,
                'body' => $content,
                //'style'=> 0,
                //'image'=> '',
                'click_action' => [
                    'type' => 1,
                    'action' => $this->intentUri,
                ]
            ]
        ];

        // 发送消息通知
        $response = $this->postJson(
            $this->getSendUrl(),
            ['message' => $data],
            array_merge($this->_httpHeaderContentType, ['Authorization' => 'Bearer ' . $accessToken])
        );

        if (!isset($response["code"]) || $response["code"] != '80000000') {
            throw new APIRequestException($response["code"], 'msg='. $response["msg"]. ' result=' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        //统一成功code为0
        $response["code"] = $response["code"] == '80000000' ? 0 : $response["code"];

        return $response;
    }

    public function getSendUrl ()
    {
        return sprintf($this->_sendUrl, $this->appId);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Renrenyouyu\LaravelPush\Services\BasePush::getAuthData()
     */
    protected function getAuthData ()
    {
        if (!$this->appId || !$this->appSecret) {
            throw new PushException(500, 'appId or appSecret empty');
        }

        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret
        ];
    }

    /**
     * 获取鉴权之后的token
     * {@inheritDoc}
     * @see \Renrenyouyu\LaravelPush\Services\BasePush::getResponseToken()
     */
    protected function getResponseToken ($data)
    {
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        if (!isset($data['access_token'])) {
            return false;
        }
        return $data['access_token'];
    }
}
