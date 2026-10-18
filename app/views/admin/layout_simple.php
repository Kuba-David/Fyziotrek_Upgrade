<?php /** @var string $content */ ?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Administrace') ?> – Fyziotrek admin</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--simple">
<main class="auth-card">
    <img class="auth-card__logo" src="<?= e(url('assets/img/logo.svg')) ?>" alt="Fyziotrek" width="180" height="44">
    <?= $content ?>
</main>
</body>
</html>
