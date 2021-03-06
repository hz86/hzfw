<?php

declare(strict_types=1);
namespace frontend\models;
use hzfw\core\Model;

class ErrorModel extends Model
{
    public int $statusCode;
    public string $message;
    public \Throwable $e;
}
