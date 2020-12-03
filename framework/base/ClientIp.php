<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;
use hzfw\web\HttpRequest;

/**
 * 获取客户IP
 * X-Forwarded-For: client, proxy1, proxy2
 * Class XForwardedFor
 * @package hzfw\base
 */
class ClientIp extends BaseObject
{
    /**
     * 白名单
     * @var array|null
     */
    private ?array $whiteList = null;
    
    /**
     * 请求信息
     * @var HttpRequest
     */
    private HttpRequest $httpRequest;
    
    /**
     * 初始化
     * @param HttpRequest $httpRequest
     * @param array|null $whiteList ["::1", "127.0.0.1"]
     */
    public function __construct (HttpRequest $httpRequest, ?array $whiteList = null)
    {
        $this->whiteList = $whiteList;
        $this->httpRequest = $httpRequest;
    }
    
    /**
     * 获取IP
     * @return string
     */
    public function GetClientIp(): string
    {
        $whiteList = $this->whiteList;
        $forwardedFor = $this->GetHeader('X-Forwarded-For', '');
        
        if ('' === $forwardedFor)
        {
            return $this->GetRemoteIP();
        }
        
        $ips = explode(',', $forwardedFor);
        $ips[] = $this->GetRemoteIP();
        
        $result = '';
        if (null === $whiteList)
        {
            // 无条件信任
            $size = count($ips);
            for ($i = 0; $i < $size; $i++)
            {
                $ips[$i] = trim($ips[$i]);
                if(false !== filter_var($ips[$i], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                    || false !== filter_var($ips[$i], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                {
                    $result = $ips[$i];
                    break;
                }
            }
        }
        else
        {
            // 根据白名单确定有效IP
            for ($i = count($ips); $i > 1; $i--)
            {
                $ips[$i - 1] = trim($ips[$i - 1]);
                if (false === in_array($ips[$i - 1], $whiteList))
                {
                    if(false !== filter_var($ips[$i - 1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                        || false !== filter_var($ips[$i - 1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                    {
                        $result = $ips[$i - 1];
                    }
                    break;
                }
                else
                {
                    if(false !== filter_var($ips[$i - 2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                        || false !== filter_var($ips[$i - 2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                    {
                        $result = $ips[$i - 2];
                    }
                    else
                    {
                        $result = $ips[$i - 1];
                    }
                }
            }
        }
        
        return $result;
    }
}
