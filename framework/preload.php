<?php

declare(strict_types=1);

/**
 * 枚举文件
 * @param string $path
 * @param \Closure $callback = function (string $filename) {}
 */
function FileEach(string $path, \Closure $callback): void
{
    $h = @opendir($path);
    if (false !== $h)
    {
        while(($fl = readdir($h)) !== false)
        {
            $temp = $path . DIRECTORY_SEPARATOR . $fl;
            if ($fl != '.' && $fl != '..')
            {
                if(is_dir($temp))
                {
                    FileEach($temp, $callback);
                }
                else
                {
                    $callback($temp);
                }
            }
        }
        
        closedir($h);
    }
}

// 载入
include_once __DIR__ . '/init.php';
FileEach(__DIR__, function (string $filename)
{
    if (__FILE__ !== $filename && __DIR__ . DIRECTORY_SEPARATOR . 'init.php' !== $filename) {
        include_once $filename;
    }
});
