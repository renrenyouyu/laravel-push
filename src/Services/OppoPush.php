<?php

namespace Renrenyouyu\LaravelPush\Services;

use Renrenyouyu\LaravelPush\Exceptions\APIRequestException;
use Renrenyouyu\LaravelPush\Exceptions\PushException;

class OppoPush extends BasePush
{
    private $_time;

    var $masterSecret;

    /**
     * 获取token地址
     *
     * @var string
     */
    var $_authUrl = "https://api.push.oppomobile.com/server/v1/auth";

    /**
     * 发送推送地址
     *
     * @var string
     */
    var $_sendUrl = "https://api.push.oppomobile.com/server/v1/";

    var $_authCacheKey = "oppo_authtoken";

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
     * 发送oppo推送消息。
     *
     * @param string $title 标题
     * @param string $content 内容
     * @param string $type 发送类型 1:all 2:regId 3:alias 4:topic 5:tag
     * @param array $id 目标id regId/alias/tag
     * @param array $extrasData 扩展数据
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage ($title, $content, $type, $id = null, $extrasData = null)
    {
        $accessToken = $this->getAccessToken(1, true);

        $notification['title'] = $title;
        $notification['content'] = $content;
        $notification['action_parameters'] = json_encode($extrasData);

        $notification['click_action_type'] = 1;
        $notification['click_action_activity'] = $this->intentUri;

        //先发送消息体内容
        $save_message_content_url = $this->_sendUrl . 'message/notification/save_message_content';
        $save_message_content_result = $this->post($save_message_content_url, $notification, array_merge($this->_httpHeaderContentType, ['auth_token' => $accessToken]));

        if (!$save_message_content_result) {
            throw new APIRequestException(400, 'oppoPush save_message_content_result empty');
        }

        if ($save_message_content_result['code'] != 0) {
            throw new APIRequestException($save_message_content_result['code'], $save_message_content_result['message']);
        }

        $message_id = $save_message_content_result['data']['message_id'];

        $message = [];
        $message['message_id'] = $message_id;
        //发送目标
        switch ($type) {
            case 1:
                $message['target_type'] = 1;
                break;
            case 2:
            case 3:
                $message['target_type'] = 2;
                if (is_array($id)) {
                    if (count($id) > 1000) {
                        throw new PushException(400, 'oppoPush regId or alias max count 1000');
                    }
                    $id = implode(';', $id);
                }
                $message['target_value'] = $id;
                break;
            case 5:
                $message['target_type'] = 6;
                $message['target_value'] = $id;
                break;
            default:
                throw new PushException(400, 'oppoPush no exist type=' . $type);
        }

        $endUrl = $this->_sendUrl . 'message/notification/broadcast';

        $result = $this->post($endUrl, $message, array_merge($this->_httpHeaderContentType, ['auth_token' => $accessToken]));
        return $result;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Renrenyouyu\LaravelPush\Services\BasePush::getAuthData()
     */
    protected function getAuthData ()
    {
        if (!$this->appKey || !$this->masterSecret) {
            throw new PushException(500, 'appKey or masterSecret empty');
        }
        $this->_time = $this->getTime();
        $sign = hash('sha256', $this->appKey . $this->_time . $this->masterSecret);

        $data['app_key'] = $this->appKey;
        $data['timestamp'] = $this->_time;
        $data['sign'] = $sign;

        return $data;
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

        if (!isset($data['data']['auth_token'])) {
            return false;
        }
        return $data['data']['auth_token'];
    }
}
