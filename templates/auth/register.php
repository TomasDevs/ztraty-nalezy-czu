<?php
use App\Forms\RegisterForm;

/**
 * @var RegisterForm $form
 * @var string|null $back
 */
?>
<div class="auth">
    <div class="page-head">
        <h1>Registrace</h1>
        <p class="lead">Účet potřebujete k přidání ztráty nebo nálezu a ke kontaktování autora inzerátu.</p>
    </div>

    <section class="panel" aria-label="Registrace">
        <form class="form" action="<?= e(url('/registrace')) ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <?php if ($back): ?>
                <input type="hidden" name="zpet" value="<?= e($back) ?>">
            <?php endif ?>
            <?= form_field($form, 'name', 'Jméno', ['autocomplete' => 'name']) ?>
            <?= form_field($form, 'email', 'E-mail', ['type' => 'email', 'autocomplete' => 'email', 'hint' => 'V inzerátech se nezobrazuje.']) ?>
            <?= form_field($form, 'password', 'Heslo', ['type' => 'password', 'autocomplete' => 'new-password', 'hint' => sprintf('Aspoň %d znaků.', RegisterForm::MIN_PASSWORD_LENGTH)]) ?>
            <?= form_field($form, 'password_confirmation', 'Heslo znovu', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
            <button class="button" type="submit">Vytvořit účet</button>
        </form>
    </section>

    <p class="auth__switch">Už máte účet? <a href="<?= e(url('/prihlaseni', ['zpet' => $back])) ?>">Přihlaste se</a>.</p>
</div>
