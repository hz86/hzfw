<?php

namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\core\UnknownClassException;
use hzfw\core\UnknownParameterException;
use hzfw;

//视图
class View extends BaseObject
{
    /**
     * 目录名称
     * @var string
     */
    public string $dirName = '';
    
    /**
     * 文件名称
     * @var string
     */
    public string $fileName = '';
    
	/**
	 * 标题
	 * @var string
	 */
	public string $title = '';
	
	/**
	 * 头
	 * @var string
	 */
	public string $head = '';
	
	/**
	 * 页开始
	 * @var string
	 */
	public string $beginPage = '';
	
	/**
	 * 页结束
	 * @var string
	 */
	public string $endPage = '';
	
	/**
	 * 正文开始
	 * @var string
	 */
	public string $beginBody = '';
	
	/**
	 * 正文结束
	 * @var string
	 */
	public string $endBody = '';

    /**
     * 配置
     * @var Config
     */
    private Config $config;

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
     * 初始化
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
	 * 视图
	 * @param string $viewName 视图名称或路径
	 * @param mixed $model
     * @return string
	 */
	public function View(string $viewName = '', $model = null): string
	{
	    $out = '';
	    ob_start();
	    ob_implicit_flush(0);
	    
	    extract(["content" => $this->ViewPartial($viewName, $model)], EXTR_OVERWRITE);
	    require(hzfw::$path . "/{$this->config->Mvc->ViewPath}/Layouts/Main.php");
	    
	    $out = ob_get_clean();
	    $out = false !== $out ? $out : '';
	    return $out;
	}
	
	/**
	 * 视图
	 * @param string $viewName 视图名称或路径
	 * @param mixed $model
     * @return string
	 */
	public function ViewPartial(string $viewName = '', $model = null): string
	{
	    if ('' === $viewName) {
	        $viewName = $this->fileName;
	    }
	    
	    if (0 !== strncmp($viewName, '/', 1)) {
	        $viewName = "/{$this->dirName}/{$viewName}";
	    }
	    
	    $out = '';
	    ob_start();
	    ob_implicit_flush(0);
	    
	    extract(["model" => $model], EXTR_OVERWRITE);
	    require(hzfw::$path . "/{$this->config->Mvc->ViewPath}{$viewName}.php");
	    
	    $out = ob_get_clean();
	    $out = false !== $out ? $out : '';
	    return $out;
	}

    /**
     * 小部件
     * @param string $componentName 小部件名称
     * @param mixed $model
     * @return string
     * @throws UnknownClassException
     * @throws UnknownParameterException
     * @throws \ReflectionException
     */
	public function ViewComponent(string $componentName = '', $model = null): string
	{
	    $namespace = $this->config->Mvc->ViewComponentNamespace;
        $class = "\\{$namespace}\\{$componentName}ViewComponent";
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
        if (!($obj instanceof ViewComponent))
        {
            $baseClass = ViewComponent::ClassName();
            throw new UnknownClassException("return class '{$class}' not an instanceof a class '{$baseClass}'");
        }

	    $obj->componentName = $componentName;
	    $obj->httpContext = $this->httpContext;
	    $obj->route = $this->route;

	    return call_user_func_array([$obj, 'Run'], ["model" => $model]);
	}
	
	/**
	 * 创建标签
	 * @param string $tag
	 * @param array $attr
	 * @param string $value
	 * @param bool $closure
	 * @param bool $valuehtml
	 * @return string
	 */
	public function createTag(string $tag, array $attr = [], string $value = '', bool $closure = false, bool $valuehtml = false): string
	{
		$encode = function(string $content): string {
		    return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, $this->config->Mvc->Charset, true);
		};
		
		$ret = '';
		$ret .= '<' . $encode($tag);
		
		foreach ($attr as $name => $val) {
			$ret .= ' ' . $encode($name) . '="' . $encode($val) . '"';
		}
		
		if(false === $closure) {
			$ret .= ' />';
		}
		else
		{
			$ret .= '>';
			$ret .= $valuehtml ? $value : $encode($value);
			$ret .= '</' . $encode($tag) . '>';
		}
		
		return $ret;
	}
}
