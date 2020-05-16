<?php

declare(strict_types=1);
namespace hzfw\web;

/**
 * 动作结果
 * @package hzfw\web
 */
class StatusCodeResult extends ActionResult
{
    public int $statusCode = 200;

    /**
     * 初始化
     * @param int $statusCode
     */
    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * 执行
     * @param HttpContext $httpContext
     */
    public function ExecuteResult(HttpContext $httpContext): void
    {
        $httpContext->response->SetStatusCode($this->statusCode);
    }
}
