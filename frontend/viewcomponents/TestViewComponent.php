<?php

namespace frontend\viewcomponents;
use hzfw\web\ViewComponent;

class TestViewComponent extends ViewComponent
{
    public function Run($model): string
    {
        return $this->View('', $model);
    }
}
