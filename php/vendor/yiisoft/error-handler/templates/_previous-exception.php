<?php
/* @var $throwable \Throwable */
/* @var $this \Yiisoft\ErrorHandler\Renderer\HtmlRenderer */
?>
<div class="previous">
    <span class="arrow">&crarr;</span>
    <div class="flex-1">
        <h2>
            <span>Caused by:</span>
            <span><?= $this->htmlEncode(get_class($throwable)) ?></span>
        </h2>
        <h3><?= nl2br($this->htmlEncode($throwable->getMessage())) ?></h3>
        <p>in <span class="file"><?= $this->htmlEncode($throwable->getFile()) ?></span> at line <span class="line"><?= $throwable->getLine() ?></span></p>
        <?= $this->renderPreviousExceptions($throwable) ?>
    </div>
</div>
