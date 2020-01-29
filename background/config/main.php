<?php

use hzfw\web\Mvc;
use hzfw\web\Config;
use hzfw\core\ServiceCollection;
use hzfw\core\ServiceProvider;
use background\services\TestService;
use background\services\Test2Service;
use background\services\Test3Service;
use background\services\Test4Service;

hzfw::Services(function (ServiceCollection $service)
{
    // 加载配置
    $service->AddSingleton(Config::ClassName(), function (ServiceProvider $options) {
        return new Config(json_decode(file_get_contents(dirname(__FILE__) . '/config.json'), true));
    });

    // 添加服务
    // 执行结束会自动释放
    // Scoped销毁时会自动释放 Transient 和 Scoped

    // 每次调用GetService都会创建新实例
    $service->AddTransient(TestService::ClassName());

    // 只会创建一次实例
    $service->AddSingleton(Test2Service::ClassName());

    // 在Scoped内只创建一次实例
    $service->AddScoped(Test3Service::ClassName());

    // 覆盖
    /*$service->AddTransient(TestService::ClassName(), function (ServiceProvider $services) {
        return new Test4Service($services->GetService(Test2Service::ClassName()));
    });*/
});

hzfw::Run(function (ServiceProvider $app)
{
    $t1 = $app->GetService(TestService::ClassName());
    $t2 = $app->GetService(TestService::ClassName());

    $t3 = $app->GetService(Test2Service::ClassName());
    $t4 = $app->GetService(Test2Service::ClassName());

    echo $t1->Get() . "\r\n";
    echo $t2->Get() . "\r\n";
    echo $t3->Get() . "\r\n";
    echo $t4->Get() . "\r\n";

    echo "\r\n";
    $scope = $app->CreateScope();

    $t1 = $scope->serviceProvider->GetService(TestService::ClassName());
    $t2 = $scope->serviceProvider->GetService(TestService::ClassName());

    $t3 = $scope->serviceProvider->GetService(Test2Service::ClassName());
    $t4 = $scope->serviceProvider->GetService(Test2Service::ClassName());

    $t5 = $scope->serviceProvider->GetService(Test3Service::ClassName());
    $t6 = $scope->serviceProvider->GetService(Test3Service::ClassName());

    echo $t1->Get() . "\r\n";
    echo $t2->Get() . "\r\n";
    echo $t3->Get() . "\r\n";
    echo $t4->Get() . "\r\n";
    echo $t5->Get() . "\r\n";
    echo $t6->Get() . "\r\n";

    $scope->Dispose();
    echo "\r\n";
});
