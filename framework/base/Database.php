<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;

class Database extends BaseObject
{
    private \PDO $conn;
    private int $transactionRef = 0;
    private bool $persistent = false;
    private ?string $username = null;
    private ?string $passwd = null;
    private string $dsn = '';
    
    /**
     * 创建连接
     * @param string $dsn
     * @param string $username
     * @param string $passwd
     * @param bool $persistent
     */
    public function __construct(string $dsn, ?string $username = null, ?string $passwd = null, bool $persistent = false)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->persistent = $persistent;
        
        $this->conn = new \PDO($dsn, $username, $passwd, [
            \PDO::ATTR_PERSISTENT => $this->persistent,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    /**
     * 重置连接
     */
    public function ReConnect(): void
    {
        unset($this->conn);
        $this->transactionRef = 0;
        $this->conn = new \PDO($this->dsn, $this->username, $this->passwd, [
            \PDO::ATTR_PERSISTENT => $this->persistent,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    /**
     * 开始事务（支持嵌套）
     */
    public function BeginTransaction(): void
    {
        $this->transactionRef++;
        try
        {
            if (1 === $this->transactionRef) {
                $this->conn->beginTransaction();
            }
            else {
                $this->conn->exec('SAVEPOINT trans'.($this->transactionRef - 1));
            }
        }
        catch (\Exception $e)
        {
            $this->transactionRef--;
            throw $e;
        }
    }
    
    /**
     * 提交事务
     */
    public function Commit(): void
    {
        $this->transactionRef--;
        try
        {
            if (0 === $this->transactionRef) {
                $this->conn->commit();
            }
            else {
                $this->conn->exec('RELEASE SAVEPOINT trans'.$this->transactionRef);
            }
        }
        catch (\Exception $e)
        {
            $this->transactionRef++;
            throw $e;
        }
    }
    
    /**
     * 回滚事务
     */
    public function Rollback(): void
    {
        $this->transactionRef--;
        try
        {
            if (0 === $this->transactionRef) {
                $this->conn->rollBack();
            }
            else {
                $this->conn->exec('ROLLBACK TO SAVEPOINT trans'.$this->transactionRef);
            }
        }
        catch (\Exception $e)
        {
            $this->transactionRef++;
            throw $e;
        }
    }
    
    /**
     * 查询SQL 返回第一行
     * @param string $sql
     * @param array $param ["name"=>"value",...]
     * @return array ["name"=>"value",...]
     */
    public function QueryOne(string $sql, array $param = null): ?array
    {
        if (null === $param)
        {
            $statement = $this->conn->query($sql);
            $row = $statement->fetch();
            return false === $row ? 
            null : $row;
        }
        else
        {
            $statement = $this->conn->prepare($sql);
            
            foreach ($param as $name => &$value) {
                $statement->bindParam(':'.$name, $value);
            }
            
            $statement->execute();
            $row = $statement->fetch();
            return false === $row ?
            null : $row;
        }
    }
    
    /**
     * 查询SQL 返回所有
     * @param string $sql
     * @param array $param ["name"=>"value",...]
     * @return array [["name"=>"value"],["name"=>"value"],...]
     */
    public function QueryAll(string $sql, array $param = null): array
    {
        if (null === $param)
        {
            $statement = $this->conn->query($sql);
            return $statement->fetchAll();
        }
        else
        {
            $statement = $this->conn->prepare($sql);
            
            foreach ($param as $name => &$value) {
                $statement->bindParam(':'.$name, $value);
            }
            
            $statement->execute();
            return $statement->fetchAll();
        }
    }
    
    /**
     * 执行SQL
     * @param string $sql
     * @return int 返回  insert | update | delete 影响行数
     */
    public function Execute(string $sql, array $param = null): int
    {
        if (null === $param)
        {
            return $this->conn->exec($sql);
        }
        else
        {
            $statement = $this->conn->prepare($sql);
            
            foreach ($param as $name => &$value) {
                $statement->bindParam(':'.$name, $value);
            }
            
            $statement->execute();
            return $statement->rowCount();
        }
    }
    
    /**
     * 上次自增ID
     * @return int
     */
    public function LastInsertedId(): int
    {
        $row = $this->conn->query('SELECT LAST_INSERT_ID()')->fetch();
        return (int)$row['LAST_INSERT_ID()'];
    }
    
    /**
     * //转义列名
     * @param string $str
     * @return string
     */
    public function QuoteColumn(string $str): string
    {
        return '`' . str_replace('`', '``', $str) . '`';
    }
    
    /**
     * 转义值
     * @param string $str
     * @return string
     */
    public function QuoteValue(string $str): string
    {
        return $this->conn->quote($str);
    }
    
    /**
     * 释放
     */
    public function Dispose(): void
    {
        unset($this->conn);
    }
}
