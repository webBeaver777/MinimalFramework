<?php

declare(strict_types=1);

// Универсальный роутер для встроенного сервера PHP.
// Обрабатывает API (/tasks, /auth, /me) через api.php,
// остальное — отдаёт статикой или index.html по умолчанию.

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

// Если запрошен существующий файл — пусть встроенный сервер отдаст его сам
$full = __DIR__ . $path;
if ($path !== '/' && file_exists($full) && !is_dir($full)) {
    return false; // встроенный сервер обслужит файл
}

// API роуты
if (preg_match('#^/(tasks|auth|me)(/|$)#', $path) === 1) {
    require __DIR__ . '/api.php';

    return true;
}

// Иначе — SPA/статический фронт
readfile(__DIR__ . '/index.html');

return true;
