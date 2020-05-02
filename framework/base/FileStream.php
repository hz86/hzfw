<?php

namespace hzfw\base;
use hzfw\core\BaseObject;

/**
 * 文件流
 * Class FileStream
 * @package hzfw\base
 */
class FileStream extends BaseObject
{
    /**
     * 句柄
     */
    private $fp = null;
    
    /**
     * 打开文件
     * @param string $filename
     * @param string $mode
     * @throws \Exception
     */
    public function __construct(string $filename, string $mode = 'rb')
    {
        $this->fp = @fopen($filename, $mode);
        if (false === $this->fp) throw new \Exception("file '{$filename}' open fail");
    }
    
    /**
     * 关闭文件
     */
    public function Dispose(): void
    {
        fclose($this->fp);
    }
    
    /**
     * 加锁
     * @param int $operation
     * @return bool
     */
    public function Lock(int $operation): bool
    {
        return flock($this->fp, $operation);
    }
    
    /**
     * 获取文件大小
     * @return int
     */
    public function Size(): int
    {
        $size = 0;
        $off = $this->Tell();
        
        $this->Seek(0, SEEK_END);
        $size = $this->Tell();
        $this->Seek($off, SEEK_SET);
        
        return $size;
    }
    
    /**
     * 读数据
     * @param int $len
     * @return string
     */
    public function Read(int $len): ?string
    {
        $result = fread($this->fp, $len);
        return false === $result ? null : $result;
    }
    
    /**
     * 写数据
     * @param string $data
     * @param int $len
     * @return int >=0 成功
     */
    public function Write(string $data, ?int $len = null): int
    {
        if (null === $len) $len = strlen($data);
        $result = fwrite($this->fp, $data, $len);
        return false === $result ? -1 : $result;
    }
    
    /**
     * 设置文件读写位置
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function Seek(int $offset, int $whence): bool
    {
        return 0 === fseek($this->fp, $offset, $whence);
    }
    
    /**
     * 返回当前读写位置
     * @return int >=0 成功
     */
    public function Tell(): int
    {
        $result = ftell($this->fp);
        return false === $result ? -1 : $result;
    }
    
    /**
     * 截取到指定大小
     * @param int $size
     * @return bool
     */
    public function Truncate(int $size): bool
    {
        return ftruncate($this->fp, $size);
    }
    
    /**
     * 强制写到磁盘
     * @return bool
     */
    public function Flush(): bool
    {
        return fflush($this->fp);
    }
    
    /**
     * 是否在文件尾
     * @return bool
     */
    public function IsEof(): bool
    {
        return feof($this->fp);
    }
    
    /**
     * 获取上次访问时间
     * @return int
     */
    public function GetLastAccessTime(): int
    {
        $stat = fstat($this->fp);
        return $stat['atime'];
    }
    
    /**
     * 获取上次修改时间
     * @return int
     */
    public function GetLastModifiedTime(): int
    {
        $stat = fstat($this->fp);
        return $stat['mtime'];
    }
    
    /**
     * 获取创建时间
     * @return int
     */
    public function GetCreateTime(): int
    {
        $stat = fstat($this->fp);
        return $stat['ctime'];
    }
}
