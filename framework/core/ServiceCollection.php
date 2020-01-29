<?php

namespace hzfw\core;

/**
 * 服务容器
 * Class ServiceCollection
 * @package hzfw\core
 */
class ServiceCollection extends BaseObject
{
    /**
     * 服务数组
     * @var array
     */
    public array $services = [];

    /**
     * 实例数组
     * [obj,...]
     * @var array
     */
    public array $instances = [];

    /**
     * 实例数组
     * ["hash" => [obj,...]]
     * @var array
     */
    public array $scopeInstances = [];

    /**
     * 服务提供器
     * @var ServiceProvider
     */
    public ServiceProvider $serviceProvider;

    /**
     * 初始化
     * ServiceCollection constructor.
     */
    public function __construct()
    {
        $this->serviceProvider = new ServiceProvider($this);
    }

    /**
     * 瞬态
     * @param string $class
     * @param \Closure|null $func
     * @throws UnknownClassException
     * $func = function (ServiceProvider $options) {};
     */
    public function AddTransient(string $class, ?\Closure $func = null): void
    {
        $this->Add(ServiceType::Transient, $class, $func);
    }

    /**
     * 单例
     * @param string $class
     * @param \Closure|null $func
     * @throws UnknownClassException
     * $func = function (ServiceProvider $options) {};
     */
    public function AddSingleton(string $class, ?\Closure $func = null): void
    {
        $this->Add(ServiceType::Singleton, $class, $func);
    }

    /**
     * 范围
     * @param string $class
     * @param \Closure|null $func
     * @throws UnknownClassException
     * $func = function (ServiceProvider $options) {};
     */
    public function AddScoped(string $class, ?\Closure $func = null): void
    {
        $this->Add(ServiceType::Scoped, $class, $func);
    }

    /**
     * 添加服务
     * @param int $type
     * @param string $class
     * @param \Closure|null $func
     * @throws UnknownClassException
     * $func = function (ServiceProvider $options) {};
     */
    public function Add(int $type, string $class, ?\Closure $func = null): void
    {
        if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
            throw new UnknownClassException("class not exist '{$class}'");
        }

        $item = new ServiceCollectionItem($type, $class, $func);
        if (!isset($this->services[$class])) $this->services[$class] = [];
        $this->services[$class][] = $item;
    }

    /**
     * 销毁
     */
    public function Dispose(): void
    {
        while (null !== ($item = array_pop($this->instances)))
        {
            if (null !== $item->obj)
            {
                $obj = $item->obj;
                if (method_exists($obj, "Dispose")) {
                    $obj->Dispose();
                }

                $item->obj = null;
                unset($obj);
            }
        }

        $this->scopeInstances = [];
        $this->services = [];
    }
}
