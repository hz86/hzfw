<?php

namespace hzfw\web;

class FileResult extends ActionResult
{
    public string $content;
    public ?string $contentType = null;

    public function __construct(string $content, ?string $contentType)
    {
        $this->content = $content;
        $this->contentType = $contentType;
    }

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
