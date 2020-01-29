<?php

namespace hzfw\web;

class RedirectResult extends ActionResult
{
    public string $url;
    public int $statusCode = 302;

    public function __construct(string $url, int $statusCode)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
    }

    public function ExecuteResult(HttpContext $httpContext): void
    {
        $httpContext->response->Redirect($this->url, $this->statusCode);
    }
}
