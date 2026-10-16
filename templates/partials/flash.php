<?php foreach (App\Core\App::session()->pullFlashes() as [$type, $message]): ?>
    <p class="alert alert--<?= e($type) ?>" role="status"><?= e($message) ?></p>
<?php endforeach ?>
