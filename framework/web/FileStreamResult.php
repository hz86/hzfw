<?php

namespace hzfw\web;
use hzfw\base\FileStream;

class FileStreamResult extends ActionResult
{
    public FileStream $stream;
    public ?string $contentType = null;

    public function __construct(FileStream $stream, ?string $contentType)
    {
        $this->stream = $stream;
        $this->contentType = $contentType;
    }

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
