<?php

namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\core\ServiceCollection;
use hzfw\core\ServiceProvider;
use hzfw\core\UnknownClassException;
use hzfw\core\UnknownMethodException;
use hzfw\core\UnknownParameterException;

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
     * 配置
     * @var Config
     */
    private Config $config;

    /**
     * HTTP上下文
     * @var HttpContext
     */
    private HttpContext $httpContext;

    /**
     * 路由
     * @var Route
     */
    private Route $route;

    /**
     * 添加服务
     * @param ServiceCollection $service
     * @throws UnknownClassException
     */
    public static function AddService(ServiceCollection $service)
    {
        // 加载视图
        $service->AddTransient(View::ClassName());

        // 加载上下文
        $service->AddScoped(HttpRequest::ClassName());
        $service->AddScoped(HttpResponse::ClassName());
        $service->AddScoped(HttpContext::ClassName());

        // 加载路由服务
        $service->AddScoped(Route::ClassName());

        // 加载Mvc
        $service->AddScoped(Mvc::ClassName());
    }

    /**
     * 使用服务
     * @param ServiceProvider $app
     * @throws \ReflectionException
     */
    public static function Use(ServiceProvider $app)
    {
        $scope = $app->CreateScope();

        try
        {
            $mvc = $scope->serviceProvider->GetService(Mvc::ClassName());
            $mvc->Run();
        }
        catch (\Throwable $t)
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true);
            echo 'error occurred';
        }
        finally {
            $scope->Dispose();
        }
    }

    /**
     * 初始化
     * Mvc constructor.
     * @param Config $config
     * @param HttpContext $httpContext
     * @param Route $route
     */
    public function __construct(Config $config, HttpContext $httpContext, Route $route)
    {
        $this->config = $config;
        $this->httpContext = $httpContext;
        $this->route = $route;
    }

    /**
     * 运行
     */
    public function Run()
    {
        $route = $this->route;
        $response = $this->httpContext->response;

        try
        {
            //路由匹配失败，抛出404
            if ('' === $route->GetControllerName() || '' === $route->GetActionName()) {
                throw new HttpException(404, 'route matching failed');
            }

            try
            {
                //调用控制器
                $result = $this->CallAction(
                    $this->config->Mvc->ControllerNamespace,
                    $route->GetControllerName(),
                    $route->GetActionName());

                //执行动作结果
                $result->ExecuteResult($this->httpContext);
            }
            catch (UnknownClassException $e) {
                throw new HttpException(404, $e->getMessage(), $e);
            }
            catch (UnknownMethodException $e) {
                throw new HttpException(404, $e->getMessage(), $e);
            }
            catch (UnknownParameterException $e) {
                throw new HttpException(404, $e->getMessage(), $e);
            }
            catch (\Throwable $e) {
                throw $e;
            }
        }
        catch (\Throwable $t)
        {
            //错误处理
            for ($level = ob_get_level(); $level > 0; $level--) {
                if (false === @ob_end_clean()) {
                    ob_clean();
                }
            }

            $response->ClearHeader();
            $response->ClearCookie();
            $response->SetContent('');

            if (null !== ($stream = $response->GetContentStream())) {
                $response->SetContentStream(null);
                $stream->Dispose();
                unset($stream);
            }

            $response->SetStatusCode(500);
            if($t instanceof HttpException) $response->SetStatusCode($t->getCode());

            list($controller, $action) = explode('/', $this->config->Mvc->Error);
            $result = $this->CallAction($this->config->Mvc->ControllerNamespace, $controller, $action, [
                'statusCode' => $response->GetStatusCode(), 'exception' => $t
            ]);

            $result->ExecuteResult($this->httpContext);
        }

        //发送头
        $this->SendHeader();

        //发送内容
        $this->SendContent();
    }

    /**
     * 调用动作
     * @param string $namespace
     * @param string $controller
     * @param string $action
     * @param array $pars
     * @return ActionResult
     * @throws UnknownClassException
     * @throws UnknownMethodException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
    private function CallAction(string $namespace, string $controller, string $action, array $pars = []): ActionResult
    {
        $method = $action;
        $class = "\\{$namespace}\\{$controller}Controller";
        $reflection = new \ReflectionClass($class);

        //构造函数
        $constructorArgs = [];
        $reflectionMethod = $reflection->getConstructor();
        if (null !== $reflectionMethod)
        {
            //获取参数信息
            $reflectionParameters = $reflectionMethod->getParameters();
            foreach ($reflectionParameters as $reflectionParameter)
            {
                //获取类
                $parameterClass = $reflectionParameter->getClass();
                if (null === $parameterClass)
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' not class");
                }

                //获取对象
                $parameterObj = $this->httpContext->requestServices->GetService($parameterClass->getName());
                if (null === $parameterObj)
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    $parameterClassName = $parameterClass->getName();
                    throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type '{$parameterClassName}' no service added");
                }

                $constructorArgs[] = $parameterObj;
            }
        }

        //创建实例
        $obj = $reflection->newInstanceArgs($constructorArgs);
        if (!($obj instanceof Controller))
        {
            $baseClass = Controller::ClassName();
            throw new UnknownClassException("return class '{$class}' not an instanceof a class '{$baseClass}'");
        }

        //基础属性
        $obj->actionName = $action;
        $obj->controllerName = $controller;
        $obj->httpContext = $this->httpContext;
        $obj->route = $this->route;

        //动作执行前
        $result = call_user_func_array([$obj, 'OnBeforeAction'], [$action]);
        if (null !== $result)
        {
            //拦截
            return $result;
        }

        //动作参数
        $actionParams = [];
        $routes = $this->route->GetRouteAll();
        $querys = $this->httpContext->request->GetQueryAll();

        $reflectionMethod = $reflection->getMethod($method);
        if (!$reflectionMethod->isPublic())
        {
            //方法不是公开的
            throw new UnknownMethodException("class '{$class}' method '{$method}' not public");
        }

        $reflectionParameters = $reflectionMethod->getParameters();
        foreach ($reflectionParameters as $reflectionParameter)
        {
            //获取参数名称和类型
            $parameterName = $reflectionParameter->getName();
            $parameterType = (string)$reflectionParameter->getType();

            //获取参数值
            $value = isset($pars[$parameterName]) ? $pars[$parameterName] : null;

            //从路由和GET参数填充
            if (null === $value) $value = isset($routes[$parameterName]) ? $routes[$parameterName] : null;
            if (null === $value) $value = isset($querys[$parameterName]) ? $querys[$parameterName] : null;
            if (null === $value && $reflectionParameter->isDefaultValueAvailable())
            {
                //使用默认值
                $value = $reflectionParameter->getDefaultValue();
                $actionParams[$parameterName] = $value;
            }
            else if(null !== $value)
            {
                if ('' === $parameterType)
                {
                    //通用型
                    $actionParams[$parameterName] = $value;
                }
                else if ('Throwable' === $parameterType)
                {
                    if (!($value instanceof \Throwable))
                    {
                        //不是异常类型
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no Throwable");
                    }
                    $actionParams[$parameterName] = $value;
                }
                else if('string' === $parameterType)
                {
                    if (!is_string($value))
                    {
                        //不是字符串
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no string");
                    }
                    $actionParams[$parameterName] = $value;
                }
                else if ('array' === $parameterType)
                {
                    if (!is_array($value))
                    {
                        //不是数组
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no array");
                    }
                    $actionParams[$parameterName] = $value;
                }
                else if ('int' === $parameterType)
                {
                    if (!is_int($value) && !(is_string($value) && 0 !== preg_match('/^[+-]?([0-9]+)$/', $value)))
                    {
                        //不是整数
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no int");
                    }
                    $actionParams[$parameterName] = (int)$value;
                }
                else if ('float' === $parameterType)
                {
                    if (!is_float($value) && !(is_string($value) && 0 !== preg_match('/^[+-]?([0-9]+|[0-9]+[\.][0-9]+)$/', $value)))
                    {
                        //不是小数
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no float");
                    }
                    $actionParams[$parameterName] = (float)$value;
                }
                else if ('bool' === $parameterType)
                {
                    if (!is_bool($value) && !(is_string($value) && 0 !== preg_match('/^(true|false|[01])$/', $value)))
                    {
                        //不是布尔型
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no bool");
                    }
                    $actionParams[$parameterName] = 'false' === $value ? false : ('true' === $value ? true : ('1' === $value ? true : false));
                }
            }
            else
            {
                //参数不存在
                throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' not exist");
            }
        }

        $result = call_user_func_array([$obj, $method], $actionParams);
        $result = call_user_func_array([$obj, 'OnAfterAction'], [$action, $result]);
        return $result;
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
        if (preg_match('/^text\/.*/i', $contentType) || preg_match('/^application\/(javascript|json)$/i'))
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
        if (preg_match('/^text\/.*/i', $contentType) || preg_match('/^application\/(javascript|json)$/i'))
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
            if (preg_match('/^text\/.*/i', $contentType) || preg_match('/^application\/(javascript|json)$/i'))
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
