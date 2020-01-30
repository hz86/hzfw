<?php

namespace frontend\middlewares;
use hzfw\web\HttpContext;
use hzfw\web\Middleware;

class TestMiddleware extends Middleware
{
    public function Run(HttpContext $httpContext, \Closure $next): void
    {
        $next();
    }
}
