<?php
use App\Forms\LoginForm;

/**
 * @var LoginForm $form
 * @var string|null $error
 * @var string|null $back
 */
?>
<div class="auth">
    <div class="page-head">
        <h1>Přihlášení</h1>
        <p class="lead">Přihlaste se, abyste mohli přidávat inzeráty a kontaktovat jejich autory.</p>
    </div>

    <section class="panel" aria-label="Přihlášení">
        <?php if ($error): ?>
            <p class="alert alert--error" role="alert"><?= e($error) ?></p>
        <?php endif ?>
        <form class="form" action="<?= e(url('/prihlaseni')) ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <?php if ($back): ?>
                <input type="hidden" name="zpet" value="<?= e($back) ?>">
            <?php endif ?>
            <?= form_field($form, 'email', 'E-mail', ['type' => 'email', 'autocomplete' => 'email']) ?>
            <?= form_field($form, 'password', 'Heslo', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
            <button class="button" type="submit">Přihlásit se</button>
        </form>
    </section>

    <p class="auth__switch">Nemáte účet? <a href="<?= e(url('/registrace', ['zpet' => $back])) ?>">Zaregistrujte se</a>.</p>
</div>
