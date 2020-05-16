<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * HTTP访问响应
 */
class HttpRequestResponse extends BaseObject
{
    /**
     * 状态码
     * @var integer
     */
    public int $statusCode = 0;
    
    /**
     * 响应头
     * @var string
     */
    public string $header = '';
    
    /**
     * 响应内容
     * @var string
     */
    public string $body = '';
    
    /**
     * 异常
     * @var \Throwable
     */
    public ?\Throwable $exception = null;
}
