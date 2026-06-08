<?php
// includes/helpers.php

require_once __DIR__ . '/db.php';

/**
 * Отдаёт JSON-ответ и завершает скрипт.
 */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');   // для локальной разработки
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Короткая обёртка: ошибка API.
 */
function api_error(string $message, int $status = 400): never
{
    json_response(['ok' => false, 'error' => $message], $status);
}

/**
 * Безопасно достаёт параметр из $_GET / $_POST.
 */
function param(string $key, mixed $default = null, string $from = 'get'): mixed
{
    $src = $from === 'post' ? $_POST : $_GET;
    return isset($src[$key]) ? $src[$key] : $default;
}

/**
 * Парсит JSON-тело запроса.
 */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
