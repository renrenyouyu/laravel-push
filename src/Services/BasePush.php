<?php

namespace Renrenyouyu\LaravelPush\Services;

use Redis;
use Renrenyouyu\LaravelPush\Contracts\PushInterface;
use Renrenyouyu\LaravelPush\Traits\HasHttpRequest;

class BasePush implements PushInterface
{
    use HasHttpRequest;

    /**
     * 包名
     *
     * @var string
     */
    var $pkgName = "";

    /**
     * redis控制类
     *
     * @var \Redis
     */
    var $_redis;

    var $appId;

    var $appKey;

    var $appSecret;

    /**
     * 自定义跳转的active
     *
     * @var string
     */
    var $intentUri;

    /**
     * 授权获得accesstoken的地址
     *
     * @var string
     */
    var $_authUrl;

    /**
     * token缓存key
     *
     * @var string
     */
    var $_authCacheKey = "push";

    /**
     * 发送消息的地址
     *
     * @var string
     */
    var $_sendUrl;

    protected $_redisConfig = [
        'database' => 0,
        'duration' => 3600,
        'groups' => [],
        'password' => false,
        'persistent' => true,
        'port' => 6379,
        'prefix' => 'push:',
        'probability' => 100,
        'host' => '127.0.0.1',
        'timeout' => 0,
        'unix_socket' => false
    ];

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct ($config = null)
    {
        foreach ($config as $k => $v) {
            if ($k == "redis") {
                $this->_redisConfig = array_merge($this->_redisConfig, $v);
            } else {
                $this->{$k} = $v;
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Renrenyouyu\LaravelPush\Contracts\PushInterface::sendMessage()
     */
    public function sendMessage ($title, $content, $type, $id = null, $extrasData = null)
    {
        // TODO Auto-generated method stub
    }

    var $_httpHeaderContentType = array('Content-Type: application/x-www-form-urlencoded');

    var $_httpType = 'form';

    /**
     * 请求新的 Access Token。
     *
     * @param int $tryCount
     *            可重试次数
     * @param bool $refresh
     * @return string
     * @throws \Exception
     */
    public function getAccessToken ($tryCount = 1, $refresh = false)
    {
        $key = $this->getCacheKey($this->_authCacheKey);
        $accessToken = $this->_redis->get($key);
        if (!$accessToken || $refresh) {
            $data = $this->getAuthData();
            // 有很大几率会调用失败
            if($this->_httpType=='json'){
                $result = $this->postJson($this->_authUrl, $data, $this->_httpHeaderContentType);
            }else{
                $result = $this->post($this->_authUrl, $data, $this->_httpHeaderContentType);
            }

            $accessToken = $this->getResponseToken($result);
            if (empty($accessToken)) {
                // 获取token失效
                if ($tryCount < 1) {
                    throw new \Exception("获取token失败");
                }
                // 过一会儿重试
                sleep(1);
                return $this->getAccessToken($tryCount - 1, $refresh);
            }
            // 设置的缓存小于实际100秒，有利于掌控有效期,默认缓存半小时,每天获取的机会还是很多的
            $this->_redis->setex($key, 1800, $accessToken);
        }
        return $accessToken;
    }

    /**
     * 获得鉴权请求post参数
     *
     * @return array
     */
    protected function getAuthData ()
    {
        return [];
    }

    /**
     * 获取鉴权之后的token
     *
     * @param [] $data
     * @return boolean
     */
    protected function getResponseToken ($data)
    {
        return false;
    }

    /**
     * 连接redis缓存
     *
     * @return boolean
     */
    protected function getRedisConnection ()
    {
        try {
            $this->_redis = new Redis();
            if (!empty($this->_redisConfig['host'])) {
                $this->_redisConfig['server'] = $this->_redisConfig['host'];
            }
            if (!empty($this->_redisConfig['unix_socket'])) {
                $return = $this->_redis->connect($this->_redisConfig['unix_socket']);
            } elseif (empty($this->_redisConfig['persistent'])) {
                $return = $this->_redis->connect($this->_redisConfig['server'], $this->_redisConfig['port'], $this->_redisConfig['timeout']);
            } else {
                $persistentId = $this->_redisConfig['port'] . $this->_redisConfig['timeout'] . $this->_redisConfig['database'];
                $return = $this->_redis->pconnect($this->_redisConfig['host'], $this->_redisConfig['port'], $this->_redisConfig['timeout'], $persistentId);
            }
        } catch (\Exception $e) {
            return false;
        }
        if ($return && $this->_redisConfig['password']) {
            $return = $this->_redis->auth($this->_redisConfig['password']);
        }
        if ($return) {
            $return = $this->_redis->select($this->_redisConfig['database']);
        }
        return $return;
    }

    /**
     * 重构缓存key
     *
     * @param string $key
     * @return string
     */
    public function getCacheKey ($key)
    {
        if (!empty($this->_redisConfig["prefix"])) {
            return $this->_redisConfig["prefix"]. $key;
        }
        return $key;
    }

    public function setPkgName ($name)
    {
        $this->pkgName = $name;
    }

    public function getPkgName ()
    {
        return $this->pkgName;
    }

    /**
     * 获取毫秒时间戳
     *
     * @return number
     */
    protected function getTime ()
    {
        list ($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 获取requestId
     *
     * @return number
     */
    protected function getRequestId ()
    {
        return (int) ($this->getTime(). rand(1000, 9999));
    }
    /**
     * @param $url
     * @param array $params
     *
     * @return string
     */
    protected function getEndpointUrl ($url, $params)
    {
        return $url . '?' . http_build_query($params);
    }

    /**
     * 输出json
     * @param $value
     * @param bool $is_die
     * @return bool|string
     */
    protected function print_json ($value, $is_die = true)
    {
        if(is_array($value)){
            print_r(json_encode($value, JSON_UNESCAPED_UNICODE));
        }else{
            print_r($value);
        }

        if ($is_die) die();
    }
}

