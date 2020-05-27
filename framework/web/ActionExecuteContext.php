<?php

declare(strict_types=1);
namespace hzfw\web;
use hzfw\core\BaseObject;

/**
 * 动作执行上下文
 * @package hzfw\web
 */
class ActionExecuteContext extends BaseObject
{
    /**
     * 控制器
     * @var Controller
     */
    public Controller $controller;
    
    /**
     * 动作名称
     * @var string
     */
    public string $actionName = '';
    
    /**
     * 动作方法名称
     * @var string
     */
    public string $actionMethod = '';
    
    /**
     * 动作参数
     * @var array [name => value]
     */
    public array $actionArguments = [];
}
