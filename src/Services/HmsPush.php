<?php

namespace Renrenyouyu\LaravelPush\Services;

use Renrenyouyu\LaravelPush\Exceptions\APIRequestException;

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
     * 发送华为推送消息。
     *
     * {@inheritdoc}
     *
     * @throws \Exception
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::sendMessage()
     */
    public function sendMessage ($deviceToken, $title, $message, $type, $after_open, $customize)
    {
        // deviceToken 转为数组
        if(!is_array($deviceToken)){
            $deviceToken = [$deviceToken];
        }
        // 构建 Payload
        $this->customize = $customize;
        // 获取token
        $accessToken = $this->getAccessToken();
        // 发送消息通知
        $response = $this->postJson($this->getSendUrl(), [
            'message' => [
                'token' => $deviceToken,
                'notification'=>[
                    'title'=> $title,
                    'body'=> $message,
                ],
                'android'=>[
                    'collapse_key'=> -1,
                    'data'=> '',
                    'notification'=> [
                        'title'=> $title,
                        'body'=> $message,
                        'style'=> 0,
                        'image'=> '',
                        'click_action'=> [
                            'type'=> 1,
                            'action'=> $this->intentUri,
                            'url'=> ''
                        ]
                    ]
                ]
            ]
        ], array_merge($this->_httpHeaderContentType, ['Authorization'=> 'Bearer '. $accessToken]));

        if (isset($response["code"]) && $response["code"] != "80000000") {
           throw new APIRequestException(400, 'code='. $response["code"]. ',msg='. $response["msg"]);
        }
        return $response;
    }

    public function getSendUrl(){
        return sprintf($this->_sendUrl, $this->appId);
    }

    /**
     * 转换透传和消息
     *
     * {@inheritdoc}
     *
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::getSendType()
     */
    public function getSendType ($type)
    {
        // 1 透传异步消息 3 系统通知栏异
        $msgArr = [
            "message" => 3,
            "quiet" => 1
        ];
        return $msgArr[$type];
    }

    /**
     * 点击之后的打开行为
     * 1 自定义行为：行为由参数intent定义 ,2 打开URL：URL地址由参数url定义, 3 打开APP：默认值，打开App的首页
     *
     * {@inheritdoc}
     *
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::getAfterOpen()
     */
    public function getAfterOpen ($go_after)
    {
        list ($type, $param) = $go_after;
        if ($type == "go_custom") {
            return [
                'type' => 1,
                'param' => [
                    // 'intent' => '#Intent;compo=com.wanmei.a9vg/.common.activitys.Activity;S.W=U;end'
                    'intent' => sprintf('#Intent;compo=%s;S.W=U;end', empty($param) ? $this->intentUri : $param)
                ]
            ];
        } elseif ($type == "go_scheme") {
            return [
                'type' => 1,
                'param' => [
                    //abao://router/huawei
                    'intent' => sprintf("%s?push=%s", $this->intentUri, urlencode(json_encode($this->customize, JSON_UNESCAPED_UNICODE)))
                ]
            ];
        } elseif ($type == "go_url") {
            // Action的type为2的时候表示打开URL地址
            return [
                'type' => 2,
                'param' => [
                    "url" => $param
                ]
            ];
        }
        // 需要拉起的应用包名，必须和注册推送的包名一致。
        return [
            'type' => 3,
            'param' => [
                'appPkgName' => $this->pkgName
            ]
        ];
    }

    /**
     *
     * {@inheritDoc}
     * @see \Renrenyouyu\LaravelPush\Services\BasePush::getAuthData()
     */
    protected function getAuthData ()
    {
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
