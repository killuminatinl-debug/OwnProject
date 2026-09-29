<?php

$ignoreLoad = true;
include_once('../engine/engine.php');
if ($_GET['token'] == md5($_GET['msid'])) {
    $id = 0;
    $mellon = $engine->database->getmsid($_GET['msid']);
    if ($mellon == false) {
        header("Location: ".$lobby_url);
    }
    $_SESSION['mellon_uid'] = $mellon['uid'];
    $_SESSION['mellon_username'] = $mellon['username'];
    $_SESSION['mellon_email'] = $mellon['email'];
    $_SESSION['mellon_msid'] = $_GET['token'];


    //$id = $engine->account->FPLogin();
    if (query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `email`=?;", array($_SESSION['mellon_email']))->rowCount() == 0) {
        query("INSERT INTO `global_avatar` (`email`) VALUES (?);", array($_SESSION['mellon_email']));
        $aid = $engine->sql->lastInsertId();
        $_SESSION[$engine->server->prefix . 'avatar'] = $aid;

        $numUser = query("SELECT * FROM `" . $engine->server->prefix . "user`")->rowCount();
        $uid = $numUser + 101;
        query("INSERT INTO `" . $engine->server->prefix . "user` (`uid`,`email`,`username`,`gold`,`silver`,`avatar`,`lastLogin`,`tribe`,`tutorial`,`spawn`) VALUES (?,?,?,?,?,?,?,?,?,?);", array($uid, $_SESSION['mellon_email'], $_SESSION['mellon_username'], $engine->account->start_gold, 0, $aid, time(), 0, 0, time()));
        $_SESSION[$engine->server->prefix . 'uid'] = $uid;
        $_SESSION[$engine->server->prefix . 'username'] = $_SESSION['mellon_username'];
        $_SESSION[$engine->server->prefix . 'tribe'] = 0;
        $_SESSION[$engine->server->prefix . 'gold'] = $engine->account->start_gold;
        $_SESSION[$engine->server->prefix . 'silver'] = 0;
        $_SESSION[$engine->server->prefix . 'tutorial'] = 0;
        query("INSERT INTO `" . $engine->server->prefix . "setting` (`email`,`lang`,`uid`) VALUES (?,?,?);", array($_SESSION['mellon_email'], 'en', $uid));
    } else {
        $u = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `email`=?;", array($_SESSION['mellon_email']))->fetch();
        $uid = $u['uid'];
        $_SESSION[$engine->server->prefix . 'avatar'] = $u['avatar'];
        $_SESSION[$engine->server->prefix . 'tribe'] = $u['tribe'];
        $_SESSION[$engine->server->prefix . 'uid'] = $u['uid'];
        $_SESSION[$engine->server->prefix . 'gold'] = $u['gold'];
        $_SESSION[$engine->server->prefix . 'silver'] = $u['silver'];
        $_SESSION[$engine->server->prefix . 'username'] = !empty($u['username']) ? $u['username'] : $_SESSION['mellon_username'];
        if (empty($u['username'])) query("UPDATE `" . $engine->server->prefix . "user` SET `username`=? WHERE `uid`=?", array($_SESSION['mellon_username'], $u['uid']));
        $_SESSION[$engine->server->prefix . 'tutorial'] = (int)$u['tutorial'];
    }
    // Self-heal accounts created by older/local versions: always enter s1 with a real tribe and village.
    $current=query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=? LIMIT 1",array($uid))->fetch(PDO::FETCH_ASSOC);
    $hasVillage=(int)query("SELECT COUNT(*) FROM `" . $engine->server->prefix . "village` WHERE `owner`=?",array($uid))->fetchColumn();
    if($current && ((int)$current['tribe']<1 || $hasVillage===0)){
        $tribe=((int)$current['tribe']>=1 && (int)$current['tribe']<=7)?(int)$current['tribe']:1;
        query("UPDATE `" . $engine->server->prefix . "user` SET `tribe`=?,`tutorial`=256,`username`=? WHERE `uid`=?",
            array($tribe,(!empty($current['username'])?$current['username']:$_SESSION['mellon_username']),$uid));
        $_SESSION[$engine->server->prefix.'tribe']=$tribe;
        $_SESSION[$engine->server->prefix.'tutorial']=256;
        $_SESSION[$engine->server->prefix.'username']=!empty($current['username'])?$current['username']:$_SESSION['mellon_username'];
        if($hasVillage===0){
            $engine->village->createVillage($uid,$_SESSION[$engine->server->prefix.'username'],null);
        }
    }

}

setcookie('t5SessionKey', (json_encode(array("key" => session_id(), "id" => $uid))), time() + 14400, "/");
header("Location: ../#msid=" . $_GET['msid']);
