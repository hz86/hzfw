<?php

declare(strict_types=1);
namespace hzfw\core;

/**
 * 服务成员
 * Class ServiceInstanceItem
 * @package hzfw\core
 */
class ServiceInstanceItem extends BaseObject
{
    public ?object $obj;
    public function __construct(?object $obj)
    {
        $this->obj = $obj;
    }
}
