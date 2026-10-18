<?php
/** @var bool $sent */
/** @var ?string $error */
?>
<h1>Zapomenuté heslo</h1>
<?php if ($sent): ?>
    <p class="notice notice--success">Pokud e-mail patří k účtu v administraci, právě na něj odešel odkaz pro nastavení nového hesla. Odkaz platí 1 hodinu.</p>
    <p class="center"><a href="<?= e(url('admin/prihlaseni')) ?>">Zpět na přihlášení</a></p>
<?php else: ?>
    <p class="muted">Zadejte e-mail, kterým se přihlašujete. Pošleme vám odkaz pro nastavení nového hesla.</p>
    <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field__label">E-mail</span>
            <input type="email" name="email" required autofocus autocomplete="username">
        </label>
        <button class="btn btn--primary btn--block" type="submit">Poslat odkaz</button>
    </form>
    <p class="muted small center"><a href="<?= e(url('admin/prihlaseni')) ?>">Zpět na přihlášení</a></p>
<?php endif; ?>
