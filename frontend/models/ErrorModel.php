<?php

namespace frontend\models;
use hzfw\web\Model;

class ErrorModel extends Model
{
    public int $statusCode;
    public string $message;
    public \Throwable $e;
}
