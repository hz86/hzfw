<?php

declare(strict_types=1);
namespace background\services;
use hzfw\core\BaseObject;

class TestService extends BaseObject
{
    public function __construct()
    {
        echo "TestService\r\n";
    }

    public function Dispose(): void
    {
        echo "Dispose TestService\r\n";
    }

    public function Get()
    {
        return "123";
    }
}