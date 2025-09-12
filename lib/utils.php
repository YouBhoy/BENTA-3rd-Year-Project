<?php

function start_app_session(): void {
    $config = get_config();
    if (session_status() === PHP_SESSION_NONE) {
        session_name($config['app']['session_name'] ?? 'benta_session');
        session_start();
    }
}

function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function redirect(string $path): void {
    $cfg = get_config();
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    header('Location: ' . $base . '/' . ltrim($path, '/'));
    exit;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


