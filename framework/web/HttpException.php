<?php

namespace hzfw\web;
use Exception;
use Throwable;

//HTTP 异常
class HttpException extends Exception
{
	public function __construct(int $code, string $message = '', ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
