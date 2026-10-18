<?php
/** @var string $content */
$title ??= 'Fyziotrek';
$description ??= 'Odborné kurzy a semináře pro fyzioterapeuty.';
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= e(absolute_url('assets/img/og.png')) ?>">
    <meta name="theme-color" content="#009757">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preload" href="<?= e(url('assets/fonts/rajdhani-latin-ext-700-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <script src="<?= e(asset('js/site.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#obsah">Přeskočit na obsah</a>
<header class="site-header" data-header>
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?= e(url('/')) ?>" aria-label="Fyziotrek – úvod">
            <img src="<?= e(url('assets/img/logo.svg')) ?>" alt="Fyziotrek" width="160" height="39">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="hlavni-menu" data-nav-toggle>
            <span class="nav-toggle__bar"></span><span class="visually-hidden">Menu</span>
        </button>
        <nav class="site-nav" id="hlavni-menu" aria-label="Hlavní menu">
            <a href="<?= e(url('/#kurzy')) ?>">Kurzy</a>
            <a href="<?= e(url('/#treninky')) ?>">Tréninky</a>
            <a href="<?= e(url('/#fyzioterapie')) ?>">Fyzioterapie</a>
            <a href="<?= e(url('/#kontakty')) ?>">Kontakt</a>
            <?php if (setting('facebook_url')): ?>
                <a class="site-nav__icon" href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="obsah">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <img src="<?= e(url('assets/img/logo.svg')) ?>" alt="Fyziotrek" width="150" height="37" class="site-footer__logo">
            <p class="site-footer__claim">vaše cesta ke vzdělání</p>
        </div>
        <div class="site-footer__contact">
            <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
            <span><?= e(setting('address')) ?></span>
        </div>
        <p class="site-footer__copy">© <?= date('Y') ?> Fyziotrek</p>
    </div>
</footer>
</body>
</html>
