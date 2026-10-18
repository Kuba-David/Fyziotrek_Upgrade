<?php
/** @var array $users */
/** @var array $user */
/** @var ?string $error */
?>
<div class="page-head">
    <div>
        <h1>Uživatelé</h1>
        <p class="muted">Kdo se může přihlásit do administrace.</p>
    </div>
</div>

<?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>

<div class="split split--even">
    <section class="panel">
        <h2 class="panel__title">Správci</h2>
        <ul class="user-list">
            <?php foreach ($users as $u): ?>
                <li>
                    <div>
                        <strong><?= e($u['name']) ?></strong><?= (int) $u['id'] === (int) $user['id'] ? ' <span class="tag tag--muted">to jste vy</span>' : '' ?><br>
                        <span class="muted small"><?= e($u['email']) ?> · naposledy přihlášen/a: <?= e(format_datetime($u['last_login_at']) ?: 'nikdy') ?></span>
                    </div>
                    <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" data-confirm="Odebrat uživatele <?= e($u['name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="link-button danger" type="submit">Odebrat</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3 class="panel__subtitle">Přidat správce</h3>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <label class="field"><span class="field__label">Jméno</span><input type="text" name="name" required></label>
            <label class="field"><span class="field__label">E-mail</span><input type="email" name="email" required autocomplete="off"></label>
            <label class="field"><span class="field__label">Heslo (alespoň 10 znaků)</span><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
            <button class="btn" type="submit">Přidat</button>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel__title">Změnit moje heslo</h2>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <label class="field"><span class="field__label">Současné heslo</span><input type="password" name="current_password" required autocomplete="current-password"></label>
            <label class="field"><span class="field__label">Nové heslo (alespoň 10 znaků)</span><input type="password" name="new_password" required minlength="10" autocomplete="new-password"></label>
            <button class="btn btn--primary" type="submit">Změnit heslo</button>
        </form>
    </section>
</div>
