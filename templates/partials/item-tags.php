<?php
use App\Repositories\ItemQuery;

/** @var array $item */
?>
<span class="tag tag--<?= e($item['type']) ?>"><?= e(ItemQuery::TYPES[$item['type']]) ?></span>
<?php if ($item['status'] === 'returned'): ?>
    <span class="tag tag--returned">Vráceno</span>
<?php endif ?>
