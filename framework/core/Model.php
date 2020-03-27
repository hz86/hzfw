<?php

namespace hzfw\core;

/**
 * Class Model
 * @package hzfw\web
 */
class Model extends BaseObject
{
    public static function Parse (?array $data): ?self
    {
        if(null === $data)
        {
            return null;
        }
        else
        {
            $obj = new static();
            
            foreach ($data as $key => $val)
            {
                if (false !== property_exists($obj, $key))
                {
                    $obj->$key = $val;
                }
            }
            
            return $obj;
        }
    }
    
    public function AsArray (): array
    {
        $arr = [];
        
        foreach ($this as $n => $v)
        {
            $arr[$n] = $v;
        }
        
        return $arr;
    }
    
    public function __get(string $name)
    {
        if (property_exists($this, $name)) return $this->$name;
        throw new UnknownPropertyException("get unknown property '" . get_class($this) . "::{$name}'");
    }
    
    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name)) $this->$name = $value;
        throw new UnknownPropertyException("set unknown property '" . get_class($this) . "::{$name}'");
    }
    
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }
    
    public function __unset(string $name): void
    {
        unset($this->$name);
    }
}
