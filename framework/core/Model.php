<?php

declare(strict_types=1);
namespace hzfw\core;

/**
 * 模型
 * Class Model
 * @package hzfw\web
 */
class Model extends BaseObject
{
    /**
     * 数组转模型
     * @param array $data
     * @return self|null
     */
    public static function Parse (?array $data = null): ?self
    {
        if(null === $data)
        {
            return null;
        }
        else
        {
            $obj = new static();
            $reflection = new \ReflectionClass($obj);
            
            foreach ($data as $key => $val)
            {
                if (false !== $reflection->hasProperty($key))
                {
                    $reflectionProperty = $reflection->getProperty($key);
                    $propertyType = $reflectionProperty->getType();
                    if (null !== $propertyType)
                    {
                        $propertyTypes = [];
                        $valType = gettype($val);
                        
                        if ($propertyType instanceof \ReflectionUnionType)
                        {
                            $propertyTypes = $propertyType->getTypes();
                        }
                        else
                        {
                            $propertyTypes = [$propertyType];
                        }
                        
                        if ('NULL' !== $valType)
                        {
                            foreach ($propertyTypes as $propertyType)
                            {
                                $propertyTypeName = $propertyType->getName();
                                
                                if ('string' === $valType)
                                {
                                    if ('int' === $propertyTypeName && 0 !== preg_match('/^[+-]?[0-9]+$/', $val))
                                    {
                                        $val = (int)$val;
                                        break;
                                    }
                                    else if ('float' === $propertyTypeName && 0 !== preg_match('/^[+-]?([0-9]+|[0-9]+[\.][0-9]+)(E[+-]?[0-9]+)?$/i', $val))
                                    {
                                        $val = (float)$val;
                                        break;
                                    }
                                    else if ('bool' === $propertyTypeName && 0 !== preg_match('/^(true|false|TRUE|FALSE|[01])$/', $val))
                                    {
                                        $val = ('true' === $val || 'TRUE' === $val || '1' === $val);
                                        break;
                                    }
                                }
                                else if ('integer' === $valType || 'double' === $valType || 'float' === $valType || 'boolean' === $valType)
                                {
                                    if ('string' === $propertyTypeName)
                                    {
                                        $val = (string)$val;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $reflectionProperty->setValue($obj, $val);
                    }
                    else
                    {
                        $reflectionProperty->setValue($obj, $val);
                    }
                }
            }
            
            return $obj;
        }
    }
    
    /**
     * 模型转数组
     * @return array
     */
    public function AsArray (): array
    {
        $arr = [];
        
        foreach ($this as $key => $val)
        {
            $arr[$key] = $val;
        }
        
        return $arr;
    }
    
    public function __get(string $name)
    {
        throw new UnknownPropertyException("get unknown property '" . get_class($this) . "::{$name}'");
    }
    
    public function __set(string $name, $value): void
    {
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
    
    public function __call(string $name, $arguments)
    {
        if (is_callable($this->$name)) return call_user_func_array($this->$name, $arguments);
        throw new UnknownMethodException("call unknown method '" . get_class($this) . "::{$name}()'");
    }
    
    public static function __callStatic(string $name, $arguments)
    {
        if (is_callable(static::$$name)) return call_user_func_array(static::$$name, $arguments);
        throw new UnknownMethodException("call unknown static method '" . static::class . "::{$name}()'");
    }
}
