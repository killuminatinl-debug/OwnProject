<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
$done=false; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  $host=trim($_POST['host']??'127.0.0.1'); $user=trim($_POST['user']??'root'); $pass=(string)($_POST['pass']??'');
  $name=preg_replace('/[^A-Za-z0-9_]/','',$_POST['name']??'travian_kingdoms');
  $admin=trim($_POST['admin']??'admin'); $email=trim($_POST['email']??'admin@example.local'); $pw=(string)($_POST['admin_password']??'');
  if(!$name || strlen($admin)<3 || strlen($pw)<10 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Vul een geldige database, beheerdersnaam, e-mail en wachtwoord van minimaal 10 tekens in.');
  $pdo=new PDO("mysql:host=$host;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $pdo->exec("USE `$name`");
  $schema=[
   "CREATE TABLE IF NOT EXISTS op_worlds(id INT PRIMARY KEY, name VARCHAR(100) NOT NULL, speed DECIMAL(8,2) NOT NULL DEFAULT 1, created_at INT NOT NULL)",
   "CREATE TABLE IF NOT EXISTS op_players(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(40) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, tribe TINYINT NOT NULL DEFAULT 1, is_admin TINYINT NOT NULL DEFAULT 0, gold INT NOT NULL DEFAULT 0, silver INT NOT NULL DEFAULT 0, culture_points INT NOT NULL DEFAULT 0, kingdom_id INT NULL, created_at INT NOT NULL)",
   "CREATE TABLE IF NOT EXISTS op_villages(id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT NOT NULL, name VARCHAR(80) NOT NULL, x INT NOT NULL, y INT NOT NULL, wood DECIMAL(12,2) NOT NULL DEFAULT 750, clay DECIMAL(12,2) NOT NULL DEFAULT 750, iron DECIMAL(12,2) NOT NULL DEFAULT 750, crop DECIMAL(12,2) NOT NULL DEFAULT 750, warehouse_cap INT NOT NULL DEFAULT 800, granary_cap INT NOT NULL DEFAULT 800, population INT NOT NULL DEFAULT 2, crop_upkeep INT NOT NULL DEFAULT 0, last_tick INT NOT NULL, UNIQUE KEY coord(x,y), KEY owner(owner_id))",
   "CREATE TABLE IF NOT EXISTS op_fields(id INT AUTO_INCREMENT PRIMARY KEY, village_id INT NOT NULL, slot TINYINT NOT NULL, type TINYINT NOT NULL, level TINYINT NOT NULL DEFAULT 0, UNIQUE KEY village_slot(village_id,slot))",
   "CREATE TABLE IF NOT EXISTS op_buildings(id INT AUTO_INCREMENT PRIMARY KEY, village_id INT NOT NULL, slot TINYINT NOT NULL, type TINYINT NOT NULL DEFAULT 0, level TINYINT NOT NULL DEFAULT 0, UNIQUE KEY village_slot(village_id,slot))",
   "CREATE TABLE IF NOT EXISTS op_build_queue(id INT AUTO_INCREMENT PRIMARY KEY, village_id INT NOT NULL, slot TINYINT NOT NULL, building_type TINYINT NOT NULL, level TINYINT NOT NULL, action_type TINYINT NOT NULL DEFAULT 0, start_at INT NOT NULL, finish_at INT NOT NULL, KEY due(village_id,finish_at))",
   "CREATE TABLE IF NOT EXISTS op_units(id INT AUTO_INCREMENT PRIMARY KEY, village_id INT NOT NULL, unit_type INT NOT NULL, amount INT NOT NULL DEFAULT 0, UNIQUE KEY village_unit(village_id,unit_type))",
   "CREATE TABLE IF NOT EXISTS op_market(id INT AUTO_INCREMENT PRIMARY KEY, seller_id INT NOT NULL, village_id INT NOT NULL, give_type TINYINT NOT NULL, give_amount INT NOT NULL, want_type TINYINT NOT NULL, want_amount INT NOT NULL, created_at INT NOT NULL)",
   "CREATE TABLE IF NOT EXISTS op_hero(id INT AUTO_INCREMENT PRIMARY KEY, player_id INT NOT NULL UNIQUE, level INT NOT NULL DEFAULT 1, xp INT NOT NULL DEFAULT 0, strength INT NOT NULL DEFAULT 10, health INT NOT NULL DEFAULT 100)",
   "CREATE TABLE IF NOT EXISTS op_quests(id INT AUTO_INCREMENT PRIMARY KEY, player_id INT NOT NULL, quest_key VARCHAR(80) NOT NULL, status TINYINT NOT NULL DEFAULT 0, progress INT NOT NULL DEFAULT 0, UNIQUE KEY player_quest(player_id,quest_key))",
   "CREATE TABLE IF NOT EXISTS op_reports(id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT NOT NULL, title VARCHAR(190) NOT NULL, body TEXT NOT NULL, created_at INT NOT NULL)",
   "CREATE TABLE IF NOT EXISTS op_kingdoms(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, tag VARCHAR(8) NOT NULL, king_id INT NOT NULL, victory_points INT NOT NULL DEFAULT 0, treasures INT NOT NULL DEFAULT 0, created_at INT NOT NULL)"
  ];
  foreach($schema as $sql) $pdo->exec($sql);
  $now=time();
  $st=$pdo->prepare("INSERT INTO op_worlds(id,name,speed,created_at) VALUES(1,'World 1',1,?) ON DUPLICATE KEY UPDATE id=id"); $st->execute([$now]);
  $st=$pdo->prepare("SELECT id FROM op_players WHERE username=? OR email=?"); $st->execute([$admin,$email]); $existing=$st->fetch();
  if(!$existing) {
   $st=$pdo->prepare("INSERT INTO op_players(username,email,password_hash,tribe,is_admin,gold,silver,culture_points,created_at) VALUES(?,?,?,1,1,100,0,0,?)");
   $st->execute([$admin,$email,password_hash($pw,PASSWORD_DEFAULT),$now]); $pid=(int)$pdo->lastInsertId();
   $st=$pdo->prepare("INSERT INTO op_villages(owner_id,name,x,y,wood,clay,iron,crop,last_tick) VALUES(?,?,?,?,750,750,750,750,?)"); $st->execute([$pid,'Hoofddorp',0,0,$now]); $vid=(int)$pdo->lastInsertId();
   $fieldTypes=[1,1,1,1,1,1,2,2,2,2,2,2,3,3,3,3,4,4];
   $st=$pdo->prepare("INSERT INTO op_fields(village_id,slot,type,level) VALUES(?,?,?,0)"); foreach($fieldTypes as $i=>$t)$st->execute([$vid,$i+1,$t]);
   $st=$pdo->prepare("INSERT INTO op_buildings(village_id,slot,type,level) VALUES(?,?,?,?)");
   for($slot=19;$slot<=40;$slot++)$st->execute([$vid,$slot,$slot===19?15:0,$slot===19?1:0]);
   $st=$pdo->prepare("INSERT INTO op_hero(player_id) VALUES(?)");$st->execute([$pid]);
   $st=$pdo->prepare("INSERT INTO op_quests(player_id,quest_key,status,progress) VALUES(?,'first_field',0,0)");$st->execute([$pid]);
  } else {
   $st=$pdo->prepare("UPDATE op_players SET is_admin=1,password_hash=? WHERE id=?");$st->execute([password_hash($pw,PASSWORD_DEFAULT),$existing['id']]);
  }
  $config="<?php\nif (!defined('DB_HOST')) define('DB_HOST',".var_export($host,true).");\nif (!defined('DB_USER')) define('DB_USER',".var_export($user,true).");\nif (!defined('DB_PASS')) define('DB_PASS',".var_export($pass,true).");\nif (!defined('DB_NAME')) define('DB_NAME',".var_export($name,true).");\nif (!defined('SQL_HOST')) define('SQL_HOST',DB_HOST);\nif (!defined('SQL_USER')) define('SQL_USER',DB_USER);\nif (!defined('SQL_PASS')) define('SQL_PASS',DB_PASS);\nif (!defined('SQL_DATB')) define('SQL_DATB',DB_NAME);\nif (!defined('LANGUAGE')) define('LANGUAGE','nl');\n";
  if(file_put_contents(__DIR__.'/config.php',$config)===false) throw new RuntimeException('Kan config.php niet schrijven; controleer maprechten.');
  $done=true;
 } catch(Throwable $e) { $error=$e->getMessage(); error_log('[Kingdoms installer] '.$e->getMessage()); }
}
?><!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kingdoms installatie</title><style>body{font:15px Arial;background:#d8c89f;margin:0;color:#302a20}.box{max-width:620px;margin:35px auto;background:#f6ebce;padding:28px;border:1px solid #9b845a;border-radius:8px}label{display:block;margin-top:12px;font-weight:bold}input{box-sizing:border-box;width:100%;padding:10px;margin-top:5px;border:1px solid #9b845a;border-radius:4px}button{margin-top:18px;padding:12px 18px;background:#755326;color:white;border:1px solid #4b3619;border-radius:4px;font-weight:bold}.err,.ok{padding:12px;margin:12px 0;white-space:pre-wrap}.err{background:#f4d4ca}.ok{background:#dcebd2}</style></head><body><div class="box"><h1>Kingdoms</h1><h2>Installatie</h2>
<?php if($done):?><div class="ok"><b>Installatie afgerond.</b><br>Beheerdersaccount is ingesteld. Bewaar je wachtwoord en verwijder of beveilig install.php na de installatie.</div><p><a href="index.php">Open het spel</a></p><?php else:?><?php if($error):?><div class="err"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif;?><form method="post"><label>MySQL host</label><input name="host" value="<?=htmlspecialchars($_POST['host']??'127.0.0.1',ENT_QUOTES,'UTF-8')?>"><label>MySQL gebruiker</label><input name="user" value="<?=htmlspecialchars($_POST['user']??'root',ENT_QUOTES,'UTF-8')?>"><label>MySQL wachtwoord</label><input type="password" name="pass"><label>Databasenaam</label><input name="name" value="<?=htmlspecialchars($_POST['name']??'travian_kingdoms',ENT_QUOTES,'UTF-8')?>"><label>Beheerdersnaam</label><input name="admin" value="<?=htmlspecialchars($_POST['admin']??'admin',ENT_QUOTES,'UTF-8')?>"><label>Beheerders e-mail</label><input type="email" name="email" value="<?=htmlspecialchars($_POST['email']??'admin@example.local',ENT_QUOTES,'UTF-8')?>"><label>Beheerderswachtwoord (minimaal 10 tekens)</label><input type="password" name="admin_password" required minlength="10"><button type="submit">Installeren</button></form><p>Deze installer maakt de tabellen voor de huidige PHP-game-engine aan zonder bestaande tabellen te verwijderen. Test eerst op een kopie van je database.</p><?php endif;?></div></body></html>