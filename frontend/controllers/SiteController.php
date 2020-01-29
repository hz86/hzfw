<?php

namespace frontend\controllers;
use frontend\models\ErrorModel;
use hzfw\web\Controller;
use hzfw\web\Cookie;
use hzfw\web\HttpContext;
use hzfw\web\HttpException;
use hzfw\web\Model;

class SiteController extends Controller
{
    //异常
    public function Error(int $statusCode, ?\Throwable $exception = null)
    {
        $model = new ErrorModel();
        {
            $model->statusCode = $statusCode;
            $model->message = null === $exception ? '' : $exception->getMessage();
            $model->e = $exception;
        }
        return $this->View('', $model);
    }

    public function Index()
    {
        return $this->View();
    }
}
