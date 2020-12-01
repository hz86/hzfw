<?php

declare(strict_types=1);
namespace hzfw\base;

/**
 * 文件锁
 * Class FileLock
 * @package hzfw\base
 */
class FileLock extends Lock
{
    /**
     * 锁目录
     * @var string
     */
    public string $path;

    /**
     * 目录层次 建议 1 - 3
     * @var int
     */
    public int $directoryLevel;

    /**
     * 初始化
     * FileLock constructor.
     * @param string $path 缓存目录
     * @param int $directoryLevel 目录层次 建议 1 - 3
     */
    public function __construct(string $path, int $directoryLevel)
    {
        $this->path = $path;
        $this->directoryLevel = $directoryLevel;
    }

    /**
     * 加锁
     * @param string $key
     * @param int $mode LOCK_SH | LOCK_EX
     * @return FileLockResult FileLockResult->UnLock();
     * @throws \Exception
     */
    public function Lock(string $key, int $mode): FileLockResult
    {
        $path = $this->GetPath($key);
        $dirPath = substr($path, 0, strrpos($path, '/'));
        @mkdir($dirPath, 0775, true);

        $fp = @fopen($path, 'r+');
        if (false === $fp) $fp = @fopen($path, 'wx');
        if (false === $fp) $fp = @fopen($path, 'r+');

        if (false === $fp)
        {
            throw new \Exception("open file failed");
        }

        try
        {
            if (false === flock($fp, $mode))
            {
                throw new \Exception("lock file failed");
            }
        }
        catch (\Throwable $e)
        {
            fclose($fp);
            throw $e;
        }

        return new FileLockResult($fp);
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
}
