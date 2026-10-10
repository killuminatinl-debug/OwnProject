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
        $name=preg_replace('/[^A-Za-z0-9_]/','',isset($_POST['name']) ? $_POST['name'] : 'travian_kingdoms');
        if ($name==='') throw new RuntimeException('Invalid database name.');

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

        $pdo->exec($sql);

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

        $now=time();
        $pdo->exec('UPDATE `global_server_data` SET `start`='.(int)$now.',`maintenance`=0,`recommended`=1 WHERE `sid`=1');

        $config="<?php\n".
            "ini_set('display_errors','0');\n".
            "ini_set('log_errors','1');\n".
            "error_reporting(E_ALL);\n\n".
            "define('SQL_HOST',".var_export(\$host,true).");\n".
            "define('SQL_USER',".var_export(\$user,true).");\n".
            "define('SQL_PASS',".var_export(\$pass,true).");\n".
            "define('SQL_DATB',".var_export(\$name,true).");\n".
            "define('LANGUAGE','en');\n".
            "define('APP_BASE','');\n".
            "\$index_url='/';\n\$mellon_url='/mellon/';\n\$cdn_url='/cdn/';\n\$lobby_url='/lobby/';\n\$game_dir='/game/s1/';\n".
            "\$domain=isset(\$_SERVER['HTTP_HOST']) ? preg_replace('/:\\d+\$/','',\$_SERVER['HTTP_HOST']) : 'localhost';\n\n".
            "function protocalRemove(\$url){return preg_replace('#^https?://#i','',\$url);}\n".
            "function myErrorHandler(\$severity,\$message,\$file,\$line){if(!(error_reporting()&\$severity))return false;error_log('[OwnProject] '.\$message.' '.\$file.':'.\$line);return false;}\n".
            "function fatalErrorShutdownHandler(){\$e=error_get_last();if(\$e&&in_array(\$e['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR),true))error_log('[OwnProject] '.\$e['message'].' '.\$e['file'].':'.\$e['line']);}\n".
            "set_error_handler('myErrorHandler');register_shutdown_function('fatalErrorShutdownHandler');\n".
            "\$GLOBALS['db']=null;\n".
            "function db(){if(\$GLOBALS['db'] instanceof PDO)return \$GLOBALS['db'];\$GLOBALS['db']=new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATB.';charset=utf8mb4',SQL_USER,SQL_PASS,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false));return \$GLOBALS['db'];}\n";
        if (file_put_contents(__DIR__.'/config.php',$config)===false) throw new RuntimeException('Could not write config.php. Check folder permissions.');

        // The two Node.js socket services must use the same database as PHP.
        $serverConfig = array('host'=>$host, 'user'=>$user, 'password'=>$pass, 'database'=>$name);
        $serverConfigJson = json_encode($serverConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($serverConfigJson===false || file_put_contents(__DIR__.'/server-config.json', $serverConfigJson."\n")===false) {
            throw new RuntimeException('Could not write server-config.json. Check folder permissions.');
        }

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
<div class="ok"><b>Database installation completed.</b><br><br>Database schema, world data, PHP configuration and shared Node.js database settings were written.<br><br><b>Important:</b> on local Windows/XAMPP, install Node.js LTS, then start <code>start-all.bat</code> and keep its service windows open. Only then test the game. The installer does not mean every game feature has been verified.<br><br><a href="/"><button>Open game</button></a></div>
<?php else: ?>
<?php if($error): ?><div class="err"><b>Installation failed:</b><br><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8'); ?></div><?php endif; ?>
<form method="post">
<label>MySQL host</label><input name="host" value="<?php echo htmlspecialchars(isset($_POST['host'])?$_POST['host']:'127.0.0.1',ENT_QUOTES,'UTF-8'); ?>">
<label>MySQL user</label><input name="user" value="<?php echo htmlspecialchars(isset($_POST['user'])?$_POST['user']:'root',ENT_QUOTES,'UTF-8'); ?>">
<label>MySQL password</label><input type="password" name="pass" value="">
<label>Database</label><input name="name" value="<?php echo htmlspecialchars(isset($_POST['name'])?$_POST['name']:'travian_kingdoms',ENT_QUOTES,'UTF-8'); ?>">
<button type="submit">Install</button>
</form>
<p>Designed for PHP 7.4 + MySQL/MariaDB/XAMPP. This original game also uses persistent Node.js socket services and a PHP background loop. Run <code>start-all.bat</code> after installation on Windows. Most ordinary shared hosting plans block persistent processes; use a VPS or hosting plan that explicitly supports them.</p>
<?php endif; ?>
</div></body></html>