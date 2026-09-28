<?php
declare(strict_types=1);
session_start();
@set_time_limit(0);
@ini_set('memory_limit','512M');
error_reporting(E_ALL);
ini_set('display_errors','1');

$errors=array();
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}

if(is_file(__DIR__.'/.installed')){
    header('Location: /');
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $host=trim($_POST['db_host']??'127.0.0.1');
    $user=trim($_POST['db_user']??'root');
    $pass=(string)($_POST['db_pass']??'');
    $name=preg_replace('/[^A-Za-z0-9_]/','',$_POST['db_name']??'travian_kingdoms');
    $admin=trim($_POST['admin_user']??'admin');
    $adminPass=(string)($_POST['admin_pass']??'');
    $serverName=trim($_POST['server_name']??'Kingdoms');
    $speedWorld=max(1,(int)($_POST['speed_world']??1));
    $speedUnit=max(1,(int)($_POST['speed_unit']??1));

    if($name==='')$errors[]='Ongeldige databasenaam.';
    if($admin==='')$errors[]='Vul een adminnaam in.';
    if(strlen($adminPass)<8)$errors[]='Admin wachtwoord moet minimaal 8 tekens zijn.';
    if(!extension_loaded('mysqli'))$errors[]='PHP MySQLi ontbreekt.';
    if(!extension_loaded('pdo_mysql'))$errors[]='PHP PDO MySQL ontbreekt.';

    if(!$errors){
        try{
            $db=new mysqli($host,$user,$pass);
            if($db->connect_errno)throw new RuntimeException('MySQL verbinding mislukt: '.$db->connect_error);
            $db->set_charset('utf8mb4');
            $db->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");

            if(!$db->query("CREATE DATABASE IF NOT EXISTS ".$name." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
                throw new RuntimeException('Database aanmaken mislukt: '.$db->error);
            if(!$db->select_db($name))throw new RuntimeException('Database selecteren mislukt: '.$db->error);

            $sql=file_get_contents(__DIR__.'/travian5.sql');
            if($sql===false||trim($sql)==='')throw new RuntimeException('travian5.sql ontbreekt of is leeg.');
            if(!$db->multi_query($sql))throw new RuntimeException('SQL import mislukt: '.$db->error);
            do{
                if($db->errno)throw new RuntimeException('SQL import mislukt: '.$db->error);
                if($result=$db->store_result())$result->free();
            }while($db->more_results()&&$db->next_result());

            $db->query("SET FOREIGN_KEY_CHECKS=0");
            $tables=$db->query("SHOW TABLES LIKE 's1_%'");
            if($tables){
                while($row=$tables->fetch_row())$db->query("TRUNCATE TABLE ".$row[0]);
                $tables->free();
            }
            $db->query("SET FOREIGN_KEY_CHECKS=1");

            $cfg="<?php\n";
            $cfg.="ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);\n";
            $cfg.="define('SQL_HOST',".var_export($host,true).");\n";
            $cfg.="define('SQL_USER',".var_export($user,true).");\n";
            $cfg.="define('SQL_PASS',".var_export($pass,true).");\n";
            $cfg.="define('SQL_DATB',".var_export($name,true).");\n";
            $cfg.="define('LANGUAGE','en');\n";
            $cfg.="\$base=''; define('APP_BASE','');\n";
            $cfg.="\$index_url='/'; \$mellon_url='/mellon/'; \$cdn_url='/cdn/'; \$lobby_url='/lobby/'; \$game_dir='/game/s1/';\n";
            $cfg.="\$domain=isset(\$_SERVER['HTTP_HOST'])?preg_replace('/:\\d+$/','',\$_SERVER['HTTP_HOST']):'localhost';\n";
            $cfg.="function protocalRemove(\$url){return preg_replace('#^https?://#i','',\$url);}\n";
            $cfg.="\$GLOBALS['db']=null;\n";
            $cfg.="function db(){if(\$GLOBALS['db'] instanceof PDO)return \$GLOBALS['db'];\$GLOBALS['db']=new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATB.';charset=utf8mb4',SQL_USER,SQL_PASS,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false));return \$GLOBALS['db'];}\n";
            $cfg.="?>\n";
            if(file_put_contents(__DIR__.'/config.php',$cfg,LOCK_EX)===false)throw new RuntimeException('config.php schrijven mislukt.');

            $adminEmail=strpos($admin,'@')!==false?$admin:$admin.'@localhost';
            $adminPassword=base64_encode($adminPass);

            $q=$db->prepare("SELECT uid FROM global_user WHERE username=? OR email=? LIMIT 1");
            $q->bind_param('ss',$admin,$adminEmail);$q->execute();$q->bind_result($uid);$exists=$q->fetch();$q->close();

            if($exists){
                $uid=(int)$uid;$now=time();
                $q=$db->prepare("UPDATE global_user SET username=?,password=?,email=?,timed=?,prestige=0,level=0 WHERE uid=?");
                $q->bind_param('sssii',$admin,$adminPassword,$adminEmail,$now,$uid);
            }else{
                $r=$db->query("SELECT COALESCE(MAX(uid),0)+1 AS n FROM global_user");
                $uid=(int)$r->fetch_assoc()['n'];$r->free();$now=time();$prestige=0;$level=0;
                $q=$db->prepare("INSERT INTO global_user (uid,username,password,email,timed,prestige,level) VALUES (?,?,?,?,?,?,?)");
                $q->bind_param('isssiii',$uid,$admin,$adminPassword,$adminEmail,$now,$prestige,$level);
            }
            if(!$q->execute())throw new RuntimeException('Admin aanmaken/bijwerken mislukt: '.$q->error);
            $q->close();

            $folder='/game/s1';$start=date('Y-m-d H:i:s');
            $q=$db->prepare("UPDATE global_server_data SET name=?,tag='server1',folder=?,prefix='s1_',speed_world=?,speed_unit=?,start=?,maintenance=0,genmap='0' WHERE sid=1");
            $q->bind_param('ssiis',$serverName,$folder,$speedWorld,$speedUnit,$start);
            if(!$q->execute())throw new RuntimeException('Serverinstellingen opslaan mislukt: '.$q->error);
            $q->close();

            $db->query("DELETE FROM s1_world");
            $q=$db->prepare("INSERT INTO s1_world (id,fieldtype,oasistype,x,y,bonus,image) VALUES (?,?,?,?,?,?,?)");
            if(!$q)throw new RuntimeException('Wereldtabel voorbereiden mislukt: '.$db->error);
            $types=array('3339','3447','3456','4347','4356','4437','4446','4536','5346','5436');
            $max=70;
            for($x=-$max;$x<=$max;$x++){
                for($y=-$max;$y<=$max;$y++){
                    if(($x*$x+$y*$y)>($max*$max))continue;
                    $id=($x+16384)+32768*($y+16384);
                    $field=$types[array_rand($types)];$oasistype=0;$image=random_int(0,31);$bonus=0;
                    $q->bind_param('issiiis',$id,$field,$oasistype,$x,$y,$bonus,$image);
                    if(!$q->execute())throw new RuntimeException('Wereldgeneratie mislukt: '.$q->error);
                }
            }
            $q->close();
            // Provision the installer account on the actual game world as well.
            $avatarId=0;
            $qa=$db->prepare("SELECT id FROM global_avatar WHERE email=? LIMIT 1");
            $qa->bind_param('s',$adminEmail); $qa->execute(); $qa->bind_result($avatarIdExisting);
            if($qa->fetch()){ $avatarId=(int)$avatarIdExisting; } $qa->close();
            if($avatarId===0){
                $qa=$db->prepare("INSERT INTO global_avatar (email,gender,hairColor,beard,ear,eye,eyebrow,hair,mouth,nose) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $zero=0; $qa->bind_param('siiiiiiiii',$adminEmail,$zero,$zero,$zero,$zero,$zero,$zero,$zero,$zero,$zero);
                if(!$qa->execute()) throw new RuntimeException('Admin avatar aanmaken mislukt: '.$qa->error);
                $avatarId=$db->insert_id; $qa->close();
            }
            $q=$db->prepare("SELECT uid FROM s1_user WHERE email=? LIMIT 1");
            $q->bind_param('s',$adminEmail); $q->execute(); $q->bind_result($gameUid); $hasGameUser=$q->fetch(); $q->close();
            if(!$hasGameUser){
                $gameUid=$uid;
                $q=$db->prepare("INSERT INTO s1_user (uid,username,email,tribe,kingdom,gold,silver,cp,avatar,serial,`desc`,protection,tutorial,quest,master,online,spawn,plus,resBonus,cropBonus,starterPack,autoExtend,lastLogin,attp,defp) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                if (!$q) throw new RuntimeException('Admin speler statement mislukt: '.$db->error);
                $v1=(string)$gameUid; $v2=$admin; $v3=$adminEmail; $v4='1'; $v5='0'; $v6='250'; $v7='0'; $v8='0'; $v9=(string)$avatarId; $v10='0'; $v11=''; $v12='0'; $v13='0'; $v14='0'; $v15='0'; $v16=(string)time(); $v17='0'; $v18='0'; $v19='0'; $v20='0'; $v21='0'; $v22='0'; $v23=(string)time(); $v24='0'; $v25='0';
                $q->bind_param('sssssssssssssssssssssssss',$v1,$v2,$v3,$v4,$v5,$v6,$v7,$v8,$v9,$v10,$v11,$v12,$v13,$v14,$v15,$v16,$v17,$v18,$v19,$v20,$v21,$v22,$v23,$v24,$v25);
                if(!$q->execute()) throw new RuntimeException('Admin speler aanmaken mislukt: '.$q->error);
                $q->close();
            }

            $db->query("UPDATE global_server_data SET genmap='2' WHERE sid=1");

            file_put_contents(__DIR__.'/.installed',date('c').' uid='.$uid,LOCK_EX);
            session_regenerate_id(true);
            $_SESSION['install_admin_uid']=$uid;
            header('Location: /mellon/authentication/login/',true,302);
            exit;
        }catch(Throwable $e){
            $errors[]=$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Travian Kingdoms installatie</title>
<style>body{font-family:Arial;background:#eee;margin:0;padding:40px}.box{max-width:620px;margin:auto;background:#fff;padding:30px;border-radius:8px}input{display:block;width:100%;box-sizing:border-box;margin:7px 0 14px;padding:10px}.err{background:#fee;border:1px solid #d88;padding:10px;margin:8px 0}button{padding:12px 24px}</style>
</head><body><div class="box"><h1>Travian Kingdoms installatie</h1>
<?php foreach($errors as $e):?><div class="err"><?=h($e)?></div><?php endforeach;?>
<form method="post" action="/install.php">
<label>Database host</label><input name="db_host" value="<?=h($_POST['db_host']??'127.0.0.1')?>">
<label>Database gebruiker</label><input name="db_user" value="<?=h($_POST['db_user']??'root')?>">
<label>Database wachtwoord</label><input type="password" name="db_pass">
<label>Database naam</label><input name="db_name" value="<?=h($_POST['db_name']??'travian_kingdoms')?>">
<label>Admin gebruikersnaam</label><input name="admin_user" value="<?=h($_POST['admin_user']??'admin')?>">
<label>Admin wachtwoord</label><input type="password" name="admin_pass" minlength="8" required>
<label>Servernaam</label><input name="server_name" value="<?=h($_POST['server_name']??'Kingdoms')?>">
<label>Wereldsnelheid</label><input type="number" name="speed_world" min="1" value="<?=h($_POST['speed_world']??1)?>">
<label>Troepensnelheid</label><input type="number" name="speed_unit" min="1" value="<?=h($_POST['speed_unit']??1)?>">
<button type="submit">Installeren</button></form></div></body></html>