<?php

namespace Renrenyouyu\LaravelPush\Contracts;

interface PushInterface
{
    public function sendMessage($deviceToken, $title, $message, $type, $id, $customize);

    /**
     * 传送类型：
     * 消息：message/透传：quiet
     *
     * @param unknown $type
     */
    public function getSendType($type);

    /**
     * 点击之后的打开行为
     *
     * @param string|[] $go_after
     *            go_app:打开app首页;
     *            go_custom:app自定义操作;
     *            go_url:打开url;
     *            go_page:打开指定界面,app需要提前定义
     */
    public function getAfterOpen($go_after);

}
