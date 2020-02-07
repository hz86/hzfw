<?php

namespace hzfw\core;

/**
 * 服务范围
 * Class ServiceScope
 * @package hzfw\core
 */
class ServiceScope extends BaseObject
{
    /**
     * 服务容器
     * @var ServiceCollection
     */
    private ServiceCollection $collection;

    /**
     * 服务提供器
     * @var ServiceProvider
     */
    public ServiceProvider $serviceProvider;

    /**
     * 初始化
     * ServiceScope constructor.
     * @param ServiceCollection $collection
     */
    public function __construct(ServiceCollection $collection)
    {
        $this->collection = $collection;
        $this->collection->scopeInstances[spl_object_hash($this)] = [];
        $this->serviceProvider = new ServiceProvider($collection, $this);
    }

    /**
     * 销毁
     */
    public function Dispose(): void
    {
        $hash = spl_object_hash($this);
        $instances = $this->collection->scopeInstances[$hash];

        while (null !== ($item = array_pop($instances)))
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

        foreach ($this->collection->services as $service)
        {
            foreach ($service as $item)
            {
                if (isset($item->instances[$hash])) {
                    unset($item->instances[$hash]);
                }
            }
        }

        unset($this->collection->scopeInstances[$hash]);
    }
}
