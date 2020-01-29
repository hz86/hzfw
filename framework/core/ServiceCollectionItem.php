<?php

namespace hzfw\core;

/**
 * 服务成员
 * Class ServiceCollectionItem
 * @package hzfw\core
 */
class ServiceCollectionItem extends BaseObject
{
    /**
     * 服务类型
     * @var int
     */
    public int $type;

    /**
     * 类名
     * @var string
     */
    public string $class;

    /**
     * 回调
     * $func = function (ServiceProvider $options) {
     *     return new Class();
     * };
     * @var \Closure|null
     */
    public ?\Closure $func;

    /**
     * 实例对象
     * ["hash" => [obj,...]]
     * @var array
     */
    public array $instances;
    /**
     * 初始化
     * ServiceCollectionItem constructor.
     * @param int $type
     * @param string $class
     * @param \Closure|null $func
     */
    public function __construct(int $type, string $class, ?\Closure $func = null)
    {
        $this->type = $type;
        $this->class = $class;
        $this->func = $func;
        $this->instances = [];
    }
}
