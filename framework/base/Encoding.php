<?php

namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 编码类
 * Class Encoding
 * @package hzfw\base
 */
class Encoding extends BaseObject
{
    /**
     * html编码
     * @param string $content
     * @param string $encoding
     * @param bool $doubleEncode
     * @return string
     */
    public static function HtmlEncode(string $content, ?string $encoding = null, bool $doubleEncode = true): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, $encoding, $doubleEncode);
    }
    
    /**
     * html解码
     * @param string $content
     * @return string
     */
    public static function HtmlDecode(string $content, ?string $encoding = null): string
    {
        $content = htmlspecialchars_decode($content, ENT_QUOTES);
        if (null === $encoding) $encoding =  mb_internal_encoding() ;
        return preg_replace_callback('/(&#[0-9a-fA-F]+;|&[a-zA-Z]+;)/', function(array $matches) use($encoding): string  {
            return self::StringEncoding($matches[0], $encoding, 'HTML-ENTITIES');
        }, $content);
    }
    
    /**
     * url编码
     * @param string $str
     * @param bool $raw
     * @return string
     */
    public static function UrlEncode(string $str, bool $raw = false): string
    {
        return $raw ? rawurlencode($str) : urlencode($str);
    }
    
    /**
     * url解码
     * @param string $str
     * @param bool $raw
     * @return string
     */
    public static function UrlDecode(string $str, bool $raw = false): string
    {
        return $raw ? rawurldecode($str) : urldecode($str);
    }
    
    /**
     * base64编码
     * @param string $data
     * @return string
     */
    public static function Base64Encode(string $data): string
    {
        return base64_encode($data);
    }
    
    /**
     * base64解码
     * @param string $data
     * @return string
     */
    public static function Base64Decode(string $data): string
    {
        return base64_decode($data, true);
    }
    
    /**
     * 字符编码转换
     * @param string $str
     * @param string $toEncoding
     * @param string $fromEncoding
     * @return string
     */
    public static function StringEncoding(string $str, string $toEncoding, string $fromEncoding): string
    {
        return mb_convert_encoding($str, $toEncoding, $fromEncoding);
    }
    
    /**
     * JSON 编码
     * @param mixed $value
     * @return string
     */
    public static function JsonEncode($value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * JSON 解码
     * @param string $value
     * @return mixed
     */
    public static function JsonDecode(string $json)
    {
        return json_decode($json, true);
    }
}
