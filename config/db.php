<?php

function get_config(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($configPath)) {
        $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.sample.php';
    }
    /** @var array $config */
    $config = require $configPath;
    return $config;
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = get_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['db']['host'],
        $cfg['db']['port'],
        $cfg['db']['database'],
        $cfg['db']['charset']
    );
    $pdo = new PDO($dsn, $cfg['db']['username'], $cfg['db']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}


