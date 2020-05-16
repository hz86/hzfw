<?php

declare(strict_types=1);
namespace hzfw\base;

/**
 * Redis缓存
 * Class FileCache
 * @package hzfw\base
 */
class RedisCache extends Cache
{
    /**
     * 
     * @var \Redis | \RedisCluster
     */
    private $redis = null;

    /**
     * 初始化
     */
    public function __construct($host, ?string $passwd = null, int $db = 0, float $timeout = 1.5)
    {
        if (is_string($host))
        {
            $mathces = null;
            $this->redis = new \Redis();

            if (0 === preg_match('/^\[(.+?)\]:(\d+?)$/', $host, $mathces))
            {
                if (0 === preg_match('/^(.+?):(\d+?)$/', $host, $mathces))
                {
                    throw new \Exception("invalid host");
                }
            }

            if (false === $this->redis->pconnect($mathces[1], (int)$mathces[2], $timeout))
            {
                throw new \Exception("redis connect failure");
            }

            if (null !== $passwd)
            {
                if (false === $this->redis->auth($passwd))
                {
                    throw new \Exception("redis auth failure");
                }
            }

            if (false === $this->redis->select($db))
            {
                throw new \Exception("invalid database number");
            }
        }
        else if(is_array($host))
        {
            $this->redis = new \RedisCluster(null, $host, $timeout, 0, true, $passwd);
        }
        else
        {
            throw new \Exception("invalid host");
        }
    }

    /**
     * 读缓存
     * @param string $key
     * @return CacheValue
     */
    public function GetValue(string $key): CacheValue
    {
        $result = new CacheValue();
        $result->hasValue = false;
        $result->value = null;

        $data = $this->redis->get($key);
        if (false === $data)
        {
            return $result;
        }

        $data = gzuncompress($data);
        $result->value = unserialize($data);
        $result->hasValue = true;
        return $result;
    }

    /**
     * 写缓存
     * @param string $key
     * @param int $expiry ms
     * @param mixed $value
     * @return bool
     */
    public function SetValue(string $key, int $expiry, $value): bool
    {
        $result = false;
        $data = serialize($value);
        $data = gzcompress($data, 1);
        $this->redis->set($key, $data, ['px' => $expiry]);
        return $result;
    }

    /**
     * 删除缓存
     * @param string $key
     */
    public function Remove(string $key): void
    {
        $this->redis->del($key);
    }

    /**
     * 释放
     */
    public function Dispose(): void
    {
        $this->redis->close();
    }
}
