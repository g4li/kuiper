<?php

namespace kuiper\web\security;

use kuiper\web\session\SessionInterface;

class Auth implements AuthInterface
{
    /**
     * 为降低用户在页面使用（ajax 调用）时出现过期情况，加入 regenerate_after 设置
     * regenerate_after 比实际过期时间要提前 300 秒（如果 lifetime/5 小于300s，则使用 lifetime/5）
     * 当用户进入页面时，如果到了 regenerate_after 时间，则需要重新登录.
     */
    const REGENERATE_AFTER = '__gc_time';

    /**
     * session 数据中记录 auth 信息 key 值
     */
    private $sessionKey;

    /**
     * session 数据.
     */
    private $sessionData = false;

    /**
     * session 组件.
     */
    private $session;

    /**
     * 是否需要重新生成 session.
     */
    private $needRegenerate = false;

    public function __construct(SessionInterface $session, $sessionKey = 'auth:id', $lifetime = null)
    {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->lifetime = $lifetime;
    }

    public function initialize()
    {
        $this->sessionData = $this->session->get($this->sessionKey);
        if (isset($this->sessionData)) {
            $now = time();
            $discard_time = isset($this->sessionData[self::REGENERATE_AFTER])
                ? $this->sessionData[self::REGENERATE_AFTER]
                : $now;
            $this->needRegenerate = ($now >= $discard_time);
        } else {
            $this->sessionData = false;
        }

        return $this;
    }

    public function getSessionKey()
    {
        return $this->sessionKey;
    }

    public function offsetExists($offset)
    {
        return isset($this->sessionData[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->sessionData[$offset])) {
            return $this->sessionData[$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->sessionData[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->sessionData[$offset]);
    }

    public function __get($name)
    {
        if (isset($this->sessionData[$name])) {
            return $this->sessionData[$name];
        }
    }

    public function __set($name, $value)
    {
        if (isset($this->sessionData[$name])) {
            $this->sessionData[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->sessionData[$name]);
    }

    public function getIdentity()
    {
        return $this->sessionData;
    }

    /**
     * 用户登录操作.
     *
     * @param mixed $identity 用户数据
     */
    public function login($identity)
    {
        if ($this->needRegenerate) {
            $this->session->regenerateId(true);
        }
        foreach ($identity as $name => $val) {
            $this->sessionData[$name] = $val;
        }
        $lifetime = isset($this->lifetime) ? $this->lifetime : ini_get('session.cookie_lifetime');
        $this->sessionData[self::REGENERATE_AFTER] = time() + $lifetime - min($lifetime * 0.2, 300);
        $this->session->set($this->sessionKey, $this->sessionData);
    }

    /**
     * 用户注销操作.
     */
    public function logout($destroySession = true)
    {
        if ($destroySession) {
            $this->session->destroy();
        } else {
            $this->session->set($this->sessionKey, false);
        }
        $this->sessionData = false;
    }

    protected function getSessionData($key = null)
    {
        if (isset($key)) {
            return $this->sessionData && isset($this->sessionData[$key])
                ? $this->sessionData[$key] : null;
        } else {
            return $this->sessionData;
        }
    }

    /**
     * 判断用户是否登录.
     */
    public function isGuest()
    {
        return $this->sessionData === false;
    }

    /**
     * 判断用户是否需要重新登录.
     */
    public function isNeedLogin()
    {
        return $this->isGuest() || $this->needRegenerate;
    }
}
