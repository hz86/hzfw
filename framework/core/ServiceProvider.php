<?php

declare(strict_types=1);
namespace hzfw\core;

/**
 * 服务提供器
 * Class ServiceProvider
 * @package hzfw\core
 */
class ServiceProvider extends BaseObject
{
    public ?ServiceScope $scope;
    public ServiceCollection $collection;
    private string $collectionHash;
    private string $hash;

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param ServiceCollection $collection
     * @param ServiceScope|null $scope
     */
    public function __construct(ServiceCollection $collection, ?ServiceScope $scope = null)
    {
        $this->scope = $scope;
        $this->collection = $collection;
        $this->collectionHash = spl_object_hash($collection);
        if (null !== $scope) $this->hash = spl_object_hash($scope);
        else $this->hash = $this->collectionHash;
    }

    /**
     * 创建范围
     * @return ServiceScope
     */
    public function CreateScope(): ServiceScope
    {
        return new ServiceScope($this->collection);
    }

    /**
     * 获取服务
     * 只返回最新一个
     * @param string $class
     * @return object|null
     * @throws UnknownClassException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
    public function GetService(string $class): ?object
    {
        $result = null;

        $service = isset($this->collection->services[$class]) ?
            $this->collection->services[$class] : null;

        if (null !== $service)
        {
            $serviceItem = $service[count($service) - 1];
            if (ServiceType::Transient === $serviceItem->type)
            {
                $t = null;
                if (null !== $this->scope) $t = $this;
                else $t = $this->collection->serviceProvider;

                if (false === isset($serviceItem->instances[$this->hash])) {
                    $serviceItem->instances[$this->hash] = [];
                }

                $result = null !== $serviceItem->func ?
                    self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                    self::NewService($t, $serviceItem->class);

                $item = new ServiceInstanceItem($result);
                $serviceItem->instances[$this->hash][] = $item;
                if (null !== $this->scope) $this->collection->scopeInstances[$this->hash][] = $item;
                $this->collection->instances[] = $item;
            }
            else if (ServiceType::Singleton === $serviceItem->type)
            {
                $t = $this->collection->serviceProvider;
                if (false === isset($serviceItem->instances[$this->collectionHash]))
                {
                    $result = null !== $serviceItem->func ?
                        self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                        self::NewService($t, $serviceItem->class);

                    $item = new ServiceInstanceItem($result);
                    $serviceItem->instances[$this->collectionHash] = [$item];
                    $this->collection->instances[] = $item;
                }
                else
                {
                    $item = $serviceItem->instances[$this->collectionHash][0];
                    $result = $item->obj;
                }
            }
            else if (ServiceType::Scoped === $serviceItem->type)
            {
                if (null !== $this->scope)
                {
                    $t = null;
                    if (null !== $this->scope) $t = $this;
                    else $t = $this->collection->serviceProvider;

                    if (false === isset($serviceItem->instances[$this->hash]))
                    {
                        $result = null !== $serviceItem->func ?
                            self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                            self::NewService($t, $serviceItem->class);

                        $item = new ServiceInstanceItem($result);
                        $serviceItem->instances[$this->hash] = [$item];
                        $this->collection->scopeInstances[$this->hash][] = $item;
                        $this->collection->instances[] = $item;
                    }
                    else
                    {
                        $item = $serviceItem->instances[$this->hash][0];
                        $result = $item->obj;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 获取所有服务
     * @param string $class
     * @return array|null
     * @throws UnknownClassException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
    public function GetServices(string $class): ?array
    {
        $result = null;

        $service = isset($this->collection->services[$class]) ?
            $this->collection->services[$class] : null;

        if (null !== $service)
        {
            $result = [];
            foreach ($service as $serviceItem)
            {
                if (ServiceType::Transient === $serviceItem->type)
                {
                    $t = null;
                    if (null !== $this->scope) $t = $this;
                    else $t = $this->collection->serviceProvider;

                    if (false === isset($serviceItem->instances[$this->hash])) {
                        $serviceItem->instances[$this->hash] = [];
                    }

                    $obj = null !== $serviceItem->func ?
                        self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                        self::NewService($t, $serviceItem->class);

                    $item = new ServiceInstanceItem($obj);
                    $serviceItem->instances[$this->hash][] = $item;
                    if (null !== $this->scope) $this->collection->scopeInstances[$this->hash][] = $item;
                    $this->collection->instances[] = $item;

                    $result[] = $obj;
                }
                else if (ServiceType::Singleton === $serviceItem->type)
                {
                    $t = $this->collection->serviceProvider;
                    if (false === isset($serviceItem->instances[$this->collectionHash]))
                    {
                        $obj = null !== $serviceItem->func ?
                            self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                            self::NewService($t, $serviceItem->class);

                        $item = new ServiceInstanceItem($obj);
                        $serviceItem->instances[$this->collectionHash] = [$item];
                        $this->collection->instances[] = $item;

                        $result[] = $obj;
                    }
                    else
                    {
                        $item = $serviceItem->instances[$this->collectionHash][0];
                        $result[] = $item->obj;
                    }
                }
                else if (ServiceType::Scoped === $serviceItem->type)
                {
                    if (null !== $this->scope)
                    {
                        $t = null;
                        if (null !== $this->scope) $t = $this;
                        else $t = $this->collection->serviceProvider;

                        if (false === isset($serviceItem->instances[$this->hash]))
                        {
                            $obj = null !== $serviceItem->func ?
                                self::NewServiceFunc($t, $serviceItem->class, $serviceItem->func) :
                                self::NewService($t, $serviceItem->class);

                            $item = new ServiceInstanceItem($obj);
                            $serviceItem->instances[$this->hash] = [$item];
                            $this->collection->scopeInstances[$this->hash][] = $item;
                            $this->collection->instances[] = $item;

                            $result[] = $obj;
                        }
                        else
                        {
                            $item = $serviceItem->instances[$this->hash][0];
                            $result[] = $item->obj;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 创建对象
     * @param ServiceProvider $t
     * @param string $class
     * @return object
     * @throws UnknownClassException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
    private static function NewService(ServiceProvider $t, string $class): object
    {
        $args = [];
        $reflection = new \ReflectionClass($class);

        //是否存在构造方法
        $reflectionMethod = $reflection->getConstructor();
        if (null !== $reflectionMethod)
        {
            //获取参数信息
            $reflectionParameters = $reflectionMethod->getParameters();
            foreach ($reflectionParameters as $reflectionParameter)
            {
                //获取类
                $parameterClass = $reflectionParameter->getClass();
                if (null === $parameterClass)
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' not class");
                }

                //获取对象
                $parameterInstance = $t->GetService($parameterClass->getName());
                if (null === $parameterInstance)
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    $parameterClassName = $parameterClass->getName();
                    throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type '{$parameterClassName}' no service added");
                }

                //压入参数数组
                $args[] = $parameterInstance;
            }
        }

        //创建实例
        return $reflection->newInstanceArgs($args);
    }

    /**
     * 创建对象
     * @param ServiceProvider $t
     * @param string $class
     * @param callable $func
     * @return object
     * @throws UnknownClassException
     * @throws \ReflectionException
     */
    private static function NewServiceFunc(ServiceProvider $t, string $class, callable $func): object
    {
        $instance = $func($t);

        if (!($instance instanceof $class))
        {
            $reflectionClass = new \ReflectionClass($instance);
            $reflectionClassName = $reflectionClass->getName();
            throw new UnknownClassException("return class '{$reflectionClassName}' not an instanceof a class '{$class}'");
        }

        return $instance;
    }
}
