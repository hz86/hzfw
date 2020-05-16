<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\core\ServiceProvider;

/**
 * HTTP 上下文
 * Class HttpContext
 * @package hzfw\web
 */
class HttpContext extends BaseObject
{
    /**
     * HTTP请求
     * @var HttpRequest
     */
    public HttpRequest $request;
    
    /**
     * HTTP响应
     * @var HttpResponse
     */
    public HttpResponse $response;

    /**
     * 服务提供器
     * @var ServiceProvider
     */
    public ServiceProvider $requestServices;

    /**
     * HTTP上下文
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param ServiceProvider $requestServices
     */
    public function __construct(
        HttpRequest $request, HttpResponse $response,
        ServiceProvider $requestServices)
    {
        $this->request = $request;
        $this->response = $response;
        $this->requestServices = $requestServices;
    }
}
