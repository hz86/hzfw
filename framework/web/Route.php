<?php

namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * 路由
 * Class Route
 * @package hzfw\web
 */
class Route extends BaseObject
{
    /**
     * 是否https
     * @var bool
     */
    private bool $isHttps = false;
    
    /**
     * 基础地址
     * @var string
     */
    private string $baseUrl = '';
    
    /**
     * 规则
     * @var array
     */
    private array $rules = [];
    
    /**
     * 路由名称
     * @var string
     */
    private string $routeName = '';
    
    /**
     * 控制器名称
     * @var string
     */
    private string $controllerName = '';
    
    /**
     * 动作名称
     * @var string
     */
    private string $actionName = '';
    
    /**
     * 路由参数
     * @var array
     */
    private array $routeParams = [];

    /**
     * 初始化
     * {
     *   "Route": {
     *     "Rules": [
     *       {
     *         "Name": "Index",
     *         "Template": "",
     *         "Defaults": {
     *           "Controller": "Site",
     *           "Action": "Index"
     *         }
     *       },
     *       {
     *         "Name": "Test1",
     *         "Template": "test1/<name>/",
     *         "Defaults": {
     *           "Controller": "Site",
     *           "Action": "Test1"
     *         }
     *       },
     *       {
     *         "Name": "Test2",
     *         "Template": "test2/<id:\\d+>/",
     *         "Defaults": {
     *           "Controller": "Site",
     *           "Action": "Test2"
     *         }
     *       },
     *       {
     *         "Name": "Default",
     *         "Template": "<Controller>/<Action>",
     *         "Defaults": {}
     *       }
     *     ]
     *   }
     * }
     * @param Config $config
     * @param HttpContext $httpContext
     * @throws HttpException
     */
    public function __construct(Config $config, HttpContext $httpContext)
    {
        //获取URL
        $this->isHttps = $httpContext->request->IsHttps();
        $this->baseUrl = $httpContext->request->GetBaseUrl();
        $url = $httpContext->request->GetCurrenUrl();
        
        //忽略 ?
        $find = strpos($url, '?');
        if (false !== $find) $url = substr($url, 0, $find);
        
        //生成路由规则
        foreach ($config->Route->Rules as $rule)
        {
            //解析路由
            $this->rules[$rule->Name] = [
                'Template' => $rule->Template, 
                'RegexTemplate' => $this->CreateRegexTemplate($rule->Template), 
                'Defaults' => []
            ];
            
            //路由规则出错
            if (null === $this->rules[$rule->Name]['RegexTemplate']) {
                throw new HttpException(500, "route rule '{$rule->Name}' error");
            }
            
            //路由默认参数
            foreach ($rule->Defaults as $n => $v) {
                $this->rules[$rule->Name]['Defaults'][$n] = $v;
            }
        }
        
        //匹配路由
        $matches = null;
        foreach ($this->rules as $name => $value)
        {
            if (preg_match('#' . $value['RegexTemplate'] . '#', $url, $matches) > 0)
            {
                //控制器和动作名称
                $defaultActionName = isset($value['Defaults']['Action']) ? $value['Defaults']['Action'] : null;
                $defaultControllerName = isset($value['Defaults']['Controller']) ? $value['Defaults']['Controller'] : null;
                $controllerName = isset($matches['Controller']) ? $matches['Controller'] : $defaultControllerName;
                $actionName = isset($matches['Action']) ? $matches['Action'] : $defaultActionName;
                
                //路由规则出错
                if (null === $controllerName || null === $actionName) {
                    throw new HttpException(500, "route rule '{$name}' error");
                }
                
                //路由参数
                foreach ($matches as $n => $v) {
                    if (is_string($n)) $this->routeParams[$n] = rawurldecode($v);
                }
                
                //命名转换
                $controllerName = $this->ToPascal($controllerName);
                $actionName = $this->ToPascal($actionName);
                
                $this->routeName = $name;
                $this->controllerName = $controllerName;
                $this->actionName = $actionName;
                break;
            }
        }
    }
    
    /**
     * 获取控制器名称
     * @return string
     */
    public function GetControllerName(): string
    {
        return $this->controllerName;
    }
    
    /**
     * 获取动作名称
     * @return string
     */
    public function GetActionName(): string
    {
        return $this->actionName;
    }
    
    /**
     * 获取路由参数
     * @param string $name
     * @param string $defaultValue
     * @return string|NULL
     */
    public function GetRoute(string $name, ?string $defaultValue = null): ?string
    {
        return (isset($this->routeParams[$name]) ? $this->routeParams[$name] : $defaultValue);
    }
    
    /**
     * 获取所有路由参数
     * @return array
     */
    public function GetRouteAll(): array
    {
        return $this->routeParams;
    }

    /**
     * 创建URL
     * @param string $routeName
     * @param array $params ['name' => 'value', ...]
     * @return string
     * @throws HttpException
     */
    public function CreateUrl(string $routeName, array $params = []): string
    {
        if (!isset($this->rules[$routeName])) {
            throw new HttpException(500, "invalid route name '{$routeName}'");
        }
        
        $template = $this->rules[$routeName]['Template'];
        $defaults = $this->rules[$routeName]['Defaults'];
        
        $url = preg_replace_callback('#\<(.+?)\>#', function(array $matches) 
            use($routeName, $defaults, &$params): string 
        {
            preg_match('#^(.+?)(?:\:(.*)$|$)#', $matches[1], $matches);
            $check = isset($matches[2]) ? $matches[2] : '.+?';
            
            $name = $matches[1];
            $value = isset($params[$name]) ? $params[$name] : null;
            
            if (null !== $value) unset($params[$name]);
            if (null === $value) $value = isset($defaults[$name]) ? $defaults[$name] : null;
            if (null === $value) throw new HttpException(500, "route name '{$routeName}' parameter '{$name}' is invalid"); 
            if (0 === preg_match('#^'.$check.'$#', $value)) throw new HttpException(500, "route name '{$routeName}' parameter '{$name}' is invalid"); 
            
            if ('Controller' === $name || 'Action' === $name) {
                $value = $this->ToLower($value);
            }
            
            return rawurlencode($value);
        }, $template);
        
        if (0 !== strncasecmp($url, 'http://', 7) && 0 !== strncasecmp($url, 'https://', 8)) {
            if (0 !== strncmp($url, '/', 1)) $url = '/' . $url;
        }
        
        $query = '';
        foreach ($params as $name => $value) {
            if ('#' !== $name) $query .= '&' . urlencode($name) . '=' . urlencode($value);
        }
        if ('' !== $query && false === strpos($url, '?')) {
            $query[0] = '?';
        }
        
        $fragment = isset($params['#']) ? $params['#'] : null;
        return $url . $query . (null === $fragment ? '' : "#{$fragment}");
    }

    /**
     * 创建URL
     * @param string $routeName
     * @param array $params ['name' => 'value', ...]
     * @return string
     * @throws HttpException
     */
    public function CreateAbsoluteUrl(string $routeName, array $params = []): string
    {
        $url = $this->CreateUrl($routeName, $params);
        
        if (0 !== strncasecmp($url, 'http://', 7) && 0 !== strncasecmp($url, 'https://', 8))
        {
            if (0 === strncmp($url, '//', 2)) {
                $url = ($this->isHttps ? 'https:' : 'http:') . $url;
            }
            else
            {
                if (0 !== strncmp($url, '/', 1)) $url = '/' . $url;
                $url = $this->baseUrl . $url;;
            }
        }
        
        return $url;
    }
    
    //解析模板
    private function CreateRegexTemplate(string $template): ?string
    {
        $result = [];
        $resultRegex = null;
        $templateLength = strlen($template);
        
        $offset = 0;
        $brackets = false;
        while (true)
        {
            if(false !== $brackets)
            {
                //括号内
                $find = strpos($template, '>', $offset);
                if(false === $find)
                {
                    $result = null;
                    break;
                }
                
                //解析变量
                $matches = [];
                $variable = substr($template, $offset, $find - $offset);
                if(0 === preg_match('#^(.+?)(?:\:(.*)$|$)#', $variable, $matches))
                {
                    $result = null;
                    break;
                }
                
                $result[] = [
                    'Name' => $matches[1], 
                    'Value' => isset($matches[2]) ? $matches[2] : '',
                ];
                
                $offset = $find + 1;
                $brackets = false;
            }
            else
            {
                //非括号部分
                $find = strpos($template, '<', $offset);
                if(false === $find)
                {
                    if($templateLength > $offset)
                    {
                        $result[] = [
                            'Name' => '', 
                            'Value' => substr($template, $offset),
                        ];
                    }
                    break;
                }
                else
                {
                    if($find - $offset > 0)
                    {
                        $result[] = [
                            'Name' => '',
                            'Value' => substr($template, $offset, $find - $offset),
                        ];
                    }
                    $offset = $find + 1;
                    $brackets = true;
                }
            }
        }
        
        if (null !== $result)
        {
            //生成正则
            $resultRegex = '^';
            
            //是否需要补全模板
            if (0 !== strncasecmp($template, 'http://', 7) && 0 !== strncasecmp($template, 'https://', 8))
            {
                //补全http或https
                if (0 === strncmp($template, '//', 2)) {
                    $resultRegex .= ($this->isHttps ? 'https\:' : 'http\:');
                }
                else
                {
                    //补全地址
                    $resultRegex .= preg_quote($this->baseUrl);
                    if (0 !== strncmp($template, '/', 1)) {
                        $resultRegex .= '/';
                    }
                }
            }
            
            foreach ($result as $v)
            {
                if ('' === $v['Name'])
                {
                    //直接转义
                    $resultRegex .= preg_quote($v['Value']);
                }
                else
                {
                    //获取参数名称和值
                    $resultRegex .= '(?<'.preg_quote($v['Name']).'>'.('' === $v['Value'] ? '.+?' : $v['Value']).')';
                }
            }
            
            $resultRegex .= '$';
        }
        
        return $resultRegex;
    }
    
    //SiteIndex = site-index
    private function ToLower(string $str): string
    {
        $result = '';
        $len = strlen($str);
        
        $c1 = ord('a');
        $c2 = ord('A');
        $c3 = ord('Z');
        
        for ($i = 0; $i < $len; $i++)
        {
            $c = ord($str[$i]);
            if ($c >= $c2 && $c <= $c3)
            {
                if (0 !== $i) $result .= '-';
                $result .= chr($c + ($c1 - $c2));
            }
            else
            {
                $result .= chr($c);
            }
        }
        
        return $result;
    }
    
    //site-index = SiteIndex
    private function ToPascal(string $str): string
    {
        $result = '';
        $upper = true;
        $len = strlen($str);
        
        $c1 = ord('a');
        $c2 = ord('A');
        $c3 = ord('z');
        $c4 = ord('-');
        
        for ($i = 0; $i < $len; $i++)
        {
            $c = ord($str[$i]);
            if (!$upper && $c4 === $c)
            {
                $upper = true;
            }
            else if (false !== $upper && $c >= $c1 && $c <= $c3)
            {
                $result .= chr(($c - ($c1 - $c2)));
                $upper = false;
            }
            else
            {
                $result .= chr($c);
                $upper = false;
            }
        }
        
        return $result;
    }
}
