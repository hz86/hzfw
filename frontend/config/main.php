<?php

use hzfw\web\Mvc;
use hzfw\web\Config;
use hzfw\core\ServiceCollection;
use hzfw\core\ServiceProvider;
use hzfw\web\MiddlewareManager;

hzfw::Services(function (ServiceCollection $service)
{
    // 加载配置
    $service->AddSingleton(Config::ClassName(), function (ServiceProvider $options) {
        return new Config(json_decode(file_get_contents(dirname(__FILE__) . '/config.json'), true));
    });

    // Mvc
    Mvc::AddService($service);
});

hzfw::Run(function (ServiceProvider $app)
{
    // Mvc
    Mvc::Use($app, function (MiddlewareManager $middlewareManager) {
        $middlewareManager->Add(new \frontend\middlewares\TestMiddleware());
    });
});
