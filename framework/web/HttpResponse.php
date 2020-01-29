<?php

namespace hzfw\web;
use hzfw\core\BaseObject;
use hzfw\base\FileStream;

/**
 * 响应
 * Class HttpResponse
 * @package hzfw\web
 */
class HttpResponse extends BaseObject
{
    /**
     * 状态码
     * @var integer
     */
    private int $statusCode = 200;
    
    /**
     * 状态文本
     * @var string
     */
    private string $statusText = 'OK';
    
    /**
     * 协议版本
     * @var string
     */
    private string $version = 'HTTP/1.1';
    
    /**
     * 内容
     * @var string
     */
    private string $content = '';
    
    /**
     * 内容类型
     * @var string
     */
    private string $contentType = 'text/html';
    
    /**
     * 内容编码
     * @var string
     */
    private string $contentCharset = '';
    
    /**
     * 内容流
     * @var FileStream|null
     */
    private ?FileStream $contentStream = null;
    
    /**
     * 内容范围
     * [begin,end]
     * @var array|null
     */
    private ?array $contentRange = null;
    
    /**
     * 响应头
     * @var array
     */
    private array $header = [];
    
    /**
     * Cookie
     * @var array
     */
    private array $cookie = [];
    
    /**
     * @var array list of HTTP status
     */
    public static array $httpStatus = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * HTTP响应
     * @param Config $config
     * @param HttpRequest $request
     */
    public function __construct(Config $config, HttpRequest $request)
    {
        $this->SetVersion($request->GetVersion());
        $this->SetContentRange($request->GetRange());
        $this->SetContentCharset($config->Mvc->Charset);
    }
    
    /**
     * 获取状态码
     * @return int
     */
    public function GetStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * 设置状态码
     * @param int $statusCode
     * @param string $statusText
     */
    public function SetStatusCode(int $statusCode, ?string $statusText = null): void
    {
        $this->statusCode = $statusCode;
        $this->statusText = null === $statusText ? (isset(self::$httpStatus[$statusCode]) ? self::$httpStatus[$statusCode] : '') : $statusText;
    }
    
    /**
     * 获取状态文本
     * @return string
     */
    public function GetStatusText(): string
    {
        return $this->statusText;
    }
    
    /**
     * 设置状态文本
     * @param string $statusText
     */
    public function SetStatusText(string $statusText): void
    {
        $this->statusText = $statusText;
    }
    
    /**
     * 获取协议版本
     * @return string
     */
    public function GetVersion(): string
    {
        return $this->version;
    }
    
    /**
     * 设置协议版本
     * @param string $version
     */
    public function SetVersion(string $version): void
    {
        $this->version = $version;
    }
    
    /**
     * 获取内容类型
     */
    public function GetContentType(): string
    {
        return $this->contentType;
    }
    
    /**
     * 设置内容类型
     * @param string $contentType
     */
    public function SetContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }
    
    /**
     * 获取内容编码
     */
    public function GetContentCharset(): string
    {
        return $this->contentCharset;
    }
    
    /**
     * 设置内容编码
     * @param string $contentCharset
     */
    public function SetContentCharset(string $contentCharset): void
    {
        $this->contentCharset = $contentCharset;
    }
    
    /**
     * 获取内容
     */
    public function GetContent(): string
    {
        return $this->content;
    }
    
    /**
     * 设置内容
     * @param string $content
     */
    public function SetContent(string $content): void
    {
        $this->content = $content;
    }
    
    /**
     * 获取内容流
     */
    public function GetContentStream(): ?FileStream
    {
        return $this->contentStream;
    }
    
    /**
     * 设置内容流
     * @param FileStream $contentStream
     */
    public function SetContentStream(?FileStream $contentStream): void
    {
        $this->contentStream = $contentStream;
    }
    
    /**
     * 获取内容范围
     * @return array [[begin,end],...]
     */
    public function GetContentRange(): ?array
    {
        return $this->contentRange;
    }
    
    /**
     * 设置内容范围
     * @param array $contentRange [[begin,end],...]
     */
    public function SetContentRange(?array $contentRange): void
    {
        $this->contentRange = $contentRange;
    }
    
    /**
     * 重定向
     * @param string $url
     * @param int $statusCode
     */
    public function Redirect(string $url, int $statusCode = 302): void
    {
        $this->SetStatusCode($statusCode);
        $this->AddHeader('Location', $url, true);
    }
    
    /**
     * 添加响应头
     * @param string $name
     * @param string $value
     * @param bool $replace
     */
    public function AddHeader(string $name, string $value, bool $replace = true): void
    {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
        
        if (true === $replace)
        {
            $this->header[$name] = $value;
        }
        else
        {
            if(array_key_exists($name, $this->header))
            {
                if(is_array($this->header[$name])) {
                    $this->header[$name][] = $value;
                }
                else
                {
                    $v = $this->header[$name];
                    $this->header[$name] = [$v, $value];
                }
            }
            else
            {
                $this->header[$name] = $value;
            }
        }
    }
    
    /**
     * 移除响应头
     * @param string $name
     * @param int $i 如果是数组则输入对应索引
     */
    public function RemoveHeader(string $name, ?int $i = null): void
    {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
        
        if (null === $i)
        {
            unset($this->header[$name]);
        }
        else
        {
            if (isset($this->header[$name]) && is_array($this->header[$name])) {
                unset($this->header[$name][$i]);
            }
        }
    }
    
    /**
     * 清除响应头
     */
    public function ClearHeader(): void
    {
        $this->header = [];
    }
    
    /**
     * 获取响应头
     */
    public function GetHeaderAll(): array
    {
        return $this->header;
    }
    
    /**
     * 添加COOKIE
     * @param string $name
     * @param string $value
     * @param array $opt
     * [
     *   "expires" => 0,
     *   "path" => "/",
     *   "domain" => ".",
     *   "secure" => false,
     *   "httponly" => false,
     *   "samesite" => "Lax"
     * ]
     */
    public function AddCookie(string $name, ?string $value = null, ?array $opt = null): void
    {
        $opt = array_merge([
            'expires' => null,
            'path' => null,
            'domain' => null,
            'secure' => null,
            'httponly' => null,
            'samesite' => null
        ], $opt);

        $domain = null === $opt['domain'] ? null : strtolower($opt['domain']);
        $this->cookie["{$domain}-{$opt['path']}-{$name}"] = new Cookie($name, $value,
            $opt['expires'], $opt['path'], $opt['domain'], $opt['secure'], $opt['httponly'], $opt['samesite']);
    }
    
    /**
     * 移除COOKIE
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function RemoveCookie(string $name, ?string $path = null, ?string $domain = null): void
    {
        $domain = null === $domain ? null : strtolower($domain);
        unset($this->cookie["{$domain}-{$path}-{$name}"]);
    }
    
    /**
     * 清除COOKIE
     */
    public function ClearCookie(): void
    {
        $this->cookie = [];
    }
    
    /**
     * 获取Cookie
     */
    public function GetCookieAll(): array
    {
        $result = [];
        foreach ($this->cookie as $value)
        {
            $result[] = $value;
        }
        
        return $result;
    }
}
