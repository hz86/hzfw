<?php

declare(strict_types=1);
namespace hzfw\core;

/**
 * 基础对象
 * Class BaseObject
 * @package hzfw\core
 */
class BaseObject
{
    /**
     * 获取类名
     * @return string
     */
    public static function ClassName(): string
    {
        return get_called_class();
    }
    
    /**
     * 释放
     */
    public function Dispose(): void
    {
        
    }
}
