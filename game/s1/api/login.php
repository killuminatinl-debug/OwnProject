<?php

$ignoreLoad = true;
include_once('../engine/engine.php');
$msid = isset($_GET['msid']) ? (string)$_GET['msid'] : '';
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($msid !== '' && hash_equals(md5($msid), $token)) {
    $id = 0;
    $uid = 0;
    $mellon = $engine->database->getmsid($msid);
    if ($mellon === false) {
        header("Location: " . $lobby_url);
        exit;
    }
    $_SESSION['mellon_uid'] = $mellon['uid'];
    $_SESSION['mellon_username'] = $mellon['username'];
    $_SESSION['mellon_email'] = $mellon['email'];
    $_SESSION['mellon_msid'] = $msid;


    //$id = $engine->account->FPLogin();
    if (query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `email`=?;", array($_SESSION['mellon_email']))->rowCount() == 0) {
        query("INSERT INTO `global_avatar` (`email`) VALUES (?);", array($_SESSION['mellon_email']));
        $aid = $engine->sql->lastInsertId();
        $_SESSION[$engine->server->prefix . 'avatar'] = $aid;

        $uid = (int)query("SELECT COALESCE(MAX(`uid`),100) + 1 FROM `" . $engine->server->prefix . "user`")->fetchColumn();
        query("INSERT INTO `" . $engine->server->prefix . "user` (`uid`,`username`,`email`,`gold`,`silver`,`avatar`,`lastLogin`,`tribe`,`tutorial`) VALUES (?,?,?,?,?,?,?,?,?);", array($uid, $_SESSION['mellon_username'], $_SESSION['mellon_email'], $engine->account->start_gold, 0, $aid, time(), 1, 0));
        $_SESSION[$engine->server->prefix . 'uid'] = $uid;
        $_SESSION[$engine->server->prefix . 'tribe'] = 1;
        $_SESSION[$engine->server->prefix . 'gold'] = $engine->account->start_gold;
        $_SESSION[$engine->server->prefix . 'silver'] = 0;
        $_SESSION[$engine->server->prefix . 'tutorial'] = 0;
        query("INSERT INTO `" . $engine->server->prefix . "setting` (`email`,`lang`,`uid`) VALUES (?,?,?);", array($_SESSION['mellon_email'], 'en', $uid));
        /* First login: create a real playable avatar, kingdom and village. */
        $kid = $engine->kingdom->create(substr(preg_replace('/[^A-Za-z0-9]/', '', $_SESSION['mellon_username']), 0, 12) ?: 'Kingdom');
        query("UPDATE `" . $engine->server->prefix . "user` SET `tribe`=1, `kingdom`=? WHERE `uid`=?", [$kid, $uid]);
        $_SESSION[$engine->server->prefix . 'tribe'] = 1;
        if ((int)query("SELECT COUNT(*) FROM `" . $engine->server->prefix . "village` WHERE `owner`=?", [$uid])->fetchColumn() === 0) {
            $engine->village->createVillage($uid, $_SESSION['mellon_username']);
        }
    } else {
        $u = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `email`=?;", array($_SESSION['mellon_email']))->fetch();
        $uid = $u['uid'];
        $_SESSION[$engine->server->prefix . 'avatar'] = $u['avatar'];
        $_SESSION[$engine->server->prefix . 'tribe'] = $u['tribe'];
        $_SESSION[$engine->server->prefix . 'uid'] = $u['uid'];
        $_SESSION[$engine->server->prefix . 'gold'] = $u['gold'];
        $_SESSION[$engine->server->prefix . 'silver'] = $u['silver'];
        $_SESSION[$engine->server->prefix . 'tutorial'] = $u['tutorial'];
    }

    /* Repair legacy/incomplete avatars as well: every logged-in player must
       have a tribe, kingdom and at least one village before the game loads. */
    $player = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?", [$uid])->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        header("Location: " . $lobby_url);
        exit;
    }
    if ((int)$player['tribe'] < 1) {
        query("UPDATE `" . $engine->server->prefix . "user` SET `tribe`=1 WHERE `uid`=?", [$uid]);
        $_SESSION[$engine->server->prefix . 'tribe'] = 1;
    }
    if ((int)$player['kingdom'] === 0) {
        $kid = $engine->kingdom->create(substr(preg_replace('/[^A-Za-z0-9]/', '', $_SESSION['mellon_username']), 0, 12) ?: 'Kingdom');
        query("UPDATE `" . $engine->server->prefix . "user` SET `kingdom`=? WHERE `uid`=?", [$kid, $uid]);
    }
    if ((int)query("SELECT COUNT(*) FROM `" . $engine->server->prefix . "village` WHERE `owner`=?", [$uid])->fetchColumn() === 0) {
        $engine->village->createVillage($uid, $_SESSION['mellon_username']);
    }
}

    setcookie('t5SessionKey', json_encode(array("key" => session_id(), "id" => $uid)), time() + 14400, "/");
    header("Location: ../#msid=" . rawurlencode($msid));
    exit;
} else {
    header("Location: " . $lobby_url);
    exit;
}

