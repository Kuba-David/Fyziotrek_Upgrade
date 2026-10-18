<?php
/** @var string $content */
/** @var array $user */
/** @var ?array $flash */
$newApplications = (int) db_value("SELECT COUNT(*) FROM applications WHERE status = 'nova'");
$unreadMessages = (int) db_value('SELECT COUNT(*) FROM messages WHERE is_read = 0');
$current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$active = fn (string $segment) => str_contains($current, '/admin/' . $segment) ? ' is-active' : '';
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Administrace') ?> – Fyziotrek admin</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('vendor/quill/quill.snow.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script src="<?= e(asset('vendor/quill/quill.js')) ?>" defer></script>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin">
<header class="admin-bar">
    <a class="admin-bar__brand" href="<?= e(url('admin/kurzy')) ?>">
        <img src="<?= e(url('assets/img/logo.svg')) ?>" alt="Fyziotrek" width="120" height="29">
        <span>administrace</span>
    </a>
    <nav class="admin-nav" aria-label="Administrace">
        <a class="admin-nav__link<?= $active('kurzy') ?>" href="<?= e(url('admin/kurzy')) ?>">Kurzy</a>
        <a class="admin-nav__link<?= $active('prihlasky') ?>" href="<?= e(url('admin/prihlasky')) ?>">
            Přihlášky<?php if ($newApplications): ?> <span class="pill" title="Nové přihlášky"><?= $newApplications ?></span><?php endif; ?>
        </a>
        <a class="admin-nav__link<?= $active('zpravy') ?>" href="<?= e(url('admin/zpravy')) ?>">
            Zprávy<?php if ($unreadMessages): ?> <span class="pill" title="Nepřečtené zprávy"><?= $unreadMessages ?></span><?php endif; ?>
        </a>
        <a class="admin-nav__link<?= $active('nastaveni') ?>" href="<?= e(url('admin/nastaveni')) ?>">Nastavení</a>
        <a class="admin-nav__link<?= $active('uzivatele') ?>" href="<?= e(url('admin/uzivatele')) ?>">Uživatelé</a>
    </nav>
    <div class="admin-bar__user">
        <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Zobrazit web ↗</a>
        <form method="post" action="<?= e(url('admin/odhlaseni')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="link-button" title="Přihlášen/a jako <?= e($user['email']) ?>">Odhlásit</button>
        </form>
    </div>
</header>

<main class="admin-main">
    <?php if ($flash): ?>
        <p class="notice notice--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
