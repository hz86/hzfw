<?php

namespace background\services;
use hzfw\core\BaseObject;

class Test4Service extends TestService
{
    public function __construct(Test2Service $test2Service)
    {
        echo "Test4Service\r\n";
    }

    public function Dispose()
    {
        echo "Dispose Test4Service\r\n";
    }

    public function Get()
    {
        return "000";
    }
}