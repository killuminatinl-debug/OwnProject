<?php
require_once dirname(__DIR__).'/config.php'; session_start();
if(!empty($_SESSION['own_admin'])){header('Location:index.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(hash_equals(ADMIN_USERNAME,(string)($_POST['username']??''))&&password_verify((string)($_POST['password']??''),ADMIN_PASSWORD_HASH)){
  session_regenerate_id(true);$_SESSION['own_admin']=true;header('Location:index.php');exit;
 } $error='Ongeldige gegevens.';
}
?><!doctype html><html><body style="background:#20252b;color:#fff;font:15px Arial"><form method="post" style="max-width:360px;margin:80px auto;background:#303740;padding:25px"><h2>Kingdoms Admin</h2><p><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></p><input name="username" placeholder="Gebruiker" required style="width:100%;box-sizing:border-box;padding:10px"><br><br><input type="password" name="password" placeholder="Wachtwoord" required style="width:100%;box-sizing:border-box;padding:10px"><br><br><button>Inloggen</button></form></body></html>