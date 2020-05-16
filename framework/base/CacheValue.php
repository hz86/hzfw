<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 缓存值
 * Class CacheValue
 */
class CacheValue extends BaseObject
{
    /**
     * 是否有值
     * @var bool
     */
    public bool $hasValue;

    /**
     * 结果
     * @var
     */
    public $value;
}
