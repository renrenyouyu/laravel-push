<?php

namespace Renrenyouyu\LaravelPush\Contracts;

interface PushInterface
{
    /**
     * Notes:
     * @param string $title 标题
     * @param string $content 内容
     * @param string $type 发送类型 1:all 2:regId 3:alias 4:tag
     * @param array $id    目标id regId/alias/tag
     * @param array $extrasData 扩展数据
     * @return mixed
     */
    public function sendMessage($title, $content, $type, $id = null, $extrasData = null);

}
