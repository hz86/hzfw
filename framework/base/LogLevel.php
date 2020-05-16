<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

class LogLevel extends BaseObject
{
    /**
     * 最详细信息
     */
    public const Trace = 0;

    /**
     * 用于开发调试信息
     */
    public const Debug = 1;

    /**
     * 应用一般流程日志
     */
    public const Information = 2;

    /**
     * 跟踪异常和警告，不影响应用停止
     */
    public const Warning = 3;

    /**
     * 当前应用错误
     */
    public const Error = 4;

    /**
     * 应用崩溃
     */
    public const Critical = 5;
}