<?php

namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * 中间件管理器
 * Class MiddlewareManager
 * @package hzfw\web
 */
class MiddlewareManager extends BaseObject
{
    /**
     * 中间件链表
     * @var array
     */
    private array $middlewares = [];

    /**
     * HTTP上下文
     * @var HttpContext
     */
    private HttpContext $httpContext;

    /**
     * 初始化
     * MiddlewareManager constructor.
     * @param HttpContext $httpContext
     */
    public function __construct(HttpContext $httpContext)
    {
        $this->httpContext = $httpContext;
    }

    /**
     * 添加中间件
     * @param Middleware $middleware
     */
    public function Add(Middleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * 运行
     */
    public function Run(): void
    {
        $this->Next();
    }

    /**
     * 下一个
     */
    private function Next(): void
    {
        $item = array_pop($this->middlewares);
        if (null !== $item)
        {
            $item->Run($this->httpContext, function () {
                $this->Next();
            });
        }
    }
}
