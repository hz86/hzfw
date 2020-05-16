<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\base\FileStream;

/**
 * 动作结果
 * @package hzfw\web
 */
class FileStreamResult extends ActionResult
{
    public FileStream $stream;
    public ?string $contentType = null;

    /**
     * 初始化
     * @param string $content 文件流
     * @param string|null $contentType 内容类型 默认 application/octet-stream
     */
    public function __construct(FileStream $stream, ?string $contentType)
    {
        $this->stream = $stream;
        $this->contentType = $contentType;
    }

    /**
     * 执行
     * @param HttpContext $httpContext
     */
    public function ExecuteResult(HttpContext $httpContext): void
    {
        $response = $httpContext->response;

        if (null === $this->contentType) {
            $this->contentType = 'application/octet-stream';
        }

        $response->SetContentStream($this->stream);
        $response->SetContentType($this->contentType);
        $response->SetContent('');
    }
}
