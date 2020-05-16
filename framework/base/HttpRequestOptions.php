<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * HTTP访问配置
 */
class HttpRequestOptions extends BaseObject
{
    /**
     * URL地址
     * @var string
     */
    public string $url;
    
    /**
     * 请求方法
     * @var string GET,POST,PUT,DELETE,...
     */
    public string $method = "GET";
    
    /**
     * 标识
     * @var string
     */
    public ?string $userAgent = null;
    
    /**
     * 提交数据
     * @var string
     */
    public ?string $data = null;
    
    /**
     * 请求头
     * @var array ['Accept-Language: zh-CN', ...]
     */
    public ?array $headers = null;
    
    /**
     * Cookie
     * @var array ['key'=>'val', 'key2'=>'val',...]
     */
    public ?array $cookies = null;
    
    /**
     * 请求超时，毫秒
     * @var integer
     */
    public int $timeout = 30000;
    
    /**
     * 连接超时，毫秒
     * @var integer
     */
    public int $connectTimeout = 3000;
    
    /**
     * 重定向
     * @var boolean
     */
    public bool $location = true;
    
    /**
     * 压缩响应数据
     * @var boolean
     */
    public bool $encoding = true;
    
    /**
     * 代理 http://user:pass@ip:port, sock5://user:pass@ip:port
     * @var string
     */
    public ?string $proxy = null;
    
    /**
     * 认证用户
     * @var string
     */
    public ?string $authUser = null;
    
    /**
     * 认证密码
     * @var string
     */
    public ?string $authPasswd = null;
    
    /**
     * 根证书PEM格式
     * @var string
     */
    public ?string $sslCa = null;
    
    /**
     * 公钥PEM格式
     * @var string
     */
    public ?string $sslCert = null;
    
    /**
     * 公钥密码
     * @var string
     */
    public ?string $sslCertPasswd = null;
    
    /**
     * 私钥PEM格式
     * @var string
     */
    public ?string $sslKey = null;
    
    /**
     * 私钥密码
     * @var string
     */
    public ?string $sslKeyPasswd = null;
    
    /**
     * SSL验证Host
     * @var boolean
     */
    public bool $sslVerifyhost = true;
}