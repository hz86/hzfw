<?php

namespace hzfw\base;

/**
 * 文件锁返回值
 * Class FileLockResult
 */
class FileLockResult extends LockResult
{
    private $fp;

    public function __construct($fp)
    {
        $this->fp = $fp;
    }

    /**
     * 解锁
     */
    public function UnLock(): void
    {
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
    }
}
