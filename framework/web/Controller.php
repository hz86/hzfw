<?php

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
     * 视图
     * @param string $viewName 视图名称或路径
     * @param mixed $model
     * @throws \ReflectionException
     * @throws \hzfw\core\UnknownClassException
     * @throws \hzfw\core\UnknownParameterException
     */
    public function View(string $viewName = '', $model = null)
    {
        $view = $this->httpContext->requestServices->GetService(View::ClassName());
        $view->dirName = $this->controllerName;
        $view->fileName = $this->actionName;
        $this->Html($view->View($viewName, $model));
    }

    /**
     * 视图
     * @param string $viewName 视图名称或路径
     * @param mixed $model
     * @throws \ReflectionException
     * @throws \hzfw\core\UnknownClassException
     * @throws \hzfw\core\UnknownParameterException
     */
    public function ViewPartial(string $viewName = '', $model = null)
    {
        $view = $this->httpContext->requestServices->GetService(View::ClassName());
        $view->dirName = $this->controllerName;
        $view->fileName = $this->actionName;
        $this->Html($view->ViewPartial($viewName, $model));
    }

    /**
     * 返回JSON
     * @param mixed $content 内容
     * @param int $options json格式配置
     */
    public function Json($content, int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    {
        $httpContext = $this->httpContext;
        $response = $httpContext->response;

        $response->SetContent(json_encode($content, $options));
        $response->SetContentType('application/json');

        $stream = $response->GetContentStream();
        if (null !== $stream)
        {
            $response->SetContentStream(null);
            $stream->Dispose();
            unset($stream);
        }
    }

    /**
     * 返回文本
     * @param string $content 内容
     */
    public function Text(string $content)
    {
        $this->File($content, 'text/plain');
    }

    /**
     * 返回HTML
     * @param string $content 内容
     */
    public function Html(string $content)
    {
        $this->File($content, 'text/html');
    }

    /**
     * 返回文件
     * @param string $content 内容
     * @param string $contentType（默认application/octet-stream）
     */
    public function File(string $content, ?string $contentType = null)
    {
        $httpContext = $this->httpContext;
        $response = $httpContext->response;

        if (null === $contentType) {
            $contentType = 'application/octet-stream';
        }

        $response->SetContent($content);
        $response->SetContentType($contentType);

        $stream = $response->GetContentStream();
        if (null !== $stream)
        {
            $response->SetContentStream(null);
            $stream->Dispose();
            unset($stream);
        }
    }

    /**
     * 返回文件
     * @param FileStream $stream
     * @param string $contentType 文件类型（默认application/octet-stream）
     */
    public function FileStream(FileStream $stream, ?string $contentType = null)
    {
        $httpContext = $this->httpContext;
        $response = $httpContext->response;

        if (null === $contentType) {
            $contentType = 'application/octet-stream';
        }

        $response->SetContentStream($stream);
        $response->SetContentType($contentType);
        $response->SetContent('');
    }

    /**
     * 重定向
     * @param string $url
     * @param int $statusCode
     */
    public function Redirect(string $url, int $statusCode = 302)
    {
        $this->httpContext->response->Redirect($url, $statusCode);
    }

    /**
     * 重定向
     * @param string $routeName
     * @param array $params
     * @param int $statusCode
     * @throws HttpException
     */
    public function RedirectRoute(string $routeName, array $params = [], int $statusCode = 302)
    {
        $url = $this->route->CreateAbsoluteUrl($routeName, $params);
        $this->httpContext->response->Redirect($url, $statusCode);
    }

    /**
     * StatusCode 200
     */
    public function Ok()
    {
        $this->httpContext->response->SetStatusCode(200);
    }

    /**
     * StatusCode 400
     */
    public function BadRequest()
    {
        $this->httpContext->response->SetStatusCode(400);
    }

    /**
     * StatusCode 404
     */
    public function NotFound()
    {
        $this->httpContext->response->SetStatusCode(404);
    }

    /**
     * StatusCode 500
     */
    public function ServerError()
    {
        $this->httpContext->response->SetStatusCode(500);
    }
}
