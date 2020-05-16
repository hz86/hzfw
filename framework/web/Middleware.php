<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * 中间件
 * Class Middleware
 * @package hzfw\web
 */
class Middleware extends BaseObject
{
    /**
     * 扩展覆盖此方法
     * @param $httpContext
     * @param \Closure $next 需要继续执行才调用
     */
    public function Run(HttpContext $httpContext, \Closure $next): void
    {
        $next();
    }
}
