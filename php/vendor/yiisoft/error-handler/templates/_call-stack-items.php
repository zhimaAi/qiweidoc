<?php
/* @var $applicationItems array<int, string> */
/* @var $vendorItemGroups array<int, array<int, string>> */
/* @var $this \Yiisoft\ErrorHandler\Renderer\HtmlRenderer */
$insertItem = static function (array &$items, array $item, int $offset = 0): void {
    $itemIndex = array_key_first($item);
    foreach (array_keys($items) as $index) {
        $offset++;
        if ($index === ($itemIndex - 1)) {
            break;
        }
    }
    $items = array_slice($items, 0, $offset, true) + $item + array_slice($items, $offset, null, true);
};
foreach ($vendorItemGroups as $key => $vendorItemGroup) {
    $count = count($vendorItemGroup);
    if ($count === 0) {
        continue;
    }
    if ($count === 1) {
        $insertItem($applicationItems, $vendorItemGroup);
        continue;
    }
    $firstIndex = array_key_first($vendorItemGroup);
    $lastIndex = array_key_last($vendorItemGroup);
    $itemsContent = implode('', $vendorItemGroup);
    $itemGroupContent = <<<HTML
        <li class="call-stack-vendor-group">
            <div class="call-stack-vendor-collapse element-wrap">
                <div class="flex-1">
                    <span class="call-stack-vendor-state">+</span>
                    <span class="file-name">{$firstIndex} - {$lastIndex} Vendor package files ({$count})</span>
                </div>
            </div>
            <ul class="call-stack-vendor-items">
                {$itemsContent}
            </ul>
        </li>
    HTML;
    $insertItem($applicationItems, [$firstIndex => $itemGroupContent]);
}
?>
<ul>
    <?php foreach ($applicationItems as $applicationItem): ?>
        <?= $applicationItem ?>
    <?php endforeach; ?>
</ul>
