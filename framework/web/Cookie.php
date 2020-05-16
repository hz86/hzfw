<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * Class Cookie
 * @package hzfw\web
 */
class Cookie extends BaseObject
{
    /**
     * 名称
     * @var string
     */
    public string $name = '';
    
    /**
     * 值
     * @var string|null
     */
    public ?string $value = null;
    
    /**
     * 域
     * @var string|null
     */
    public ?string $domain = null;
    
    /**
     * 路径
     * @var string|null
     */
    public ?string $path = null;
    
    /**
     * 超时（时间戳秒）
     * @var integer|null
     */
    public ?int $expires = null;
    
    /**
     * 必须HTTPS
     * @var bool|null
     */
    public ?bool $secure = null;
    
    /**
     * 只能HTTP传输，禁止js读取
     * @var bool|null
     */
    public ?bool $httponly = null;

    /**
     * 限制第三方访问
     * @var string|null Strict|Lax|None
     */
    public ?string $samesite = null;

    /**
     * 初始化
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $samesite
     */
    public function __construct(string $name, ?string $value = null, ?int $expires = null, 
        ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httponly = null, ?string $samesite = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->path = $path;
        $this->expires = $expires;
        $this->secure = $secure;
        $this->httponly = $httponly;
        $this->samesite = $samesite;
    }
}
