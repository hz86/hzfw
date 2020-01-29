<?php 

//调试用
//正常不要暴露信息
//var_dump($model->e);
?>
<div>
    <h1><?= \hzfw\base\Encoding::HtmlEncode($model->statusCode) ?></h1>
    <div><?= \hzfw\base\Encoding::HtmlEncode($model->message) ?></div>
</div>
