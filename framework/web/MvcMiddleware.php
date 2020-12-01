<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\UnknownClassException;
use hzfw\core\UnknownMethodException;
use hzfw\core\UnknownParameterException;

/**
 * Mvc中间件
 * Class Middleware
 * @package hzfw\web
 */
class MvcMiddleware extends Middleware
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
     * 执行Mvc
     * @param HttpContext $httpContext
     * @param \Closure $next
     * @throws UnknownClassException
     * @throws UnknownMethodException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
    public function Run(HttpContext $httpContext, \Closure $next): void
    {
        $this->httpContext = $httpContext;
        $this->config = $httpContext->requestServices->GetService(Config::ClassName());
        $this->route = $httpContext->requestServices->GetService(Route::ClassName());

        $route = $this->route;
        $response = $this->httpContext->response;

        try
        {
            //捕获错误
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool
            {
                if (!(error_reporting() & $errno)) return false;
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            });

            //路由匹配失败，抛出404
            if ('' === $route->GetControllerName() || '' === $route->GetActionName()) {
                throw new HttpException(404, 'route matching failed');
            }

            //调用控制器
            $result = $this->CallAction(
                $this->config->Mvc->ControllerNamespace,
                $route->GetControllerName(),
                $route->GetActionName());

            //执行动作结果
            $result->ExecuteResult($this->httpContext);
        }
        catch (\Throwable $e)
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
            if($e instanceof HttpException) $response->SetStatusCode($e->getCode());

            list($controller, $action) = explode('/', $this->config->Mvc->Error);
            $result = $this->CallAction($this->config->Mvc->ControllerNamespace, $controller, $action, [
                'statusCode' => $response->GetStatusCode(), 'exception' => $e
            ]);

            $result->ExecuteResult($this->httpContext);
        }

        $next();
    }

    /**
     * 调用动作
     * @param string $namespace
     * @param string $controller
     * @param string $action
     * @param array $pars
     * @return ActionResult
     * @throws \ReflectionException
     * @throws HttpException
     */
    private function CallAction(string $namespace, string $controller, string $action, array $pars = []): ActionResult
    {
        $method = $action;
        $class = "\\{$namespace}\\{$controller}Controller";
        $reflection = null;

        try
        {
            $reflection = new \ReflectionClass($class);
        } 
        catch (UnknownClassException $e)
        {
            throw new HttpException(404, $e->getMessage(), $e);
        }

        //构造函数
        $constructorArgs = [];
        $reflectionMethod = $reflection->getConstructor();
        if (null !== $reflectionMethod)
        {
            //获取参数信息
            $reflectionParameters = $reflectionMethod->getParameters();
            foreach ($reflectionParameters as $reflectionParameter)
            {
                //获取类型
                $parameterType = $reflectionParameter->getType();
                if (null === $parameterType || false !== $parameterType->isBuiltin())
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    throw new HttpException(500, "class '{$class}' parameter '{$parameterName}' not class");
                }

                //获取对象
                $parameterClassName = $parameterType->getName();
                $parameterObj = $this->httpContext->requestServices->GetService($parameterClassName);
                if (null === $parameterObj)
                {
                    //获取失败
                    $parameterName = $reflectionParameter->getName();
                    throw new HttpException(500, "class '{$class}' parameter '{$parameterName}' type '{$parameterClassName}' no service added");
                }

                $constructorArgs[] = $parameterObj;
            }
        }

        //创建实例
        $obj = $reflection->newInstanceArgs($constructorArgs);
        if (!($obj instanceof Controller))
        {
            $baseClass = Controller::ClassName();
            throw new HttpException(500, "return class '{$class}' not an instanceof a class '{$baseClass}'");
        }

        //基础属性
        $obj->actionName = $action;
        $obj->controllerName = $controller;
        $obj->httpContext = $this->httpContext;
        $obj->route = $this->route;

        //动作参数
        $actionParams = [];
        $routes = $this->route->GetRouteAll();
        $querys = $this->httpContext->request->GetQueryAll();

        if (false === $reflection->hasMethod($method))
        {
            //方法不存在
            throw new HttpException(404, "class '{$class}' method '{$method}' not exist");
        }

        $reflectionMethod = $reflection->getMethod($method);
        if (!$reflectionMethod->isPublic())
        {
            //方法不是公开的
            throw new HttpException(404, "class '{$class}' method '{$method}' not public");
        }

        $reflectionParameters = $reflectionMethod->getParameters();
        foreach ($reflectionParameters as $reflectionParameter)
        {
            //获取参数名称和类型
            $parameterName = $reflectionParameter->getName();
            $parameterType = $reflectionParameter->getType();
            $parameterTypeName = null !== $parameterType ? $parameterType->getName() : '';

            //获取参数值
            $hasValue = array_key_exists($parameterName, $pars);
            $value = $hasValue ? $pars[$parameterName] : null;

            //从路由参数填充
            if (false === $hasValue)
            {
                $hasValue = array_key_exists($parameterName, $routes);
                $value = $hasValue ? $routes[$parameterName] : null;
            }

            //从GET参数填充
            if (false === $hasValue)
            {
                $hasValue = array_key_exists($parameterName, $querys);
                $value = $hasValue ? $querys[$parameterName] : null;
            }

            //是否默认值
            if (false === $hasValue && $reflectionParameter->isDefaultValueAvailable())
            {
                //使用默认值
                $value = $reflectionParameter->getDefaultValue();
                $actionParams[$parameterName] = $value;
            }
            else if(false !== $hasValue)
            {
                if ('' === $parameterTypeName)
                {
                    //通用型
                    $actionParams[$parameterName] = $value;
                }
                else if('string' === $parameterTypeName)
                {
                    if (!is_string($value))
                    {
                        //不是字符串
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no string");
                    }
                    $actionParams[$parameterName] = $value;
                }
                else if ('array' === $parameterTypeName)
                {
                    if (!is_array($value))
                    {
                        //不是数组
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no array");
                    }
                    $actionParams[$parameterName] = $value;
                }
                else if ('int' === $parameterTypeName)
                {
                    if (!(is_int($value) || (is_string($value) && 0 !== preg_match('/^[+-]?[0-9]+$/', $value))))
                    {
                        //不是整数
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no int");
                    }
                    $actionParams[$parameterName] = (int)$value;
                }
                else if ('float' === $parameterTypeName)
                {
                    if (!(is_float($value) || (is_string($value) && 0 !== preg_match('/^[+-]?([0-9]+|[0-9]+[\.][0-9]+)(E[+-]?[0-9]+)?$/i', $value))))
                    {
                        //不是小数
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no float");
                    }
                    $actionParams[$parameterName] = (float)$value;
                }
                else if ('bool' === $parameterTypeName)
                {
                    if (!(is_bool($value) || (is_string($value) && 0 !== preg_match('/^(true|false|TRUE|FALSE|[01])$/', $value))))
                    {
                        //不是布尔型
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no bool");
                    }
                    $actionParams[$parameterName] = ('true' === $value || 'TRUE' === $value || '1' === $value);
                }
                else if (is_object($value))
                {
                    $parameterClassName = $reflectionParameter->getType()->getName();
                    if (!($value instanceof $parameterClassName))
                    {
                        //类型错误
                        throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' type no {$parameterTypeName}");
                    }
                    $actionParams[$parameterName] = $value;
                }
            }
            else
            {
                //参数不存在
                throw new HttpException(404, "class '{$class}' parameter '{$parameterName}' not exist");
            }
        }

        // 动作执行上下文
        $context = new ActionExecuteContext();
        {
            $context->actionName = $action;
            $context->actionMethod = $method;
            $context->actionArguments = $actionParams;
            $context->controller = $obj;
        }

        // 执行
        $result = call_user_func([$obj, 'OnExecuteAction'], $context);
        return $result;
    }
}
