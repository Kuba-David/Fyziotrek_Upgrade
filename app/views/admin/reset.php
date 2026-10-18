<?php
/** @var bool $valid */
/** @var ?string $error */
?>
<h1>Nové heslo</h1>
<?php if (!$valid): ?>
    <p class="notice notice--error">Odkaz je neplatný nebo mu vypršela platnost.</p>
    <p class="center"><a href="<?= e(url('admin/zapomenute-heslo')) ?>">Požádat o nový odkaz</a></p>
<?php else: ?>
    <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field__label">Nové heslo (alespoň 10 znaků)</span>
            <input type="password" name="password" required minlength="10" autofocus autocomplete="new-password">
        </label>
        <button class="btn btn--primary btn--block" type="submit">Uložit nové heslo</button>
    </form>
<?php endif; ?>
