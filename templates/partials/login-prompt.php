<?php
/** @var string $message */
$back = current_uri();
?>
<div class="login-prompt">
    <p><?= e($message) ?></p>
    <div class="login-prompt__actions">
        <a class="button" href="<?= e(url('/prihlaseni', ['zpet' => $back])) ?>">Přihlásit se</a>
        <a class="button button--secondary" href="<?= e(url('/registrace', ['zpet' => $back])) ?>">Vytvořit účet</a>
    </div>
</div>
