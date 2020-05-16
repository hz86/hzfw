<?php

declare(strict_types=1);
namespace frontend\controllers;
use frontend\models\ErrorModel;
use hzfw\web\ActionResult;
use hzfw\web\Controller;
use hzfw\base\Logger;

class SiteController extends Controller
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    //异常
    public function Error(int $statusCode, ?\Throwable $exception = null): ActionResult
    {
        $model = new ErrorModel();
        {
            $model->statusCode = $statusCode;
            $model->message = null === $exception ? '' : $exception->getMessage();
            $model->e = $exception;
        }

        if ($model->statusCode >= 400 && $model->statusCode <= 499)
        {
            $this->logger->LogInformation('ErrorView', $model->message, $model->e);
        }
        else
        {
            $this->logger->LogError('ErrorView', $model->message, $model->e);
        }

        return $this->View('', $model);
    }

    public function Index(): ActionResult
    {
        return $this->View();
    }
}
