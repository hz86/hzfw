<?php 

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * HTTP访问
 */
class HttpRequest extends BaseObject
{
    private $mh = false;
    private array $map1 = [];
    private array $map2 = [];
    
    public function __construct()
    {
        $this->mh = curl_multi_init();
    }
    
    /**
     * 销毁
     */
    public function Dispose(): void
    {
        while (null !== ($ch = array_pop($this->map1)))
        {
            unset($this->map2["{$ch}"]);
            curl_multi_remove_handle($this->mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($this->mh);
        $this->mh = false;
    }
    
    /**
     * 添加
     * @param HttpRequestOptions $options
     * @throws \Throwable
     */
    public function Add(HttpRequestOptions $options): void
    {
        $ch = curl_init();
        try
        {
            $key = spl_object_hash($options);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            curl_setopt($ch, CURLOPT_URL, $options->url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options->method);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options->sslVerifyhost ? 2 : 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            if (null !== $options->sslCa)
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, $options->sslCa);
            }
            
            if (null !== $options->sslCert)
            {
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
                curl_setopt($ch, CURLOPT_SSLCERT, $options->sslCert);
                if (null != $options->sslCertPasswd) curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $options->sslCertPasswd);
            }
            
            if (null !== $options->sslKey)
            {
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
                curl_setopt($ch, CURLOPT_SSLKEY, $options->sslKey);
                if (null != $options->sslKeyPasswd) curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $options->sslKeyPasswd);
            }
            
            if (null !== $options->authUser)
            {
                curl_setopt($ch, CURLOPT_USERNAME, $options->authUser);
            }
            
            if (null !== $options->authPasswd)
            {
                curl_setopt($ch, CURLOPT_PASSWORD, $options->authPasswd);
            }
            
            if ($options->location)
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            }
            
            if ($options->encoding)
            {
                curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
            }
            
            if(null !== $options->proxy)
            {
                $proxyData = parse_url($options->proxy);
                if (false !== $proxyData)
                {
                    if ("http" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "80"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                    else if ("https" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTPS);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "443"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                    else if ("sock5" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "1080"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                }
            }
            
            if (null != $options->data)
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $options->data);
            }
            
            if (null !== $options->headers)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options->headers);
            }
            
            if (null !== $options->cookies)
            {
                $cookie = "";
                foreach ($options->cookies AS $n => $v) {
                    $cookie .= ";" . urlencode($n) . "=" . urlencode($v);
                }
                if (strlen($cookie) > 0) $cookie = substr($cookie, 1);
                curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            }
            
            if (null !== $options->userAgent)
            {
                curl_setopt($ch, CURLOPT_USERAGENT, $options->userAgent);
            }
            
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $options->connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $options->timeout);
            
            curl_multi_add_handle($this->mh, $ch);
            $this->map2["{$ch}"] = $options;
            $this->map1[$key] = $ch;
        }
        catch (\Throwable $e)
        {
            curl_close($ch);
            throw $e;
        }
    }
    
    /**
     * 移除
     * @param HttpRequestOptions $options
     */
    public function Remove(HttpRequestOptions $options): void
    {
        $key = spl_object_hash($options);
        if (isset($this->map1[$key]))
        {
            $ch = $this->map1[$key];
            unset($this->map1[$key]);
            unset($this->map2["{$ch}"]);
            curl_multi_remove_handle($this->mh, $ch);
            curl_close($ch);
        }
    }
    
    /**
     * 执行
     * $func = function (HttpRequestResponse $response, HttpRequestOptions $options){}
     * @param \Closure $func 
     */
    public function MultiExec(\Closure $func): void
    {
        try
        {
            $active = 0;
            $status = CURLM_OK;
            
            do
            {
                $status = curl_multi_exec($this->mh, $active);
            }
            while (CURLM_CALL_MULTI_PERFORM === $status);
            while ($active > 0 && CURLM_OK === $status)
            {
                if (-1 === curl_multi_select($this->mh))
                {
                    usleep(100);
                }
                else
                {
                    do
                    {
                        $status = curl_multi_exec($this->mh, $active);
                    }
                    while (CURLM_CALL_MULTI_PERFORM === $status);
                    while(false !== ($item = curl_multi_info_read($this->mh)))
                    {
                        $ch = $item["handle"];
                        $key = spl_object_hash($this->map2["{$ch}"]);
                        
                        try
                        {
                            $result = new HttpRequestResponse();
                            
                            if (CURLE_OK === $item["result"])
                            {
                                $info = curl_getinfo($ch);
                                $response = curl_multi_getcontent($ch);
                                $headers = explode("\r\n\r\n", substr($response, 0, $info["header_size"]));
                                $result->body = substr($response, $info["header_size"], strlen($response) - $info["header_size"]);
                                $result->header = $headers[count($headers) - 2] . "\r\n\r\n";
                                $result->statusCode = $info["http_code"];
                            }
                            else
                            {
                                $result->statusCode = 0;
                                $result->header = $result->body = "";
                                $result->exception = new \Exception(curl_error($ch), curl_errno($ch));
                            }
                            
                            $func($result, $this->map2["{$ch}"]);
                        }
                        finally
                        {
                            unset($this->map1[$key]);
                            unset($this->map2["{$ch}"]);
                            curl_multi_remove_handle($this->mh, $ch);
                            curl_close($ch);
                        }
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            while (null !== ($ch = array_pop($this->map1)))
            {
                unset($this->map2["{$ch}"]);
                curl_multi_remove_handle($this->mh, $ch);
                curl_close($ch);
            }
            
            throw $e;
        }
    }
    
    /**
     * 执行请求
     * @param HttpRequestOptions $options
     * @return HttpRequestResponse
     * @throws \Exception
     */
    public static function Exec(HttpRequestOptions $options): HttpRequestResponse
    {
        $result = new HttpRequestResponse();
        $ch = curl_init();
        
        try
        {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            curl_setopt($ch, CURLOPT_URL, $options->url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options->method);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options->sslVerifyhost ? 2 : 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            if (null !== $options->sslCa)
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, $options->sslCa);
            }
            
            if (null !== $options->sslCert)
            {
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
                curl_setopt($ch, CURLOPT_SSLCERT, $options->sslCert);
                if (null != $options->sslCertPasswd) curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $options->sslCertPasswd);
            }
            
            if (null !== $options->sslKey)
            {
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
                curl_setopt($ch, CURLOPT_SSLKEY, $options->sslKey);
                if (null != $options->sslKeyPasswd) curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $options->sslKeyPasswd);
            }
            
            if (null !== $options->authUser)
            {
                curl_setopt($ch, CURLOPT_USERNAME, $options->authUser);
            }
            
            if (null !== $options->authPasswd)
            {
                curl_setopt($ch, CURLOPT_PASSWORD, $options->authPasswd);
            }
            
            if ($options->location)
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            }
            
            if ($options->encoding)
            {
                curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
            }
            
            if(null !== $options->proxy)
            {
                $proxyData = parse_url($options->proxy);
                if (false !== $proxyData)
                {
                    if ("http" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "80"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                    else if ("https" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTPS);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "443"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                    else if ("sock5" === strtolower($proxyData["scheme"]))
                    {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyData["host"] . ":" . (isset($proxyData["port"]) ? $proxyData["port"] : "1080"));
                        if (isset($proxyData["user"]) && isset($proxyData["pass"]))
                        {
                            curl_setopt($ch, CURLOPT_PROXYUSERNAME, urldecode($proxyData["user"]));
                            curl_setopt($ch, CURLOPT_PROXYPASSWORD, urldecode($proxyData["pass"]));
                        }
                    }
                }
            }
            
            if (null != $options->data)
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $options->data);
            }
            
            if (null !== $options->headers)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options->headers);
            }
            
            if (null !== $options->cookies)
            {
                $cookie = "";
                foreach ($options->cookies AS $n => $v) {
                    $cookie .= ";" . urlencode($n) . "=" . urlencode($v);
                }
                if (strlen($cookie) > 0) $cookie = substr($cookie, 1);
                curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            }
            
            if (null !== $options->userAgent)
            {
                curl_setopt($ch, CURLOPT_USERAGENT, $options->userAgent);
            }
            
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $options->connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $options->timeout);
            
            $response = curl_exec($ch);
            if (false !== $response)
            {
                $info = curl_getinfo($ch);
                $headers = explode("\r\n\r\n", substr($response, 0, $info["header_size"]));
                $result->body = substr($response, $info["header_size"], strlen($response) - $info["header_size"]);
                $result->header = $headers[count($headers) - 2] . "\r\n\r\n";
                $result->statusCode = $info["http_code"];
            }
            else
            {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
        }
        finally
        {
            curl_close($ch);
        }
        
        return $result;
    }
}
