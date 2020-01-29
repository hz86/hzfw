<?php

namespace background\services;
use hzfw\core\BaseObject;

class TestService extends BaseObject
{
    public function __construct()
    {
        echo "TestService\r\n";
    }

    public function Dispose()
    {
        echo "Dispose TestService\r\n";
    }

    public function Get()
    {
        return "123";
    }
}