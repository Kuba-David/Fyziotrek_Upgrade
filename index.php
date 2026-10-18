<?php
declare(strict_types=1);

// PHP's built-in dev server: let it serve static files itself.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) && !str_ends_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php')) {
    return false;
}

require __DIR__ . '/app/bootstrap.php';
require APP_DIR . '/controllers/site.php';
require APP_DIR . '/controllers/admin.php';

start_session();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
if (base_path() !== '' && str_starts_with($path, base_path())) {
    $path = substr($path, strlen(base_path()));
}
$path = '/' . trim($path, '/');

$routes = [
    '~^/$~' => 'site_home',
    '~^/index\.(html|php)$~' => fn () => redirect('/', 301),
    '~^/kurzy/([a-z0-9-]+)$~' => 'site_course',
    '~^/kurzy/([a-z0-9-]+)/prihlaska$~' => 'site_apply',
    '~^/kurzy/([a-z0-9-]+)/prihlaska/odeslano$~' => 'site_apply_done',
    '~^/kontakt$~' => 'site_contact',
    '~^/sitemap\.xml$~' => 'site_sitemap',
    '~^/robots\.txt$~' => 'site_robots',
    '~^/admin$~' => 'admin_dashboard',
    '~^/admin/setup$~' => 'admin_setup',
    '~^/admin/prihlaseni$~' => 'admin_login',
    '~^/admin/odhlaseni$~' => 'admin_logout',
    '~^/admin/zapomenute-heslo$~' => 'admin_forgot_password',
    '~^/admin/nove-heslo$~' => 'admin_reset_password',
    '~^/admin/kurzy$~' => 'admin_courses',
    '~^/admin/kurzy/novy$~' => 'admin_course_edit',
    '~^/admin/kurzy/(\d+)$~' => 'admin_course_edit',
    '~^/admin/kurzy/(\d+)/(smazat|duplikovat|nahoru|dolu|zverejnit)$~' => 'admin_course_action',
    '~^/admin/prihlasky$~' => 'admin_applications',
    '~^/admin/prihlasky/export$~' => 'admin_applications_export',
    '~^/admin/prihlasky/(\d+)$~' => 'admin_application',
    '~^/admin/zpravy$~' => 'admin_messages',
    '~^/admin/zpravy/(\d+)/smazat$~' => 'admin_message_delete',
    '~^/admin/nastaveni$~' => 'admin_settings',
    '~^/admin/uzivatele$~' => 'admin_users',
];

try {
    foreach ($routes as $pattern => $handler) {
        if (preg_match($pattern, $path, $m)) {
            array_shift($m);
            $handler(...$m);
            exit;
        }
    }

    // Old Adobe Muse pages (e.g. /mulligan-koncept.html) redirect to their new address.
    if (str_ends_with($path, '.html') && ($course = find_course_by_legacy_path(ltrim($path, '/')))) {
        redirect('kurzy/' . $course['slug'] . ($course['is_application'] ? '/prihlaska' : ''), 301);
    }

    not_found();
} catch (Throwable $e) {
    error_log('Fyziotrek: ' . $e);
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        echo 'Omlouváme se, na webu nastala chyba. Zkuste to prosím později.';
    }
}
