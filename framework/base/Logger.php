<?php

namespace hzfw\base;
use hzfw\core\BaseObject;

class Logger extends BaseObject
{
    /**
     * 日志等级
     * @var int
     */
    public int $level;

    /**
     * 日志目录
     * @var string
     */
    public string $logPath;

    /**
     * 保留日志天数
     * 0 = 永久保留
     * @var int
     */
    public int $expireLogs;

    /**
     * 初始化
     * Logger constructor.
     * @param int $level
     * @param string $logPath
     * @param int $expireLogs
     */
    public function __construct(int $level, string $logPath, int $expireLogs = 0)
    {
        $this->level = $level;
        $this->logPath = $logPath;
        $this->expireLogs = $expireLogs;
        mkdir($this->logPath, 0775, true);
    }

    /**
     * 输出日志
     * @param LogLevel $level
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function Log(int $level, string $name, string $message, ?\Throwable $e = null): void
    {
        if ($level >= $this->level)
        {
            $log = date('Y-m-d H:i:s') . "\r\n";
            $log .= "Name: {$name}\r\nMessage: {$message}\r\n";
            if (null !== $e) $log .= "{$e}\r\n";
            $log .= "\r\n\r\n";

            $fp = @fopen($this->logPath . '/' . date('Y-m-d') . '.log', 'a');
            if (false !== $fp)
            {
                if (flock($fp, LOCK_EX))
                {
                    fwrite($fp, $log);
                    flock($fp, LOCK_UN);
                }

                fclose($fp);
            }
        }
        $this->GC();
    }

    /**
     * 最详细日志
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogTrace(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Trace, $name, $message, $e);
    }

    /**
     * 调试用日志
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogDebug(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Debug, $name, $message, $e);
    }

    /**
     * 一般信息
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogInformation(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Information, $name, $message, $e);
    }

    /**
     * 警告信息
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogWarning(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Warning, $name, $message, $e);
    }

    /**
     * 错误信息
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogError(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Error, $name, $message, $e);
    }

    /**
     * 崩溃
     * @param string $name
     * @param string $message
     * @param \Throwable $e
     */
    public function LogCritical(string $name, string $message, ?\Throwable $e = null): void
    {
        $this->Log(LogLevel::Critical, $name, $message, $e);
    }

    /**
     * 枚举文件
     * @param string $path
     * @param \Closure $callback = function (string $filename) {}
     */
    private function FileEach(string $path, \Closure $callback): void
    {
        $h = @opendir($path);
        if (false !== $h)
        {
            while(($fl = readdir($h)) !== false)
            {
                $temp = $path.'/'.$fl;
                if ($fl != '.' && $fl != '..')
                {
                    if(is_dir($temp))
                    {
                        $this->FileEach($temp, $callback);
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

    /**
     * 垃圾回收
     */
    private function GC()
    {
        try
        {
            // 0 - 1000000
            $gcProbability = 1;
            if (mt_rand(0, 1000000) >= $gcProbability * 10000) {
                return;
            }

            if(0 !== $this->expireLogs)
            {
                $arr = [];
                $now = strtotime(date('Y-m-d'));
                for ($i = 0; $i < $this->expireLogs; $i++)
                {
                    $arr[] = date('Y-m-d', $now - ($i * 3600 * 24));
                }

                $this->FileEach($this->logPath, function (string $filename) use ($arr)
                {
                    try
                    {
                        $matches = null;
                        if (preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches) > 0)
                        {
                            if (!in_array($matches[1], $arr))
                            {
                                @unlink($filename);
                            }
                        }
                        else
                        {
                            @unlink($filename);
                        }
                    }
                    catch (\Throwable $t)
                    {
                        //忽略
                    }
                });
            }
        }
        catch (\Throwable $t)
        {
            //忽略
        }
    }
}
