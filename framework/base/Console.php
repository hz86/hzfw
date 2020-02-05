<?php

namespace hzfw\base;
use hzfw\core\BaseObject;

class Console extends BaseObject
{
    /**
     * 获取参数数量
     * @return int
     */
    public static function GetArgc(): int {
        return $_SERVER['argc'];
    }
    
    /**
     * 获取参数
     * @return array
     */
    public static function GetArgv(): array {
        return $_SERVER['argv'];
    }
    
    /**
     * 输入
     * @param int $len
     * @return string
     */
    public static function Stdin(int $len): string {
        return fread(\STDIN, $len);
    }
    
    /**
     * 输出
     * @param string $str
     * @return int
     */
    public static function Stdout(string $str): int {
        return fwrite(\STDOUT, $str);
    }
    
    /**
     * 错误
     * @param string $str
     * @return int
     */
    public static function Stderr(string $str): int {
        return fwrite(\STDERR, $str);
    }
    
    /**
     * 读入
     * @param string $str
     * @return string
     */
    public static function Read(int $len): string {
        return self::Stdin($len);
    }
    
    /**
     * 读行
     * @param string $str
     * @return string
     */
    public static function ReadLine(string $str): string {
        return readline($str);
    }
    
    /**
     * 写出
     * @param string $str
     */
    public static function Write(string $str): void {
        self::Stdout($str);
    }
    
    /**
     * 写出行
     * @param string $str
     */
    public static function WriteLine(string $str): void {
        self::Stdout("{$str}\r\n");
    }
    
    /**
     * 报警
     */
    public static function Beep(): void {
        self::Stdout("\x07");
    }
    
    /**
     * 清屏
     */
    public static function Clear(): void {
        self::Stdout(chr(27).chr(91).'H'.chr(27).chr(91).'J'); // ^[H^[J
    }
}
