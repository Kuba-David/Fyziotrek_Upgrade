<?php
declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name('fyziotrek');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Platnost formuláře vypršela. Vraťte se zpět, obnovte stránku a zkuste to znovu.');
    }
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['user_id'] ?? null;
        $user = $id ? db_one('SELECT id, email, name FROM users WHERE id = ?', [$id]) : null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect(users_exist() ? 'admin/prihlaseni' : 'admin/setup');
    }
    return $user;
}

function users_exist(): bool
{
    return (int) db_value('SELECT COUNT(*) FROM users') > 0;
}

function attempt_login(string $email, string $password): bool
{
    $user = db_one('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    // Verify against a dummy hash when the user doesn't exist, so timing doesn't reveal valid emails.
    $hash = $user['password_hash'] ?? password_hash(random_bytes(8), PASSWORD_DEFAULT);
    if (!password_verify($password, $hash) || !$user) {
        return false;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_update('users', (int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    db_update('users', (int) $user['id'], ['last_login_at' => now()]);
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function password_problem(string $password): ?string
{
    if (mb_strlen($password) < 10) {
        return 'Heslo musí mít alespoň 10 znaků.';
    }
    return null;
}

const PASSWORD_RESET_TTL = 3600;

/** Send a one-time password reset link. Silent when the e-mail is unknown, so it can't be used to probe accounts. */
function send_password_reset(string $email): void
{
    $user = db_one('SELECT id, name, email FROM users WHERE email = ?', [mb_strtolower($email)]);
    if (!$user) {
        return;
    }
    $token = bin2hex(random_bytes(32));
    db_update('users', (int) $user['id'], [
        'reset_token_hash' => hash('sha256', $token),
        'reset_expires_at' => time() + PASSWORD_RESET_TTL,
    ]);
    $link = absolute_url('admin/nove-heslo?token=' . $token);
    $body = "Dobrý den,\n\nněkdo (nejspíš vy) požádal o nastavení nového hesla do administrace webu Fyziotrek.\n\n"
        . "Nové heslo nastavíte na této adrese (platí 1 hodinu):\n$link\n\n"
        . "Pokud jste o změnu nežádali, tento e-mail ignorujte – heslo zůstává beze změny.";
    send_mail($user['email'], 'Nové heslo do administrace Fyziotrek', $body);
}

function find_user_by_reset_token(string $token): ?array
{
    if (!preg_match('~^[a-f0-9]{64}$~', $token)) {
        return null;
    }
    $user = db_one('SELECT * FROM users WHERE reset_token_hash = ?', [hash('sha256', $token)]);
    return $user && (int) $user['reset_expires_at'] >= time() ? $user : null;
}
