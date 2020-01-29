<?php

namespace hzfw\web;
use hzfw\core\BaseObject;

class ViewComponent extends BaseObject
{
    /**
     * 部件名称
     * @var string
     */
    public string $componentName;
    
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
     * 运行
     */
    public function Run($model): string
    {
        return '';
    }

    /**
     * 视图
     * @param string $viewName 视图名称或路径
     * @param mixed $model
     * @return string
     * @throws \ReflectionException
     * @throws \hzfw\core\UnknownClassException
     * @throws \hzfw\core\UnknownParameterException
     */
    public function View(string $viewName = '', $model = null): string
    {
        $view = $this->httpContext->requestServices->GetService(View::ClassName());
        $view->dirName = '/Components/';
        $view->fileName = $this->componentName;
        return $view->ViewPartial($viewName, $model);
    }
}
