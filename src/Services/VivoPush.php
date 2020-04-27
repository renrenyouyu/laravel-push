<?php

namespace Renrenyouyu\LaravelPush\Services;

use Renrenyouyu\LaravelPush\Exceptions\APIRequestException;
use Renrenyouyu\LaravelPush\Exceptions\PushException;

class VivoPush extends BasePush
{
    private $_time;

    /**
     * 获取token地址
     *
     * @var string
     */
    var $_authUrl = "https://api-push.vivo.com.cn/message/auth";

    var $_httpHeaderContentType = array('Content-Type: application/json');
    var $_httpType = 'json';

    /**
     * 发送推送地址
     *
     * @var string
     */
    var $_sendUrl = "https://api-push.vivo.com.cn/message/send";

    /**
     * 广播保存推送消息地址
     *
     * @var string
     */
    var $_sendSaveMessageUrl = "https://api-push.vivo.com.cn/message/saveListPayload";

    /**
     * 批量推送消息地址
     *
     * @var string
     */
    var $_batchSendUrl = "https://api-push.vivo.com.cn/message/pushToList";

    var $_authCacheKey = "vivo_authtoken";

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
     * 发送vivo推送消息。
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
        if(!$id){
            throw new PushException(400, 'vivoPush id is empty');
        }elseif(!is_array($id)){
            $id = [$id];
            if (count($id) > 1000) {
                throw new PushException(400, 'vivoPush regId or alias max count 1000');
            }
        }

        $accessToken = $this->getAccessToken();

        $data = $batchData = [];

        $this->intentUri = $this->intentUri. json_encode($extrasData['extras']);

        $data['notifyType'] = '2';
        $data['title'] = $title;
        $data['content'] = $content;
        $data['skipType'] = '4';
        //自定义跳转
        $data['skipContent'] = $this->intentUri;
        $data['requestId'] = $this->getRequestId();

        if(count($id)>1){
            //广播先发消息
            $saveResult = $this->postJson($this->_sendSaveMessageUrl, $data, array_merge($this->_httpHeaderContentType, ['authToken' => $accessToken]));
            if (!$saveResult) {
                throw new APIRequestException(400, 'vivoPush save_message_content_result empty');
            }

            if (!isset($saveResult['result']) || $saveResult['result'] != 0) {
                throw new APIRequestException($saveResult['code'], $saveResult['message']);
            }

            $batchData['taskId'] = $saveResult['taskId'];
        }

        //发送目标 因为所有广播有数量限制 所以只使用单播和批量广播 不使用所有广播
        switch ($type) {
            case 2:
                if(count($id)==1) {
                    $data['regId'] = $id[0];
                }else{
                    $batchData['regIds'] = $id;
                    $batchData['requestId'] = $this->getRequestId();
                }
                break;
            case 3:
                if(count($id)==1) {
                    $data['alias'] = $id[0];
                }else{
                    $batchData['aliases'] = $id;
                    $batchData['requestId'] = $this->getRequestId();
                }
                break;
            default:
                throw new PushException(400, 'vivoPush no exist type=' . $type);
        }

        if(count($id)>1){
            $sendUrl = $this->_batchSendUrl;
            $message = $batchData;
        }else{
            $sendUrl = $this->_sendUrl;
            $message = $data;
        }

        $result = $this->postJson($sendUrl, $message, array_merge($this->_httpHeaderContentType, ['authToken' => $accessToken]));
        return $result;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Renrenyouyu\LaravelPush\Services\BasePush::getAuthData()
     */
    protected function getAuthData ()
    {
        if (!$this->appId || !$this->appKey || !$this->appSecret) {
            throw new PushException(500, 'appId or appKey or appSecret empty');
        }
        $this->_time = $this->getTime();
        $sign = md5($this->appId . $this->appKey . $this->_time . $this->appSecret);

        return [
            "appId" => $this->appId,
            "appKey" => $this->appKey,
            "timestamp" => $this->_time,
            "sign" => $sign,
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

        if (!isset($data['authToken'])) {
            return false;
        }
        return $data['authToken'];
    }
}
