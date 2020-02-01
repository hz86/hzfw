<?php

namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 缓存
 * Class Cache
 * @package hzfw\base
 */
class Cache extends BaseObject
{
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
        return $result;
    }

    /**
     * 写缓存
     * @param string $key
     * @param int $expiry
     * @param mixed $value
     * @return bool
     */
    public function SetValue(string $key, int $expiry, $value): bool
    {
        return false;
    }

    /**
     * 删除缓存
     * @param string $key
     */
    public function Remove(string $key): void
    {

    }
}
