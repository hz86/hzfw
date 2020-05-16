<?php

declare(strict_types=1);
namespace background\services;
use hzfw\core\BaseObject;

class Test3Service extends TestService
{
    public function __construct(TestService $testService)
    {
        echo "Test3Service\r\n";
    }

    public function Dispose(): void
    {
        echo "Dispose Test3Service\r\n";
    }

    public function Get()
    {
        return "789";
    }
}