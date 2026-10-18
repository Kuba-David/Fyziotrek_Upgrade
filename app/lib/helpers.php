<?php
declare(strict_types=1);

/** Get (or with an array argument, set) the app configuration. Dot notation: config('mail.from'). */
function config(array|string|null $key = null, mixed $default = null): mixed
{
    static $config = [];
    if (is_array($key)) {
        $config = $key;
        return null;
    }
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Path prefix the site is served from ('' at the domain root, '/sub' in a subfolder). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = '/'): string
{
    return rtrim((string) config('base_url', ''), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_DIR . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function redirect(string $path, int $status = 302): never
{
    $target = preg_match('~^https?://~', $path) ? $path : url($path);
    header('Location: ' . $target, true, $status);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_array(string $key): array
{
    $value = $_POST[$key] ?? [];
    return is_array($value) ? $value : [];
}

function slugify(string $text): string
{
    if (function_exists('transliterator_transliterate')) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text) ?: $text;
    } else {
        $text = strtr(mb_strtolower($text), ['á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss']);
    }
    $text = preg_replace('~[^a-z0-9]+~', '-', strtolower($text));
    return trim((string) $text, '-') ?: 'kurz';
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Turn "2026-11-27" into "27. 11. 2026". */
function format_date(?string $date): string
{
    if (!$date || !preg_match('~^(\d{4})-(\d{2})-(\d{2})~', $date, $m)) {
        return '';
    }
    return (int) $m[3] . '. ' . (int) $m[2] . '. ' . $m[1];
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date('j. n. Y H:i', $ts) : $value;
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/** Remember submitted form values for re-rendering after a validation redirect. */
function old(string $key, string $default = ''): string
{
    $value = $_SESSION['old'][$key] ?? $default;
    return is_string($value) ? $value : $default;
}

/** Render a view inside a layout. Variables in $data become local variables in both. */
function render(string $view, array $data = [], ?string $layout = 'layout'): void
{
    $content = render_to_string($view, $data);
    if ($layout === null) {
        echo $content;
        return;
    }
    echo render_to_string($layout, $data + ['content' => $content]);
}

function render_to_string(string $view, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_DIR . '/views/' . $view . '.php';
    return (string) ob_get_clean();
}

function not_found(): never
{
    http_response_code(404);
    render('404', ['title' => 'Stránka nenalezena']);
    exit;
}

/**
 * Simple per-IP rate limit. Returns false when the IP made $max attempts
 * in the last $seconds for the given bucket.
 */
function rate_limit(string $bucket, int $max, int $seconds): bool
{
    $ip = client_ip();
    $since = time() - $seconds;
    db()->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([time() - 86400]);
    $stmt = db()->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at >= ?');
    $stmt->execute([$bucket, $ip, $since]);
    if ((int) $stmt->fetchColumn() >= $max) {
        return false;
    }
    db()->prepare('INSERT INTO rate_limits (bucket, ip, created_at) VALUES (?, ?, ?)')->execute([$bucket, $ip, time()]);
    return true;
}

/** Escape plain text from the admin, turn URLs and e-mails into links and newlines into <br>. */
function format_text(?string $text): string
{
    $html = e(trim((string) $text));
    $html = (string) preg_replace_callback('~\bhttps?://[^\s<]+[^\s<.,;:!?)]~u', function ($m) {
        $label = preg_replace('~^https?://(www\.)?~', '', $m[0]);
        return '<a href="' . $m[0] . '" target="_blank" rel="noopener">' . rtrim((string) $label, '/') . '</a>';
    }, $html);
    $html = (string) preg_replace('~(?<![\w.:/-])([\w.+-]+@[\w-]+\.[\w.-]+\w)~u', '<a href="mailto:$1">$1</a>', $html);
    return nl2br($html, false);
}
