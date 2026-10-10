<?php
require __DIR__.'/bootstrap.php';
if(me()){header('Location: game.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'login'; $username=trim($_POST['username']??''); $password=(string)($_POST['password']??'');
 try {
  if($action==='register'){
   $email=trim($_POST['email']??'');$tribe=(int)($_POST['tribe']??1);
   if(!preg_match('/^[A-Za-z0-9_]{3,20}$/',$username))throw new RuntimeException('Gebruikersnaam: 3–20 letters, cijfers of underscores.');
   if(strlen($password)<10)throw new RuntimeException('Wachtwoord moet minimaal 10 tekens bevatten.');
   if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Vul een geldig e-mailadres in.');
   if(!isset(tribes()[$tribe]))$tribe=1;
   db()->beginTransaction();$st=q('INSERT INTO op_players(username,email,password_hash,tribe,created_at) VALUES(?,?,?,?,?)',[$username,$email,password_hash($password,PASSWORD_DEFAULT),$tribe,now()]);$uid=(int)db()->lastInsertId();
   $used=[];while(true){$x=random_int(-100,100);$y=random_int(-100,100);if(!one('SELECT id FROM op_villages WHERE x=? AND y=?',[$x,$y]))break;}
   q('INSERT INTO op_villages(owner_id,name,x,y,wood,clay,iron,crop,last_tick) VALUES(?,?,?,?,750,750,750,750,?)',[$uid,'Nieuw dorp',$x,$y,now()]);$vid=(int)db()->lastInsertId();
   $ft=[1,1,1,1,1,1,2,2,2,2,2,2,3,3,3,3,4,4];foreach($ft as $i=>$t)q('INSERT INTO op_fields(village_id,slot,type,level) VALUES(?,?,?,0)',[$vid,$i+1,$t]);
   for($slot=19;$slot<=40;$slot++)q('INSERT INTO op_buildings(village_id,slot,type,level) VALUES(?,?,?,?)',[$vid,$slot,$slot===19?15:0,$slot===19?1:0]);
   q('INSERT INTO op_hero(player_id) VALUES(?)',[$uid]);q("INSERT INTO op_quests(player_id,quest_key,status,progress) VALUES(?,'first_field',0,0)",[$uid]);db()->commit();
   session_regenerate_id(true);$_SESSION['uid']=$uid;$_SESSION['village_id']=$vid;header('Location: game.php');exit;
  }
  $p=one('SELECT * FROM op_players WHERE username=?',[$username]);
  if(!$p||!password_verify($password,$p['password_hash']))throw new RuntimeException('Onjuiste gebruikersnaam of wachtwoord.');
  session_regenerate_id(true);$_SESSION['uid']=(int)$p['id'];unset($_SESSION['village_id']);header('Location: game.php');exit;
 } catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e instanceof PDOException?'Account bestaat al of databasefout.':$e->getMessage();}
}
?><!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kingdoms — aanmelden</title><style>body{margin:0;background:#d8c89f;color:#302a20;font:15px Arial}.box{max-width:420px;margin:7vh auto;background:#f5e8c6;border:1px solid #9b845a;border-radius:8px;padding:24px;box-shadow:0 4px 16px #0002}h1{font:30px Georgia;color:#5b482d}label{display:block;margin-top:12px}input,select{width:100%;box-sizing:border-box;padding:10px;margin-top:5px;border:1px solid #9b845a;border-radius:4px}button{padding:10px 14px;margin-top:15px;background:#755326;color:white;border:1px solid #4b3619;border-radius:4px}.error{padding:10px;background:#f1d1c8;margin:12px 0}.tabs{display:flex;gap:8px}.tabs button{flex:1}</style></head><body><div class="box"><h1>Kingdoms</h1><p>Log in op je wereld of maak een account aan.</p><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><div class="tabs"><button type="button" onclick="document.getElementById('login').hidden=false;document.getElementById('register').hidden=true">Inloggen</button><button type="button" onclick="document.getElementById('login').hidden=true;document.getElementById('register').hidden=false">Registreren</button></div><form id="login" method="post"><input type="hidden" name="action" value="login"><label>Gebruikersnaam</label><input name="username" required autocomplete="username"><label>Wachtwoord</label><input type="password" name="password" required autocomplete="current-password"><button>Inloggen</button></form><form id="register" method="post" hidden><input type="hidden" name="action" value="register"><label>Gebruikersnaam (3–20)</label><input name="username" required minlength="3" maxlength="20"><label>E-mail</label><input type="email" name="email" required><label>Stam</label><select name="tribe"><option value="1">Romeinen</option><option value="2">Germanen</option><option value="3">Galliërs</option></select><label>Wachtwoord (minimaal 10 tekens)</label><input type="password" name="password" required minlength="10"><button>Account maken</button></form></div></body></html>