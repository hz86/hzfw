<?php

namespace frontend\controllers;
use frontend\models\ErrorModel;
use hzfw\web\ActionResult;
use hzfw\web\Controller;

class SiteController extends Controller
{
    //异常
    public function Error(int $statusCode, ?\Throwable $exception = null): ActionResult
    {
        $model = new ErrorModel();
        {
            $model->statusCode = $statusCode;
            $model->message = null === $exception ? '' : $exception->getMessage();
            $model->e = $exception;
        }
        return $this->View('', $model);
    }

    public function Index(): ActionResult
    {
        return $this->View();
    }
}
