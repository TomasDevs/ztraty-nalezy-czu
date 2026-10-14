<?php
/** @var string $slug */
$paths = [
    'doklady' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.5 16c.5-1.3 1.4-2 2.5-2s2 .7 2.5 2M14 10h4M14 13h3"/>',
    'klice' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 8-8M16 7l2 2M14 9l2 2"/>',
    'elektronika' => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>',
    'obleceni' => '<path d="M8 3 3 6l2 4 2-1v12h10V9l2 1 2-4-5-3c-.5 1.5-2 2.5-4 2.5S8.5 4.5 8 3Z"/>',
    'doplnky' => '<circle cx="12" cy="15" r="6"/><path d="m9 5 3-2 3 2-3 4Z"/>',
    'ostatni' => '<path d="m3 7 9-4 9 4v10l-9 4-9-4Z"/><path d="m3 7 9 4 9-4M12 11v10"/>',
];
?>
<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><?= $paths[$slug] ?? $paths['ostatni'] ?></svg>
