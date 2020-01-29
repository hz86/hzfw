<!DOCTYPE html>

<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <title><?= \hzfw\base\Encoding::HtmlEncode($this->title) ?></title>
    <?= $this->head ?>
</head>
<?= $this->beginPage ?>
<body>
<?= $this->beginBody ?>
<?= $content ?>
<?= $this->endBody ?>
</body>
<?= $this->endPage ?>
</html>
