<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\base\FileStream;

/**
 * 控制器
 * Class Controller
 * @package hzfw\web
 */
class Controller extends BaseObject
{
    /**
     * 控制器名称
     * @var string
     */
    public string $controllerName;

    /**
     * 动作名称
     * @var string
     */
    public string $actionName;

    /**
     * 上下文
     * @var HttpContext
     */
    public HttpContext $httpContext;

    /**
     * 路由
     * @var Route
     */
    public Route $route;

    /**
     * 执行动作前
     * @param string $action
     * @return ActionResult 返回非null则拦截
     */
    public function OnBeforeAction(string $action): ?ActionResult
    {
        return null;
    }

    /**
     * 执行动作后
     * @param string $action
     * @param ActionResult $result
     * @return ActionResult
     */
    public function OnAfterAction(string $action, ActionResult $result): ActionResult
    {
        return $result;
    }

    /**
     * 视图
     * @param string $viewName 视图名称或路径
     * @param mixed $model
     * @return ActionResult
     * @throws \ReflectionException
     * @throws \hzfw\core\UnknownClassException
     * @throws \hzfw\core\UnknownParameterException
     */
    public function View(string $viewName = '', $model = null): ActionResult
    {
        $view = $this->httpContext->requestServices->GetService(View::ClassName());
        {
            $view->fileName = $this->actionName;
            $view->dirName = $this->controllerName;
        }
        return $this->Html($view->View($viewName, $model));
    }

    /**
     * 视图
     * @param string $viewName 视图名称或路径
     * @param mixed $model
     * @return ActionResult
     * @throws \ReflectionException
     * @throws \hzfw\core\UnknownClassException
     * @throws \hzfw\core\UnknownParameterException
     */
    public function ViewPartial(string $viewName = '', $model = null): ActionResult
    {
        $view = $this->httpContext->requestServices->GetService(View::ClassName());
        {
            $view->fileName = $this->actionName;
            $view->dirName = $this->controllerName;
        }
        return $this->Html($view->ViewPartial($viewName, $model));
    }

    /**
     * 返回JSON
     * @param mixed $content 内容
     * @param int $options json格式配置
     * @return ActionResult
     * @throws \Exception
     */
    public function Json($content, int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): ActionResult
    {
        return $this->File(json_encode($content, $options), 'application/json');
    }

    /**
     * 返回文本
     * @param string $content 内容
     * @return ActionResult
     * @throws \Exception
     */
    public function Text(string $content): ActionResult
    {
        return $this->File($content, 'text/plain');
    }

    /**
     * 返回HTML
     * @param string $content 内容
     * @return ActionResult
     * @throws \Exception
     */
    public function Html(string $content): ActionResult
    {
        return $this->File($content, 'text/html');
    }

    /**
     * 返回文件
     * @param string $content 内容
     * @param string $contentType（默认application /octet-stream）
     * @return FileResult
     * @throws \Exception
     */
    public function File(string $content, ?string $contentType = null): FileResult
    {
        return new FileResult($content, $contentType);
    }

    /**
     * 返回文件
     * @param FileStream $stream
     * @param string $contentType 文件类型（默认application/octet-stream）
     * @return FileStreamResult
     */
    public function FileStream(FileStream $stream, ?string $contentType = null): FileStreamResult
    {
        return new FileStreamResult($stream, $contentType);
    }

    /**
     * 重定向
     * @param string $url
     * @param int $statusCode
     * @return RedirectResult
     */
    public function Redirect(string $url, int $statusCode = 302): RedirectResult
    {
        return new RedirectResult($url, $statusCode);
    }

    /**
     * 重定向
     * @param string $routeName
     * @param array $params
     * @param int $statusCode
     * @return RedirectResult
     * @throws HttpException
     */
    public function RedirectRoute(string $routeName, array $params = [], int $statusCode = 302): RedirectResult
    {
        $url = $this->route->CreateAbsoluteUrl($routeName, $params);
        return new RedirectResult($url, $statusCode);
    }

    /**
     * StatusCode 200
     */
    public function Ok(): StatusCodeResult
    {
        return new StatusCodeResult(200);
    }

    /**
     * StatusCode 400
     */
    public function BadRequest(): StatusCodeResult
    {
        return new StatusCodeResult(400);
    }

    /**
     * StatusCode 404
     */
    public function NotFound(): StatusCodeResult
    {
        return new StatusCodeResult(404);
    }

    /**
     * StatusCode 500
     */
    public function ServerError(): StatusCodeResult
    {
        return new StatusCodeResult(500);
    }
}
