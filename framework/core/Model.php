<?php

namespace hzfw\core;

/**
 * Class Model
 * @package hzfw\web
 */
class Model extends BaseObject
{
    public static function Parse (array $data): self
    {
        $obj = new self();
        
        foreach ($data as $key => $val)
        {
            $obj->__set($key, $val);
        }
        
        return $obj;
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
    
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
}
