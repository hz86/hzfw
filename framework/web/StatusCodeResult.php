<?php

namespace hzfw\web;

class StatusCodeResult extends ActionResult
{
    public int $statusCode = 200;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function ExecuteResult(HttpContext $httpContext): void
    {
        $httpContext->response->SetStatusCode($this->statusCode);
    }
}
