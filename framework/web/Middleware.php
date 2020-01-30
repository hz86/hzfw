<?php

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
     * @param $next
     */
    public function Run(HttpContext $httpContext, \Closure $next): void
    {
        $next();
    }
}
