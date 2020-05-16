<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * Csrf攻击防护
 * Class Csrf
 * @package hzfw\web
 */
class Csrf extends BaseObject
{
    /**
     * HTTP上下文
     * @var HttpContext
     */
    private HttpContext $httpContext;
    
    /**
     * Cookie域
     * @var string
     */
    private string $cookieDomain = '';
    
    /**
     * Cookie名
     * @var string
     */
    private string $cookieName = '';
    
    /**
     * Csrf值
     * @var string|null
     */
    private ?string $csrfValue = null;
    
    /**
     * 初始化
     * {
     *   "Csrf": {
     *     "CookieName": "_csrf",
     *     "CookieDomain": ""
     *   }
     * }
     */
    public function __construct(Config $config, HttpContext $httpContext)
    {
        $this->httpContext = $httpContext;
        $this->cookieName = $config->Csrf->CookieName;
        $this->cookieDomain = $config->Csrf->CookieDomain;
        $this->csrfValue = $httpContext->request->GetCookie($this->cookieName, null);
    }
    
    /**
     * 获取Token
     * @return string
     */
    public function GetToken(): string
    {
        if(null === $this->csrfValue)
        {
            $this->csrfValue = sha1(uniqid((string)mt_rand(), true));
            $this->httpContext->response->AddCookie($this->cookieName, $this->csrfValue, [
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'domain' => '' === $this->cookieDomain ? null : $this->cookieDomain
            ]);
        }
        
        $randValue = substr(sha1(uniqid((string)mt_rand(), true)), 0, 16);
        return $randValue . sha1($randValue . $this->csrfValue);
    }
    
    /**
     * 验证Token
     * @param string $token
     * @return bool
     */
    public function ValidateToken(string $token): bool
    {
        $result = false;
        
        if(56 === strlen($token))
        {
            if (null !== $this->csrfValue)
            {
                $randValue = substr($token, 0, 16);
                $result = sha1($randValue . $this->csrfValue) === substr($token, 16);
            }
        }
        
        return $result;
    }
}
