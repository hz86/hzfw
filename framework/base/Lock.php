<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 互斥锁
 * Class Lock
 * @package hzfw\base
 */
class Lock extends BaseObject
{
    /**
     * 共享锁
     * @var integer
     */
    const LOCK_SH = 1;

    /**
     * 独占锁
     * @var integer
     */
    const LOCK_EX = 2;

    /**
     * 加锁
     * @param string $key
     * @param int $mode LOCK_SH | LOCK_EX
     * @return LockResult
     */
    public function Lock(string $key, int $mode): LockResult
    {
        return new LockResult();
    }
}
