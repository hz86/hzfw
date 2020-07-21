<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 多语言
 * 语言文件路径：path/en-US/name.php
 * 语言文件格式：["en_us" => "zh_cn"]
 * Class I18n
 * @package hzfw\base
 */
class I18n extends BaseObject
{
    /**
     * 语言文件目录
     * @var string
     */
    public string $path;
    
    /**
     * 当前语言
     * @var string
     */
    public string $language;
    
    /**
     * 翻译
     * @param string $name 文件名
     * @param string $message 消息文本 可用 {name} 的方式占位
     * @param array $params 对应占位符的内容
     */
    public function Translation(string $name, string $message, array $params = []): string
    {
        $map = @include_once($this->path . '/' . $this->language . '/' . $name . '.php');
        $message = false !== $map && isset($map[$message]) ? $map[$message] : $message;
        
        uksort($params, function ($a, $b) 
        {
            return strlen((string)$b) - strlen((string)$a);
        });
        
        foreach ($params as $name => $value)
        {
            $message = str_replace('{'.$name.'}', $value, $message);
        }
        
        return $message;
    }
}
