<?php /** @var ?string $error */ ?>
<h1>Vítejte v administraci</h1>
<p class="muted">Web zatím nemá žádného správce. Vytvořte první účet – s ním se pak budete přihlašovat.</p>
<?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="stack">
    <?= csrf_field() ?>
    <label class="field">
        <span class="field__label">Jméno</span>
        <input type="text" name="name" required value="<?= e(post('name')) ?>">
    </label>
    <label class="field">
        <span class="field__label">E-mail (slouží k přihlášení)</span>
        <input type="email" name="email" required autocomplete="username" value="<?= e(post('email')) ?>">
    </label>
    <label class="field">
        <span class="field__label">Heslo (alespoň 10 znaků)</span>
        <input type="password" name="password" required minlength="10" autocomplete="new-password">
    </label>
    <?php if (is_file(ROOT_DIR . '/data/seed/courses.json')): ?>
        <label class="check">
            <input type="checkbox" name="import_seed" value="1" checked>
            <span>Naplnit web kurzy převzatými z původního webu</span>
        </label>
    <?php endif; ?>
    <button class="btn btn--primary btn--block" type="submit">Vytvořit účet</button>
</form>
