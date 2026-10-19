<?php
/**
 * @var array[] $users
 * @var int $currentId
 */
?>
<div class="page-head">
    <h1>Administrace</h1>
</div>

<?= render('partials/admin-nav', ['active' => 'uzivatele']) ?>

<h2 class="visually-hidden">Uživatelé</h2>
<div class="table-wrap" role="region" aria-label="Uživatelé" tabindex="0">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Jméno</th>
                <th scope="col">E-mail</th>
                <th scope="col">Role</th>
                <th scope="col">Inzeráty</th>
                <th scope="col">Stav</th>
                <th scope="col">Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <?php $isSelf = $user['id'] === $currentId ?>
                <tr>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= $user['role'] === 'admin' ? 'Administrátor' : 'Uživatel' ?></td>
                    <td><?= e((string) $user['items_count']) ?></td>
                    <td><?= $user['is_blocked'] ? 'Zablokovaný' : 'Aktivní' ?></td>
                    <td>
                        <?php if ($isSelf): ?>
                            <span class="muted">Váš účet</span>
                        <?php else: ?>
                            <div class="table__actions">
                                <form action="<?= e(url("/admin/uzivatele/{$user['id']}/blokace")) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="blocked" value="<?= $user['is_blocked'] ? '0' : '1' ?>">
                                    <button class="link-button<?= $user['is_blocked'] ? '' : ' link-button--danger' ?>" type="submit"><?= $user['is_blocked'] ? 'Odblokovat' : 'Zablokovat' ?></button>
                                </form>
                                <form action="<?= e(url("/admin/uzivatele/{$user['id']}/role")) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="role" value="<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>">
                                    <button class="link-button" type="submit"><?= $user['role'] === 'admin' ? 'Odebrat administrátora' : 'Udělat administrátorem' ?></button>
                                </form>
                            </div>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
