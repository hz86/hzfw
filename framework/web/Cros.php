<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * 跨域
 * Class Cros
 * @package hzfw\web
 */
class Cros extends BaseObject
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
     * 初始化
     * {
     *   "Cros": {
     *     "Default": {
     *       "WithOrigins": ["*"],
     *       "WithHeaders": ["*"],
     *       "WithMethods": ["*"],
     *       "Credentials": true
     *     }
     *   }
     * }
     */
    public function __construct(Config $config, HttpContext $httpContext)
    {
        $this->config = $config;
        $this->httpContext = $httpContext;
    }
    
    /**
     * 跨域设置
     * @param string $name
     */
    public function Run(string $name = 'Default'): ?ActionResult
    {
        $request = $this->httpContext->request;
        $response = $this->httpContext->response;
        $config = $this->config->Cros->$name;
        
        // 来源
        $origin = $request->GetHeader('Origin', null);
        if (null === $origin)
        {
            return null;
        }
        
        // 是否允许跨域
        $allowOrigin = '';
        foreach ($config->WithOrigins as $withOrigin)
        {
            if ('*' === $withOrigin)
            {
                // 任意
                $allowOrigin = $origin;
                break;
            }
            
            // 通配符处理
            $withOrigin = preg_quote($withOrigin);
            $withOrigin = str_replace('\*', '.+', $withOrigin);
            
            // 匹配列表
            if (0 !== preg_match('#^' . $withOrigin . '$#i', $origin))
            {
                $allowOrigin = $origin;
                break;
            }
        }
        
        // 默认跨域设置
        if ('' !== $allowOrigin)
        {
            $response->AddHeader('Access-Control-Allow-Origin', $allowOrigin);
            if (true === $config->Credentials) $response->AddHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        // 预检
        if ($request->GetMethod() === 'OPTIONS')
        {
            // 获取请求头
            $accessControlRequestMethod = $request->GetHeader('Access-Control-Request-Method', null);
            $accessControlRequestHeaders = $request->GetHeader('Access-Control-Request-Headers', null);
            if (null === $accessControlRequestMethod && null === $accessControlRequestHeaders)
            {
                return null;
            }
            
            if ('' !== $allowOrigin)
            {
                // 检查方法
                if (null !== $accessControlRequestMethod)
                {
                    // 允许的方法
                    $allowMethods = '';
                    foreach ($config->WithMethods as $withMethod)
                    {
                        if ('*' === $withMethod)
                        {
                            // 任意
                            $allowMethods .= ', ' . $accessControlRequestMethod;
                            break;
                        }
                        
                        if (0 === strcmp($accessControlRequestMethod, $withMethod))
                        {
                            // 列表允许值
                            $allowMethods .= ', ' . $accessControlRequestMethod;
                            break;
                        }
                    }
                    
                    if ('' !== $allowMethods)
                    {
                        // 删除多余字符
                        $allowMethods = substr($allowMethods, 2);
                    }
                    
                    if ('' !== $allowMethods)
                    {
                        // 允许的方法
                        $response->AddHeader('Access-Control-Allow-Methods', $allowMethods);
                    }
                }
                
                // 检域头
                if (null !== $accessControlRequestHeaders)
                {
                    // 允许的头
                    $allowHeaders = '';
                    
                    // 分割头 X-PINGOTHER, Content-Type
                    foreach (explode(',', $accessControlRequestHeaders) as $requestHeader)
                    {
                        $requestHeader = trim($requestHeader);
                        foreach ($config->WithHeaders as $withHeader)
                        {
                            if ('*' === $withHeader)
                            {
                                // 任意
                                $allowHeaders .= ', ' . $requestHeader;
                                break;
                            }
                            
                            if (0 === strcasecmp($requestHeader, $withHeader))
                            {
                                // 列表允许值
                                $allowHeaders .= ', ' . $requestHeader;
                                break;
                            }
                        }
                    }
                    
                    if ('' !== $allowHeaders)
                    {
                        // 删除多余字符
                        $allowHeaders = substr($allowHeaders, 2);
                    }
                    
                    if ('' !== $allowHeaders)
                    {
                        // 允许的头
                        $response->AddHeader('Access-Control-Allow-Headers', $allowHeaders);
                    }
                }
            }
            
            // 拦截后续执行
            $response->AddHeader('Access-Control-Max-Age', '86400');
            return new StatusCodeResult(204);
        }
        
        return null;
    }
}
