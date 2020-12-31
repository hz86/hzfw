<?php

declare(strict_types=1);
use hzfw\core\ServiceProvider;
use hzfw\core\ServiceCollection;
use hzfw\core\UnknownClassException;

/**
 * 框架
 * Class hzfw
 */
class hzfw
{
    //全局变量
    private static array $alias = [];
    private static array $classAlias = [];
    private static ?ServiceCollection $services = null;

    /**
     * 项目目录
     * @var string
     */
    public static string $path = '';

    /**
     * 退出代码
     * @var integer
     */
    public static int $exitCode = 0;

    /**
     * 获取别名
     * @param string $name
     * @return string|null
     */
    public static function GetAlias(string $name): ?string
    {
        return isset(self::$alias[$name]) ? self::$alias[$name] : null;
    }

    /**
     * 设置别名
     * @param string $name
     * @param string $value
     */
    public static function SetAlias(string $name, string $value): void
    {
        self::$alias[$name] = $value;
    }

    /**
     * 移除别名
     * @param string $name
     */
    public static function RemoveAlias(string $name): void
    {
        unset(self::$alias[$name]);
    }

    /**
     * 扩充别名文本
     * 特殊字符% 如果冲突 可写成 %%
     * $str = "%slias%/filename.txt"
     * @param string $str
     * @return string
     */
    public static function ExpandAliasString(string $str): string
    {
        $callback = function(array $matches): string
        {
            $value = self::getAlias($matches[1]);
            if(null === $value) throw new \Exception("unknown alias '{$matches[1]}'");
            return $value;
        };

        $result = preg_replace_callback('/%([^%]+?)%/', $callback, $str);
        $result = str_replace('%%', '%', $result);
        return $result;
    }

    /**
     * 获取类别名
     * $class = "foo\\bar"
     * @param string $class
     * @return string|null
     */
    public static function GetClassAlias(string $class): ?string
    {
        $result = null;
        $arr = explode('\\', $class);
        $classAlias = self::$classAlias;

        foreach ($arr as $item)
        {
            if(!isset($classAlias[$item])) {
                $result = null;
                break;
            }

            $result = $classAlias[$item]['value'];
            $classAlias = $classAlias[$item]['node'];
        }

        return $result;
    }

    /**
     * 设置类别名
     * $class = "foo\\bar"
     * $value = "aaa\\bbb\\ccc"
     * "foo\\bar\\zz" = "aaa\\bbb\\ccc\\zz"
     * @param string $class
     * @param string $value
     */
    public static function SetClassAlias(string $class, string $value): void
    {
        $arr = explode('\\', $class);
        $classAlias = &self::$classAlias;
        $len = count($arr);

        for($i = 0; $i < $len; $i++)
        {
            $item = $arr[$i];
            if(!isset($classAlias[$item]))
            {
                $classAlias[$item] = [
                    'node' => [], 'value' => null,
                ];
            }

            if($i + 1 === $len) {
                $classAlias[$item]['value'] = $value;
            }

            $classAlias = &$classAlias[$item]['node'];
        }
    }

    /**
     * 移除类别名
     * @param string $class
     */
    public static function RemoveClassAlias(string $class): void
    {
        $arr = explode('\\', $class);
        $classAlias = &self::$classAlias;
        $len = count($arr);

        for($i = 0; $i < $len; $i++)
        {
            $item = $arr[$i];
            if(!isset($classAlias[$item]))
            {
                $classAlias[$item] = [
                    'node' => [], 'value' => null,
                ];
            }

            if($i + 1 === $len) {
                $classAlias[$item]['value'] = null;
            }

            $classAlias = &$classAlias[$item]['node'];
        }
    }

    /**
     * 扩充类别名文本
     * @param string $class
     * @return string
     */
    public static function ExpandClassAliasString(string $class): string
    {
        $result = null;
        $arr = explode('\\', $class);
        $classAlias = self::$classAlias;
        $len = count($arr);

        for($i = 0; $i < $len; $i++)
        {
            $item = $arr[$i];
            if(!isset($classAlias[$item])) {
                break;
            }

            $result = $classAlias[$item]['value'];
            $classAlias = $classAlias[$item]['node'];
        }

        if(null !== $result)
        {
            for($j = $i; $j < $len; $j++) {
                $item = $arr[$j]; $result .= "\\{$item}";
            }
        }

        return (null !== $result ? $result : $class);
    }

    /**
     * 注册服务
     * $func = function (ServiceCollection $services) {};
     * @param Closure $func
     * @throws UnknownClassException
     */
    public static function Services(Closure $func): void
    {
        self::$services = new ServiceCollection();
        self::$services->AddTransient(ServiceProvider::ClassName(), function (ServiceProvider $options) {
            return new ServiceProvider($options->collection, $options->scope);
        });

        $func(self::$services);
    }

    /**
     * 运行
     * @param Closure $func
     * @throws Exception
     * $func = function (ServiceProvider $app) {};
     */
    public static function Run(Closure $func): void
    {
        if (null === self::$services) {
            throw new \Exception("must first use hzfw::Services()");
        }

        try
        {
            $app = self::$services->serviceProvider;
            $func($app);
        }
        finally
        {
            self::$services->Dispose();
            self::$services = null;
        }
    }
    
    /**
     * 设置退出代码
     * @param int $code
     */
    public static function SetExitCode(int $code): void
    {
        self::$exitCode = $code;
    }
}

//捕获异常
set_exception_handler(function (Throwable $t)
{
    throw $t;
});

//捕获错误
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool
{
    if (!(error_reporting() & $errno)) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

//类自动加载
hzfw::$path = dirname(dirname(__FILE__));
hzfw::SetClassAlias('hzfw', 'framework');
spl_autoload_register (function (string $class): void
{
    $filename = hzfw::ExpandClassAliasString($class);
    $filename = str_replace('\\', '/', hzfw::$path . '/' . $filename) . '.php';

    include $filename;
    if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
        throw new UnknownClassException("class not exist '{$class}', file '{$filename}'");
    }
});

//IIS 地址编码问题
//采用基于重写模块原始URL
if(isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
}
