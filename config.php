<?php
// Root deployment: C:\\xampp\\htdocs\\ and normal domain hosting.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('SQL_HOST', '127.0.0.1');
define('SQL_USER', 'root');
define('SQL_PASS', '');
define('SQL_DATB', 'travian_kingdoms');
define('LANGUAGE', 'en');

$base = '';
define('APP_BASE', $base);
$index_url = '/';
$mellon_url = '/mellon/';
$cdn_url = '/cdn/';
$lobby_url = '/lobby/';
$game_dir = '/game/s1/';
$domain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';

function protocalRemove($url) {
    return preg_replace('#^https?://#i', '', $url);
}

function myErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('[OwnProject] ' . $message . ' ' . $file . ':' . $line);
    return false;
}

function fatalErrorShutdownHandler() {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        error_log('[OwnProject] ' . $e['message'] . ' ' . $e['file'] . ':' . $e['line']);
    }
}

set_error_handler('myErrorHandler');
register_shutdown_function('fatalErrorShutdownHandler');

$GLOBALS['db'] = null;

function db() {
    if ($GLOBALS['db'] instanceof PDO) {
        return $GLOBALS['db'];
    }

    $GLOBALS['db'] = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DATB . ';charset=utf8mb4',
        SQL_USER,
        SQL_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    $GLOBALS['db']->exec("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");
    return $GLOBALS['db'];
}


