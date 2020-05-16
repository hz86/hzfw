<?php

declare(strict_types=1);
namespace hzfw\web;

/**
 * 动作结果
 * @package hzfw\web
 */
class FileResult extends ActionResult
{
    public string $content;
    public ?string $contentType = null;

    /**
     * 初始化
     * @param string $content 数据内容
     * @param string|null $contentType 内容类型 默认 application/octet-stream
     */
    public function __construct(string $content, ?string $contentType)
    {
        $this->content = $content;
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

        $response->SetContent($this->content);
        $response->SetContentType($this->contentType);

        $stream = $response->GetContentStream();
        if (null !== $stream)
        {
            $response->SetContentStream(null);
            $stream->Dispose();
            unset($stream);
        }
    }
}
