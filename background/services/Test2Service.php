<?php

namespace background\services;
use hzfw\core\BaseObject;

class Test2Service extends BaseObject
{
    public function __construct()
    {
        echo "Test2Service\r\n";
    }

    public function Dispose()
    {
        echo "Dispose Test2Service\r\n";
    }

    public function Get()
    {
        return "456";
    }
}