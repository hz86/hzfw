# hzfw
PHP依赖注入强类型框架 (PHP dependency injection strongly typed framework)  
轻量级MVC框架 (Lightweight MVC framework)

## 要求
最低 PHP7.4

## 依赖注入例子
看 background 中的代码  

## 基本目录
站点目录 hzfw/frontend/web  
配置文件 hzfw/frontend/config/main.php  
配置文件 hzfw/frontend/config/config.json  
控制器 hzfw/frontend/controllers  
中间件 hzfw/frontend/middlewares  
模型 hzfw/frontend/models  
视图  hzfw/frontend/views  
小部件 hzfw/frontend/viewcomponents  

## URL重写(urlrewrite)

```
Apache -------------------------------

RewriteEngine on  

# if a directory or a file exists, use it directly  
RewriteCond %{REQUEST_FILENAME} !-f  
RewriteCond %{REQUEST_FILENAME} !-d  

# otherwise forward it to index.php  
RewriteRule . index.php  

Nginx -------------------------------

location / {
	if (!-e $request_filename){
		rewrite ^/(.*) /index.php last;
	}
}

IIS -------------------------------

<rule name="RewriteUserFriendlyURL" enabled="true" stopProcessing="true">
	<match url=".*" />
	<conditions>
		<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
		<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
	</conditions>
	<action type="Rewrite" url="index.php" />
</rule>
```

## 配置说明
```
{
  "Mvc": {
    //设置默认编码
    "Charset": "utf-8",

    //设置控制器命名空间
    "ControllerNamespace": "frontend\\controllers",

    //设置小部件命名空间
    "ViewComponentNamespace": "frontend\\viewcomponents",

    //设置视图目录
    "ViewPath": "frontend/views",

    //设置默认错误页面
    "Error": "Site/Error"
  },
  "Route": {
    "Rules": [
      {
        //路由名称
        "Name": "Index",

        //路由模板
        "Template": "",

        //路由默认控制器和动作名称
        "Defaults": {
          "Controller": "Site",
          "Action": "Index"
        }
      },
      {
        //变量使用 <>符号表示
        //例：/test1/asd/
        "Name": "Test1",
        "Template": "test1/<name>/",
        "Defaults": {
          "Controller": "Site",
          "Action": "Test1"
        }
      },
      {
        //变量支持正则匹配
        //例：/test2/100/
        "Name": "Test2",
        "Template": "test2/<id:\\d+>/",
        "Defaults": {
          "Controller": "Site",
          "Action": "Test2"
        }
      },
      {
        //默认路由
        //例：/site/index
        //例：Action = TestTest, /site/test-test
        "Name": "Default",
        "Template": "<Controller>/<Action>",
        "Defaults": {}
      }
    ]
  }
}

```

## 入口文件

frontend/web/index.php  

```
<?php

error_reporting(E_ALL);

//是否调试模式（只是一个标记，具体功能需要自己处理）
defined('HZFW_DEBUG') or define('HZFW_DEBUG', false);

//初始化框架
require_once(__DIR__ . '/../../framework/init.php');

//配置
require_once(__DIR__ . '/../config/main.php');
```

##  配置

frontend/config/main.php  

```
<?php

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
    Mvc::Use($app, function (MiddlewareManager $middlewareManager) {
        $middlewareManager->Add(new \frontend\middlewares\TestMiddleware());
    });
});

```

## MVC命名规则

控制器规则：控制器名称 + Controller.php  
视图规则：控制器名称/动作名称.php  
需要注意大小写  

## 路由

配置规则看上面  
路由参数和get参数可以绑定到控制器内的动作参数中直接获取
可在控制器 和 视图内使用下面命令  

```
//根据路由创建 url地址
$this->route->CreateUrl();
$this->route->CreateAbsoluteUrl();
```

## 控制器

控制器规则：控制器名称 + Controller.php  
frontend/controllers/SiteController.php  

```
<?php

namespace frontend\controllers;
use frontend\models\ErrorModel;
use hzfw\web\ActionResult;
use hzfw\web\Controller;
use hzfw\base\Logger;

class SiteController extends Controller
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    //异常
    public function Error(int $statusCode, ?\Throwable $exception = null): ActionResult
    {
        $model = new ErrorModel();
        {
            $model->statusCode = $statusCode;
            $model->message = null === $exception ? '' : $exception->getMessage();
            $model->e = $exception;
        }

        if ($model->statusCode >= 400 && $model->statusCode <= 499)
        {
            $this->logger->LogInformation('ErrorView', $model->message, $model->e);
        }
        else
        {
            $this->logger->LogError('ErrorView', $model->message, $model->e);
        }

        return $this->View('', $model);
    }

    public function Index(): ActionResult
    {
        return $this->View();
    }
}

```

## 小部件

frontend/views/Components  
和控制器类似，但由视图内调用。  

## 视图

视图规则：控制器名称/动作名称.php  
视图内可用 $model 取得传入的数据  

必备文件  
frontend/views/Layouts/Main.php  
View() 调用视图时 会载入这个文件  
ViewPartial() 则忽略  

Main.php
```
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title><?= \hzfw\base\Encoding::HtmlEncode($this->title) ?></title>
    <?= $this->head ?>
</head>
<?= $this->beginPage ?>
<body>
<?= $this->beginBody ?>
<?= $content ?>
<?= $this->endBody ?>
</body>
<?= $this->endPage ?>
</html>
```

## 数据库操作例子

定义Model  

```
<?php

namespace frontend\models;
use hzfw\web\Model;

class UserModel extends Model
{
    public $id;
    public $name;
    public $password;
}

```

读取数据  
```
//使用 $this->httpContext->requestServices->GetService() 或 __construct 获取 db实例
$model = UserModel::Parse($db->QueryOne("SELECT `id`, `name`, `password` FROM `user` WHERE `name` = :name", [
    ":name" => "xxxx"
]));

```