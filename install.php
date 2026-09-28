<?php
declare(strict_types=1);
session_start();
/*
 * Installation state is deliberately checked before processing POST.
 * A completed installer must never redirect back into itself.
 */
if (is_file(__DIR__.'/.installed')) {
    if (isset($_GET['done'])) {
        header('Location: ./');
        exit;
    }
    http_response_code(403);
    exit('Already installed. Open the game at ./');
}
$errors=[];
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $host=trim($_POST['db_host']??'127.0.0.1'); $user=trim($_POST['db_user']??'root'); $pass=(string)($_POST['db_pass']??'');
 $name=preg_replace('/[^a-zA-Z0-9_]/','',$_POST['db_name']??'travian_kingdoms');
 $admin=trim($_POST['admin_user']??'admin'); $adminPass=(string)($_POST['admin_pass']??'');
 $server=trim($_POST['server_name']??'Kingdoms'); $speed=max(1,(int)($_POST['speed_world']??1)); $unitSpeed=max(1,(int)($_POST['speed_unit']??1));
 if(strlen($adminPass)<8)$errors[]='Admin wachtwoord moet minimaal 8 tekens zijn.';
 if(!extension_loaded('mysqli'))$errors[]='MySQLi ontbreekt.';
 if(!$errors)try{
  $db=new mysqli($host,$user,$pass); if($db->connect_errno)throw new RuntimeException($db->connect_error); $db->set_charset('utf8mb4');
  if(!$db->query("CREATE DATABASE IF NOT EXISTS ".$name." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))throw new RuntimeException($db->error);
  $db->select_db($name); $sql=file_get_contents(__DIR__.'/travian5.sql');
  if(!$sql)throw new RuntimeException('travian5.sql ontbreekt.');
  if(!$db->multi_query($sql))throw new RuntimeException('SQL import: '.$db->error);
  while($db->more_results()&&$db->next_result()){if($db->errno)throw new RuntimeException($db->error);}
  $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
  $base=$scheme.'://'.$_SERVER['HTTP_HOST'].rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/');
  if($base==='http://')$base='';
  $cfg="<?php\n".
   "define('SQL_HOST',".var_export($host,true).");\n".
   "define('SQL_USER',".var_export($user,true).");\n".
   "define('SQL_PASS',".var_export($pass,true).");\n".
   "define('SQL_DATB',".var_export($name,true).");\n".
   "define('LANGUAGE','en');\n".
   "define('APP_BASE',".var_export($base,true).");\n".
   "define('ADMIN_USERNAME',".var_export($admin,true).");\n".
   "define('ADMIN_PASSWORD_HASH',".var_export(password_hash($adminPass,PASSWORD_DEFAULT),true).");\n".
   "\$base=APP_BASE; \$index_url=APP_BASE.'/'; \$mellon_url=APP_BASE.'/mellon/'; \$cdn_url=APP_BASE.'/cdn/'; \$lobby_url=APP_BASE.'/lobby/'; \$domain=\$_SERVER['HTTP_HOST']??'localhost'; \$game_dir=APP_BASE.'/game/s1/';\n".
   "function protocalRemove(\$url){return preg_replace('#^https?://#i','',\$url);} function myErrorHandler(\$c,\$m,\$f,\$l){error_log('[OwnProject] '.\$m.' '.\$f.':'.\$l);} function fatalErrorShutdownHandler(){\$e=error_get_last();if(\$e&&in_array(\$e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))error_log('[OwnProject] '.\$e['message']);}\n";
  if(file_put_contents(__DIR__.'/config.php',$cfg,LOCK_EX)===false)throw new RuntimeException('config.php schriven mislukt.');
  $r=$db->query("SHOW TABLES LIKE 's1_%'"); while($x=$r->fetch_row())$db->query("TRUNCATE TABLE ".$x[0]);
  $st=$db->prepare("UPDATE global_server_data SET name=?,tag='server1',folder=?,prefix='s1_',speed_world=?,speed_unit=?,start=?,maintenance=0,genmap='0' WHERE sid=1");
  $folder=$base.'/game/s1';$start=date('Y-m-d H:i:s');$st->bind_param('ssiis',$server,$folder,$speed,$unitSpeed,$start);$st->execute();$st->close();
  require __DIR__.'/admin/engine/engine.php'; $engine->server=(object)$engine->database->getServer(1); $engine->world->generateMap();
  if (file_put_contents(__DIR__.'/.installed', date('c'), LOCK_EX) === false) {
    throw new RuntimeException('Kan installatiestatus niet opslaan.');
}
header('Location: ./', true, 302);
exit;
 }catch(Throwable $e){$errors[]=$e->getMessage();}
}
?><!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Kingdoms installatie</title></head><body><div class="box"><h1>Travian Kingdoms installatie</h1><?php foreach($errors as $e):?><div class="err"><?=h($e)?></div><?php endforeach;?><form method="post"><input name="db_host" value="127.0.0.1"><input name="db_user" value="root"><input type="password" name="db_pass"><input name="db_name" value="travian_kingdoms"><input name="admin_user" value="admin"><input type="password" name="admin_pass"><input name="server_name" value="Kingdoms"><input type="number" name="speed_world" min="1" value="1"><input type="number" name="speed_unit" min="1" value="1"><button>Installeren</button></form></div></body></html>