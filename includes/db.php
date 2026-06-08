<?php
// includes/db.php — единственное место с настройками подключения

define('DB_HOST', 'nastolka');
define('DB_PORT', 3306);
define('DB_NAME', 'nastolka');
define('DB_USER', 'root');      // ← замените на своего пользователя
define('DB_PASS', '');          // ← и пароль

/**
 * Возвращает PDO-соединение (Singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
