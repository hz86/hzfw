<?php

declare(strict_types=1);
namespace hzfw\web;

/**
 * 动作结果
 * @package hzfw\web
 */
class RedirectResult extends ActionResult
{
    public string $url;
    public int $statusCode = 302;

    /**
     * 初始化
     * @param string $url URL地址
     * @param int $statusCode 状态码
     */
    public function __construct(string $url, int $statusCode)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
    }

    /**
     * 执行
     * @param HttpContext $httpContext
     */
    public function ExecuteResult(HttpContext $httpContext): void
    {
        $httpContext->response->Redirect($this->url, $this->statusCode);
    }
}
