<?php
ini_set('display_errors','1');
error_reporting(E_ALL);

define('SQL_HOST','127.0.0.1');
define('SQL_USER','root');
define('SQL_PASS','');
define('SQL_DATB','travian_kingdoms');
define('LANGUAGE','en');

$base = '';
define('APP_BASE', $base);

$index_url = '/';
$mellon_url = '/mellon/';
$cdn_url = '/cdn/';
$lobby_url = '/lobby/';
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$game_dir = '/game/s1/';

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
