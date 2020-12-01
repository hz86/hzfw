<?php

declare(strict_types=1);
namespace hzfw\base;

/**
 * 文件缓存
 * Class FileCache
 * @package hzfw\base
 */
class FileCache extends Cache
{
    /**
     * 缓存目录
     * @var string
     */
    public string $path;

    /**
     * 目录层次 建议 1 - 3
     * @var int
     */
    public int $directoryLevel;

    /**
     * 垃圾回收概率 0 - 100
     * @var int
     */
    public int $gcProbability;

    /**
     * 初始化
     * FileCache constructor.
     * @param string $path 缓存目录
     * @param int $directoryLevel 目录层次 建议 1 - 3
     * @param int $gcProbability 垃圾回收概率 0 - 100
     */
    public function __construct(string $path, int $directoryLevel, int $gcProbability)
    {
        $this->path = $path;
        $this->directoryLevel = $directoryLevel;
        $this->gcProbability = $gcProbability;
    }

    /**
     * 读缓存
     * @param string $key
     * @return CacheValue
     */
    public function GetValue(string $key): CacheValue
    {
        $now = time();
        $path = $this->GetPath($key);

        $result = new CacheValue();
        $result->hasValue = false;
        $result->value = null;

        $fp = @fopen($path, 'r');
        if (false !== $fp)
        {
            if (flock($fp, LOCK_SH))
            {
                if (@filemtime($path) > $now)
                {
                    fseek($fp, 0, SEEK_END);
                    $size = ftell($fp);

                    fseek($fp, 0, SEEK_SET);
                    $data = fread($fp, $size);

                    flock($fp, LOCK_UN);
                    $data = gzuncompress($data);
                    $result->value = unserialize($data);
                    $result->hasValue = true;
                }
            }

            fclose($fp);
        }

        return $result;
    }

    /**
     * 写缓存
     * @param string $key
     * @param int $expiry ms
     * @param mixed $value
     * @return bool
     */
    public function SetValue(string $key, int $expiry, $value): bool
    {
        $now = time();
        $path = $this->GetPath($key);
        $result = false;

        $data = serialize($value);
        $data = gzcompress($data, 1);

        $dirPath = substr($path, 0, strrpos($path, '/'));
        @mkdir($dirPath, 0775, true);

        $fp = @fopen($path, 'r+');
        if (false === $fp) $fp = @fopen($path, 'wx');
        if (false === $fp) $fp = @fopen($path, 'r+');

        if (false !== $fp)
        {
            if (flock($fp, LOCK_EX))
            {
                ftruncate($fp, 0);

                fwrite($fp, $data);
                @touch($path, $now + (int)($expiry / 1000));

                flock($fp, LOCK_UN);
            }

            fclose($fp);
        }

        $this->GC();

        return $result;
    }

    /**
     * 删除缓存
     * @param string $key
     */
    public function Remove(string $key): void
    {
        $path = $this->GetPath($key);
        @unlink($path);
    }

    /**
     * 根据key获取路径
     * @param string $key
     * @return string
     */
    private function GetPath(string $key): string
    {
        $hash = sha1($key);
        $path = $this->path;

        for ($i = 0; $i < $this->directoryLevel; $i++) {
            $path .= '/' . substr($hash, $i * 3, 3);
        }

        $path .= '/' . substr($hash,$this->directoryLevel * 3);
        return $path;
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
     * 回收文件
     */
    private function GC(): void
    {
        try
        {
            // 0 - 1000000
            if (mt_rand(0, 1000000) >= $this->gcProbability * 10000) {
                return;
            }

            $now = time();
            $this->FileEach($this->path, function (string $filename) use ($now)
            {
                try
                {
                    if (@filemtime($filename) <= $now) {
                        @unlink($filename);
                    }
                }
                catch (\Throwable $e)
                {
                    //忽略
                }
            });
        }
        catch (\Throwable $e)
        {
            //忽略
        }
    }
}
