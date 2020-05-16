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

        $next();
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

        if (false === $reflection->hasMethod($method))
        {
            //方法不存在
            throw new UnknownMethodException("class '{$class}' method '{$method}' not exist");
        }

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
            $parameterType = $reflectionParameter->getType()->getName();

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
                    if (!(is_int($value) || (is_string($value) && 0 !== preg_match('/^[+-]?[0-9]+$/', $value))))
                    {
                        //不是整数
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no int");
                    }     
                    $actionParams[$parameterName] = (int)$value;
                }
                else if ('float' === $parameterType)
                {
                    if (!(is_float($value) || (is_string($value) && 0 !== preg_match('/^[+-]?([0-9]+|[0-9]+[\.][0-9]+)(E[+-]?[0-9]+)?$/i', $value))))
                    {
                        //不是小数
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no float");
                    }
                    $actionParams[$parameterName] = (float)$value;
                }
                else if ('bool' === $parameterType)
                {
                    if (!(is_bool($value) || (is_string($value) && 0 !== preg_match('/^(true|false|[01])$/', $value))))
                    {
                        //不是布尔型
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no bool");
                    }
                    $actionParams[$parameterName] = 'false' === $value ? false : ('true' === $value ? true : ('1' === $value ? true : false));
                }
                else if (is_object($value))
                {
                    $parameterClass = $reflectionParameter->getClass()->getName();
                    if (!($value instanceof $parameterClass))
                    {
                        //类型错误
                        throw new UnknownParameterException("class '{$class}' parameter '{$parameterName}' type no {$parameterType}");
                    }
                    $actionParams[$parameterName] = $value;
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
}
