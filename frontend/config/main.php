<?php

declare(strict_types=1);
use hzfw\web\Mvc;
use hzfw\web\Config;
use hzfw\core\ServiceCollection;
use hzfw\core\ServiceProvider;
use hzfw\web\MiddlewareManager;
use hzfw\base\Logger;
use hzfw\base\LogLevel;

hzfw::Services(function (ServiceCollection $service)
{
    // 加载配置
    $service->AddSingleton(Config::ClassName(), function (ServiceProvider $options) {
        return new Config(json_decode(file_get_contents(dirname(__FILE__) . '/config.json'), true));
    });

    // 日志
    $service->AddSingleton(Logger::ClassName(), function (ServiceProvider $options) {
        return new Logger(LogLevel::Information, hzfw::$path . '/frontend/logs', 7);
    });

    // Mvc
    Mvc::AddService($service);
});

hzfw::Run(function (ServiceProvider $app)
{
    // Mvc
    Mvc::Use($app, function (ServiceProvider $service) 
    {
        // 中间件
        $middlewareManager = $service->GetService(MiddlewareManager::ClassName());
        $middlewareManager instanceof MiddlewareManager;
        {
            $middlewareManager->Add(new \frontend\middlewares\TestMiddleware());
        }
    });
});
