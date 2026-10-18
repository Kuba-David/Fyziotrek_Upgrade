<?php /** @var ?string $error */ ?>
<h1>Přihlášení do administrace</h1>
<?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="stack">
    <?= csrf_field() ?>
    <label class="field">
        <span class="field__label">E-mail</span>
        <input type="email" name="email" required autofocus autocomplete="username" value="<?= e(post('email')) ?>">
    </label>
    <label class="field">
        <span class="field__label">Heslo</span>
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="btn btn--primary btn--block" type="submit">Přihlásit se</button>
</form>
<p class="muted small center"><a href="<?= e(url('admin/zapomenute-heslo')) ?>">Zapomněli jste heslo?</a></p>
