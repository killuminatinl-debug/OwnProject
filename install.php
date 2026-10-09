<?php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

$done=false;
$error='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $host=trim(isset($_POST['host']) ? $_POST['host'] : '127.0.0.1');
        $user=trim(isset($_POST['user']) ? $_POST['user'] : 'root');
        $pass=(string)(isset($_POST['pass']) ? $_POST['pass'] : '');
        $ownerName=trim(isset($_POST['owner_name']) ? $_POST['owner_name'] : '');
        $ownerEmail=trim(isset($_POST['owner_email']) ? $_POST['owner_email'] : '');
        $ownerPassword=(string)(isset($_POST['owner_password']) ? $_POST['owner_password'] : '');
        $name=preg_replace('/[^A-Za-z0-9_]/','',isset($_POST['name']) ? $_POST['name'] : 'travian_kingdoms');
        if ($name==='') throw new RuntimeException('Invalid database name.');
        if (!preg_match('/^[A-Za-z0-9_-]{3,24}$/', $ownerName)) throw new RuntimeException('Owner username must be 3-24 characters (letters, numbers, underscore or hyphen).');
        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid owner email address.');
        if (strlen($ownerPassword) < 8) throw new RuntimeException('Owner password must contain at least 8 characters.');

        $pdo=new PDO('mysql:host='.$host.';charset=utf8mb4',$user,$pass,array(
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>true
        ));
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `'.$name.'`');

        $sqlFile=__DIR__.'/travian5.sql';
        if (!is_file($sqlFile)) throw new RuntimeException('travian5.sql is missing from the installation.');

        $sql=file_get_contents($sqlFile);
        if ($sql===false || trim($sql)==='') throw new RuntimeException('Could not read travian5.sql.');

        foreach (preg_split('/;\\s*(?:\\r?\\n|$)/', $sql) as $statement) {\n            if (trim($statement) !== '') {\n                $pdo->exec($statement);\n            }\n        }

        // Keep the static world/map/data but start the actual game empty.
        $dynamic=array(
            'global_user','global_avatar','global_msid',
            's1_user','s1_village','s1_field','s1_building','s1_units',
            's1_train','s1_train_queue','s1_tqueue','s1_troop_move','s1_troop_stay',
            's1_report_head','s1_report_body','s1_notification','s1_setting','s1_cache',
            's1_hero','s1_hero_item','s1_market','s1_auction','s1_chat_line','s1_chat_room',
            's1_gamecard','s1_php','s1_nodejs','s1_influence','s1_kingdom','s1_kingdom_invite'
        );
        foreach($dynamic as $table){
            try { $pdo->exec('TRUNCATE TABLE `'.$table.'`'); } catch(Throwable $ignore) {}
        }

        // Create the owner's login account. This schema has no separate admin-role column.
        $ownerInsert=$pdo->prepare('INSERT INTO `global_user` (`username`,`email`,`password`,`timed`,`prestige`,`level`) VALUES (?,?,?,?,?,?)');
        $ownerInsert->execute(array($ownerName,$ownerEmail,base64_encode($ownerPassword),time(),0,0));

        $now=time();
        $pdo->exec('UPDATE `global_server_data` SET `start`='.(int)$now.',`maintenance`=0,`recommended`=1 WHERE `sid`=1');

        $config = <<<'CONFIG'
<?php
ini_set('display_errors','0');
ini_set('log_errors','1');
error_reporting(E_ALL);

define('SQL_HOST', __SQL_HOST__);
define('SQL_USER', __SQL_USER__);
define('SQL_PASS', __SQL_PASS__);
define('SQL_DATB', __SQL_DATB__);
define('LANGUAGE','en');
define('APP_BASE','');
$index_url='/';
$mellon_url='/mellon/';
$cdn_url='/cdn/';
$lobby_url='/lobby/';
$game_dir='/game/s1/';
$domain=isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\\d+$/','',$_SERVER['HTTP_HOST']) : 'localhost';

function protocalRemove($url){return preg_replace('#^https?://#i','',$url);}
function myErrorHandler($severity,$message,$file,$line){if(!(error_reporting()&$severity))return false;error_log('[OwnProject] '.$message.' '.$file.':'.$line);return false;}
function fatalErrorShutdownHandler(){$e=error_get_last();if($e&&in_array($e['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR),true))error_log('[OwnProject] '.$e['message'].' '.$e['file'].':'.$e['line']);}
set_error_handler('myErrorHandler');
register_shutdown_function('fatalErrorShutdownHandler');
$GLOBALS['db']=null;
function db(){if($GLOBALS['db'] instanceof PDO)return $GLOBALS['db'];$GLOBALS['db']=new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATB.';charset=utf8mb4',SQL_USER,SQL_PASS,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false));return $GLOBALS['db'];}
CONFIG;
        $config = str_replace(
            array('__SQL_HOST__','__SQL_USER__','__SQL_PASS__','__SQL_DATB__'),
            array(var_export($host,true),var_export($user,true),var_export($pass,true),var_export($name,true)),
            $config
        );
        if (file_put_contents(__DIR__.'/config.php',$config)===false) throw new RuntimeException('Could not write config.php. Check folder permissions.');

        $done=true;
    } catch(Throwable $e) {
        $error=$e->getMessage();
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Travian Kingdoms - Installer</title>
<style>body{font-family:Arial;background:#eee;margin:0}.box{max-width:620px;margin:70px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 12px #999}input{width:100%;box-sizing:border-box;padding:10px;margin:6px 0 14px}button{padding:12px 22px;font-weight:bold}.ok{background:#e8ffe8;padding:15px}.err{background:#ffe8e8;padding:15px;white-space:pre-wrap}</style></head>
<body><div class="box">
<h1>Travian Kingdoms</h1><h2>Server installer</h2>
<?php if($done): ?>
<div class="ok"><b>Installation completed.</b><br><br>Database schema, world data, server configuration and the owner login are ready.<br><br>Sign in with the owner username and password you entered.<br><br><a href="/"><button>Open game</button></a></div>
<?php else: ?>
<?php if($error): ?><div class="err"><b>Installation failed:</b><br><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8'); ?></div><?php endif; ?>
<form method="post">
<label>MySQL host</label><input name="host" value="<?php echo htmlspecialchars(isset($_POST['host'])?$_POST['host']:'127.0.0.1',ENT_QUOTES,'UTF-8'); ?>">
<label>MySQL user</label><input name="user" value="<?php echo htmlspecialchars(isset($_POST['user'])?$_POST['user']:'root',ENT_QUOTES,'UTF-8'); ?>">
<label>MySQL password</label><input type="password" name="pass" value="">
<label>Database</label><input name="name" value="<?php echo htmlspecialchars(isset($_POST['name'])?$_POST['name']:'travian_kingdoms',ENT_QUOTES,'UTF-8'); ?>">
<hr><h3>Owner login</h3>
<label>Username (3–24 characters)</label><input name="owner_name" required minlength="3" maxlength="24" value="<?php echo htmlspecialchars(isset($_POST['owner_name'])?$_POST['owner_name']:'admin',ENT_QUOTES,'UTF-8'); ?>">
<label>Email</label><input type="email" name="owner_email" required value="<?php echo htmlspecialchars(isset($_POST['owner_email'])?$_POST['owner_email']:'admin@localhost.test',ENT_QUOTES,'UTF-8'); ?>">
<label>Password (minimum 8 characters)</label><input type="password" name="owner_password" required minlength="8" autocomplete="new-password">
<button type="submit">Install</button>
</form>
<p>PHP 7.4 + MySQL/MariaDB/XAMPP compatible. No Node process is required for the basic game loop.</p>
<?php endif; ?>
</div></body></html>