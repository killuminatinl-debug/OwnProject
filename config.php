<?php
ini_set('display_errors','1');
error_reporting(E_ALL);

define('SQL_HOST','127.0.0.1');
define('SQL_USER','root');
define('SQL_PASS','');
define('SQL_DATB','travian_kingdoms');
define('LANGUAGE','en');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
$root = str_replace('\\','/', realpath(__DIR__) ?: __DIR__);
$docRoot = str_replace('\\','/', realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)) ?: ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)));
$basePath = '';
if ($docRoot !== '' && strpos($root, $docRoot) === 0) {
    $basePath = substr($root, strlen($docRoot));
}
$basePath = '/' . trim($basePath, '/');
if ($basePath === '/') $basePath = '';
$base = $scheme . '://' . $host . $basePath;
define('APP_BASE', $base);

$index_url = $base . '/';
$mellon_url = $base . '/mellon/';
$cdn_url = $base . '/cdn/';
$lobby_url = $base . '/lobby/';
$domain = $host;
$game_dir = $base . '/game/s1/';

function protocalRemove($url){ return preg_replace('#^https?://#i','',$url); }
function myErrorHandler($code,$message,$file,$line){
    error_log('[OwnProject] '.$message.' '.$file.':'.$line);
}
function fatalErrorShutdownHandler(){
    $e = error_get_last();
    if($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)){
        error_log('[OwnProject] '.$e['message'].' '.$e['file'].':'.$e['line']);
    }
}
set_error_handler('myErrorHandler');
register_shutdown_function('fatalErrorShutdownHandler');
