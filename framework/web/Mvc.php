<?php

namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\core\ServiceCollection;
use hzfw\core\ServiceProvider;
use hzfw\core\UnknownClassException;

/**
 * {
 *   "Mvc": {
 *     "Charset": "utf-8",
 *     "ControllerNamespace": "frontend\\controllers",
 *     "ViewComponentNamespace": "frontend\\viewcomponents",
 *     "ViewPath": "frontend/views",
 *     "Error": "Site/Error"
 *   }
 * }
 * Class Mvc
 * @package hzfw\web
 */
class Mvc extends BaseObject
{
    /**
     * 中间件
     * @var MiddlewareManager
     */
    private MiddlewareManager $middlewareManager;

    /**
     * HTTP上下文
     * @var HttpContext
     */
    private HttpContext $httpContext;

    /**
     * 添加服务
     * @param ServiceCollection $service
     * @throws UnknownClassException
     */
    public static function AddService(ServiceCollection $service): void
    {
        // 加载视图
        $service->AddTransient(View::ClassName());

        // 加载上下文
        $service->AddScoped(HttpRequest::ClassName());
        $service->AddScoped(HttpResponse::ClassName());
        $service->AddScoped(HttpContext::ClassName());

        // 加载路由服务
        $service->AddScoped(Route::ClassName());

        // 中间件
        $service->AddScoped(MiddlewareManager::ClassName());

        // 加载Mvc
        $service->AddScoped(Mvc::ClassName());
    }

    /**
     * 使用服务
     * @param ServiceProvider $app
     * @param \Closure|null $func 可以添加中间件或执行其他操作
     * $func = function(ServiceProvider $service) {}
     */
    public static function Use(ServiceProvider $app, ?\Closure $func = null): void
    {
        $scope = $app->CreateScope();

        try
        {
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool
            {
                if (!(error_reporting() & $errno)) return false;
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            });

            $mvc = $scope->serviceProvider->GetService(Mvc::ClassName());
            $middlewareManager = $scope->serviceProvider->GetService(MiddlewareManager::ClassName());
            $middlewareManager->Add(new MvcMiddleware());
            if (null !== $func)
            {
                $func($scope->serviceProvider);
            }

            $mvc->Run();
        }
        catch (\Throwable $t)
        {
            for ($level = ob_get_level(); $level > 0; $level--) {
                if (false === @ob_end_clean()) {
                    ob_clean();
                }
            }
            
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true);
            echo 'error occurred';
            if (HZFW_DEBUG)
            {
                var_dump($t);
            }
        }
        finally
        {
            $scope->Dispose();
        }
    }

    /**
     * 初始化
     * Mvc constructor.
     * @param MiddlewareManager $middlewareManager
     * @param HttpContext $httpContext
     */
    public function __construct(HttpContext $httpContext, MiddlewareManager $middlewareManager)
    {
        $this->httpContext = $httpContext;
        $this->middlewareManager = $middlewareManager;
    }

    /**
     * 运行
     */
    public function Run(): void
    {
        // 执行中间件
        $this->middlewareManager->Run();

        // 发送响应头
        $this->SendHeader();

        // 发送响应内容
        $this->SendContent();
    }

    /**
     * 发送头
     */
    private function SendHeader(): void
    {
        //响应信息
        $response = $this->httpContext->response;

        //设置状态码
        $version = $response->GetVersion();
        $statusCode = $response->GetStatusCode();
        $statusText = $response->GetStatusText();
        header("{$version} {$statusCode} {$statusText}", true);

        //HEADER设置
        foreach ($response->GetHeaderAll() as $k => $v)
        {
            if (is_array($v))
            {
                foreach ($v as $v2) {
                    header("{$k}: {$v2}", false);
                }
            }
            else
            {
                header("{$k}: {$v}", true);
            }
        }

        //COOKIE设置
        foreach ($response->GetCookieAll() as $v)
        {
            setcookie($v->name, $v->value, [
                'expires' => $v->expires,
                'path' => $v->path,
                'domain' => $v->domain,
                'secure' => $v->secure,
                'httponly' => $v->httponly,
                'samesite' => $v->samesite
            ]);
        }
    }

    /**
     * 单个范围
     */
    private function SendContentRange(): void
    {
        $response = $this->httpContext->response;
        $contentRange = $response->GetContentRange();

        //输出类型
        $contentTypeHeader = '';
        $contentType = $response->GetContentType();
        if (0 !== preg_match('/^text\/.+/i', $contentType) || 0 !== preg_match('/^application\/(javascript|json)$/i', $contentType))
        {
            //文本类型
            $contentCharset = $response->GetContentCharset();
            $contentTypeHeader = '' === $contentCharset ? "Content-Type: {$contentType}" : "Content-Type: {$contentType}; charset={$contentCharset}";
        }
        else
        {
            //其他
            $contentTypeHeader = "Content-Type: {$contentType}";
        }

        //设置状态码
        $response->SetStatusCode(206);

        //输出头
        $version = $response->GetVersion();
        $statusCode = $response->GetStatusCode();
        $statusText = $response->GetStatusText();
        header("{$version} {$statusCode} {$statusText}", true);
        header($contentTypeHeader, true);

        //获取单个范围
        list($begin, $end) = $contentRange[0];
        $stream = $response->GetContentStream();
        if (null === $stream)
        {
            //完整内容
            $content = $response->GetContent();
            $contentLength = strlen($content);

            //补全
            if (null === $end && null === $begin)
            {
                $begin = 0;
                $end = $contentLength - 1;
            }
            else if (null === $end && null !== $begin)
            {
                $end = $contentLength - 1;
            }
            else if (null !== $end && null === $begin)
            {
                $begin = $contentLength - $end;
                $end = $begin + 1;
            }

            //判断是否异常
            if ($begin > $end || $end >= $contentLength)
            {
                header("{$version} 416 Requested Range Not Satisfiable", true);
                return;
            }

            //截取数据并返回
            header("Content-Range: bytes {$begin}-{$end}/{$contentLength}", true);
            $content = substr($content, $begin, $end - $begin + 1);
            echo $content;
        }
        else
        {
            set_time_limit(0);
            $size = 1024 * 1204 * 8;

            //文件大小
            $contentLength = $stream->Size();

            //补全
            if (null === $end && null === $begin)
            {
                $begin = 0;
                $end = $contentLength - 1;
            }
            else if (null === $end && null !== $begin)
            {
                $end = $contentLength - 1;
            }
            else if (null !== $end && null === $begin)
            {
                $begin = $contentLength - $end;
                $end = $begin + 1;
            }

            //判断是否异常
            if ($begin > $end || $end >= $contentLength)
            {
                header("{$version} 416 Requested Range Not Satisfiable", true);
                return;
            }

            //截取数据并返回
            header("Content-Range: bytes {$begin}-{$end}/{$contentLength}", true);

            //设置开始位置
            $stream->Seek($begin, SEEK_SET);

            //判断文件尾和指定范围
            while (!$stream->IsEof() && ($pos = $stream->Tell()) <= $end)
            {
                //修正读取大小
                if ($pos + $size > $end) {
                    $size = $end - $pos + 1;
                }

                //输出内容并强制刷新
                echo $stream->Read($size);
                flush();
            }

            $stream->Dispose();
            unset($stream);
        }
    }

    /**
     * 多个范围
     */
    private function SendContentMultipartRange(): void
    {
        $response = $this->httpContext->response;
        $contentRange = $response->GetContentRange();

        //输出类型
        $contentTypeHeader = '';
        $contentType = $response->GetContentType();
        if (0 !== preg_match('/^text\/.+/i', $contentType) || 0 !== preg_match('/^application\/(javascript|json)$/i', $contentType))
        {
            //文本类型
            $contentCharset = $response->GetContentCharset();
            $contentTypeHeader = '' === $contentCharset ? "Content-Type: {$contentType}" : "Content-Type: {$contentType}; charset={$contentCharset}";
        }
        else
        {
            //其他
            $contentTypeHeader = "Content-Type: {$contentType}";
        }

        //设置状态码
        $response->SetStatusCode(206);

        //输出头
        $version = $response->GetVersion();
        $statusCode = $response->GetStatusCode();
        $statusText = $response->GetStatusText();
        header("{$version} {$statusCode} {$statusText}", true);

        //多个
        $delimiter = md5(uniqid('', true));
        header("Content-Type: multipart/byteranges; boundary={$delimiter}", true);
        $stream = $response->GetContentStream();

        if (null === $stream)
        {
            //完整内容
            $content = $response->GetContent();
            $contentLength = strlen($content);
            foreach ($contentRange as &$v)
            {
                //补全
                list($begin, $end) = $v;
                if (null === $end && null === $begin)
                {
                    $begin = 0;
                    $end = $contentLength - 1;
                }
                else if (null === $end && null !== $begin)
                {
                    $end = $contentLength - 1;
                }
                else if (null !== $end && null === $begin)
                {
                    $begin = $contentLength - $end;
                    $end = $begin + 1;
                }

                //判断是否异常
                if ($begin > $end || $end >= $contentLength)
                {
                    header("{$version} 416 Requested Range Not Satisfiable", true);
                    return;
                }

                $v[0] = $begin;
                $v[1] = $end;
            }

            foreach ($contentRange as &$v)
            {
                list($begin, $end) = $v;
                echo "\r\n--{$delimiter}\r\n{$contentTypeHeader}\r\n";
                echo "Content-Range: bytes {$begin}-{$end}/{$contentLength}\r\n\r\n";
                $content = substr($content, $begin, $end - $begin + 1);
                echo $content;
            }

            echo "\r\n--{$delimiter}--\r\n";
        }
        else
        {
            set_time_limit(0);
            $size = 1024 * 1204 * 8;

            //文件大小
            $contentLength = $stream->Size();
            foreach ($contentRange as &$v)
            {
                //补全
                list($begin, $end) = $v;
                if (null === $end && null === $begin)
                {
                    $begin = 0;
                    $end = $contentLength - 1;
                }
                else if (null === $end && null !== $begin)
                {
                    $end = $contentLength - 1;
                }
                else if (null !== $end && null === $begin)
                {
                    $begin = $contentLength - $end;
                    $end = $begin + 1;
                }

                //判断是否异常
                if ($begin > $end || $end >= $contentLength)
                {
                    header("{$version} 416 Requested Range Not Satisfiable", true);
                    return;
                }

                $v[0] = $begin;
                $v[1] = $end;
            }

            foreach ($contentRange as &$v)
            {
                list($begin, $end) = $v;
                echo "\r\n--{$delimiter}\r\n{$contentTypeHeader}\r\n";
                echo "Content-Range: bytes {$begin}-{$end}/{$contentLength}\r\n\r\n";

                //设置开始位置
                $stream->Seek($begin, SEEK_SET);

                //判断文件尾和指定范围
                while (!$stream->IsEof() && ($pos = $stream->Tell()) <= $end)
                {
                    //修正读取大小
                    if ($pos + $size > $end) {
                        $size = $end - $pos + 1;
                    }

                    //输出内容并强制刷新
                    echo $stream->Read($size);
                    flush();
                }
            }

            echo "\r\n--{$delimiter}--\r\n";
            $stream->Dispose();
            unset($stream);
        }
    }

    /**
     * 发送内容
     */
    private function SendContent(): void
    {
        $response = $this->httpContext->response;
        $contentRange = $response->GetContentRange();

        if (null === $contentRange)
        {
            //输出类型
            $contentTypeHeader = '';
            $contentType = $response->GetContentType();
            if (0 !== preg_match('/^text\/.+/i', $contentType) || 0 !== preg_match('/^application\/(javascript|json)$/i', $contentType))
            {
                //文本类型
                $contentCharset = $response->GetContentCharset();
                $contentTypeHeader = '' === $contentCharset ? "Content-Type: {$contentType}" : "Content-Type: {$contentType}; charset={$contentCharset}";
            }
            else
            {
                //其他
                $contentTypeHeader = "Content-Type: {$contentType}";
            }

            //设置输出类型
            header($contentTypeHeader, true);

            //直接返回
            $stream = $response->GetContentStream();
            if (null === $stream)
            {
                //输出内容
                echo $response->GetContent();
            }
            else
            {
                set_time_limit(0);
                $size = 1024 * 1204 * 8;

                while (!$stream->IsEof())
                {
                    //输出内容并强制刷新
                    echo $stream->Read($size);
                    flush();
                }

                $stream->Dispose();
                unset($stream);
            }
        }
        else if (1 === count($contentRange))
        {
            $this->SendContentRange();
        }
        else
        {
            $this->SendContentMultipartRange();
        }
    }
}
