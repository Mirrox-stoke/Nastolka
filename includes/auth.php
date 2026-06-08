<?php
// includes/auth.php

require_once __DIR__ . '/db.php';

/**
 * Инициализирует сессию, если ещё не запущена.
 */
function session_init(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Возвращает текущего авторизованного пользователя или null.
 */
function current_user(): ?array
{
    session_init();
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $stmt = db()->prepare('SELECT id, email, username FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (Exception) {
        return null;
    }
}

/**
 * Проверяет, авторизован ли пользователь.
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Регистрирует нового пользователя.
 */
function register_user(string $email, string $password, string $username): array
{
    // Валидация
    if (empty($email) || empty($password) || empty($username)) {
        return ['ok' => false, 'error' => 'Все поля обязательны'];
    }
    
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Пароль должен быть минимум 6 символов'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Некорректный email'];
    }
    
    if (strlen($username) < 2 || strlen($username) > 100) {
        return ['ok' => false, 'error' => 'Имя должно быть от 2 до 100 символов'];
    }
    
    try {
        // Проверяем, не существует ли уже такой email
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'Этот email уже зарегистрирован'];
        }
        
        // Хэшируем пароль и создаём пользователя
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = db()->prepare(
            'INSERT INTO users (email, password_hash, username) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $username]);
        
        $user_id = db()->lastInsertId();
        
        // Логируем пользователя
        session_init();
        $_SESSION['user_id'] = $user_id;
        
        return ['ok' => true, 'message' => 'Успешная регистрация'];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => 'Ошибка при регистрации'];
    }
}

/**
 * Логирует пользователя по email и пароль.
 */
function login_user(string $email, string $password): array
{
    if (empty($email) || empty($password)) {
        return ['ok' => false, 'error' => 'Email и пароль обязательны'];
    }
    
    try {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Некорректный email или пароль'];
        }
        
        session_init();
        $_SESSION['user_id'] = $user['id'];
        
        return ['ok' => true, 'message' => 'Успешный вход'];
    } catch (Exception) {
        return ['ok' => false, 'error' => 'Ошибка при входе'];
    }
}

/**
 * Выход пользователя.
 */
function logout_user(): void
{
    session_init();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}
