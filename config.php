<?php
ini_set('display_errors','1'); error_reporting(E_ALL);
define('SQL_HOST','127.0.0.1'); define('SQL_USER','root'); define('SQL_PASS',''); define('SQL_DATB','travian_kingdoms'); define('LANGUAGE','en');
$base=''; define('APP_BASE',$base); $index_url='/'; $mellon_url='/mellon/'; $cdn_url='/cdn/'; $lobby_url='/lobby/'; $domain=$_SERVER['HTTP_HOST']??'localhost'; $game_dir='/game/s1/';
function protocalRemove($url){return preg_replace('#^https?://#i','',$url);}
function myErrorHandler($c,$m,$f,$l){error_log('[OwnProject] '.$m.' '.$f.':'.$l);}
function fatalErrorShutdownHandler(){ $e=error_get_last(); if($e&&in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))error_log('[OwnProject] '.$e['message'].' '.$e['file'].':'.$e['line']);}
set_error_handler('myErrorHandler'); register_shutdown_function('fatalErrorShutdownHandler');
$GLOBALS['db']=null;
if (!defined('KINGDOMS_ADMIN_BOOTSTRAP')) {
function query($sql,$params=[]){$s=db()->prepare($sql);$s->execute(is_array($params)?$params:[]);return $s;}
}
function db(){if($GLOBALS['db'] instanceof PDO)return $GLOBALS['db'];$GLOBALS['db']=new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATB.';charset=utf8mb4',SQL_USER,SQL_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$GLOBALS['db']->exec("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");return $GLOBALS['db'];}
