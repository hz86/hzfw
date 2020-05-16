<?php

declare(strict_types=1);
namespace hzfw\core;

/**
 * 服务类型
 * Class ServiceType
 * @package hzfw\core
 */
class ServiceType extends BaseObject
{
    /**
     * 瞬态
     */
    public const Transient = 0;

    /**
     * 单例
     */
    public const Singleton = 1;

    /**
     * 范围
     */
    public const Scoped = 2;
}
