<?php

namespace hzfw\core;

/**
 * 动态对象
 * Class DynamicObject
 * @package hzfw\core
 */
class DynamicObject extends BaseObject
{
    /**
     * 创建动态对象
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        foreach ($params as $key => $val)
        {
            if (is_array($val))
            {
                $this->__set($key, new DynamicObject($val));
            }
            else
            {
                $this->__set($key, $val);
            }
        }
    }
    
    public function __get(string $name)
    {
        if (property_exists($this, $name)) return $this->$name;
        throw new UnknownPropertyException("get unknown property '" . get_class($this) . "::{$name}'");
    }
    
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
    
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }
    
    public function __unset(string $name): void
    {
        unset($this->$name);
    }
    
    public function __call(string $name, $arguments)
    {
        if (is_callable($this->$name)) return call_user_func_array($this->$name, $arguments);
        throw new UnknownMethodException("call unknown method '" . get_class($this) . "::{$name}()'");
    }
    
    public static function __callStatic(string $name, $arguments)
    {
        if (is_callable(self::$name)) return call_user_func_array(self::$name, $arguments);
        throw new UnknownMethodException("call unknown static method '" . get_class(self) . "::{$name}()'");
    }
}
