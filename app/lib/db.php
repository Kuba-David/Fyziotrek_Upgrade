<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = config('db.driver', 'sqlite');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($driver === 'mysql') {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', config('db.host'), config('db.name'));
        $pdo = new PDO($dsn, (string) config('db.user'), (string) config('db.password'), $options);
    } else {
        $path = (string) config('db.sqlite_path', ROOT_DIR . '/data/fyziotrek.sqlite');
        $isNew = !is_file($path);
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        if ($isNew) {
            @chmod($path, 0640);
        }
    }

    migrate($pdo, $driver);
    return $pdo;
}

/** Create tables on first run. Statements use IF NOT EXISTS, so this is safe to repeat. */
function migrate(PDO $pdo, string $driver): void
{
    $exists = $driver === 'mysql'
        ? $pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn()
        : $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'settings'")->fetchColumn();
    if ($exists) {
        return;
    }
    $sql = (string) file_get_contents(APP_DIR . '/schema/' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $pdo->exec($statement);
    }
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_value(string $sql, array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? null : $value;
}

function db_exec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/** Insert a row and return its id. Column names come from code, never from user input. */
function db_insert(string $table, array $row): int
{
    $columns = array_keys($row);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', array_fill(0, count($columns), '?'))
    );
    db()->prepare($sql)->execute(array_values($row));
    return (int) db()->lastInsertId();
}

function db_update(string $table, int $id, array $row): void
{
    $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($row)));
    db()->prepare("UPDATE $table SET $sets WHERE id = ?")->execute([...array_values($row), $id]);
}
