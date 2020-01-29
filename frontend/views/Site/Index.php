<?php
$this->title = '标题';
?>
<div>
    <?= $this->ViewPartial('/Common/Header', ["par" => "123"]) ?>
</div>
<hr />
<div>
    <div>Hello World</div>
</div>
<hr />
<div>
    <?= $this->ViewComponent('Test', ["par" => "456"]) ?>
</div>
