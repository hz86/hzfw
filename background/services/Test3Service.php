<?php

namespace background\services;
use hzfw\core\BaseObject;

class Test3Service extends TestService
{
    public function __construct(TestService $testService)
    {
        echo "Test3Service\r\n";
    }

    public function Dispose()
    {
        echo "Dispose Test3Service\r\n";
    }

    public function Get()
    {
        return "789";
    }
}