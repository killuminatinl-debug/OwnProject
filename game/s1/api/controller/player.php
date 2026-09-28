<?php

if ($data['action'] == "ping") {
    echo json_encode(array(
        "response" => array("data" => 0),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
        "cache" => array()
    ));
} elseif ($data['action'] == "chooseTribe") {
    $uid = (int)$_SESSION[$engine->server->prefix . 'uid'];
    $tribe = (int)($data['params']['tribeId'] ?? 0);
    if ($tribe < 1 || $tribe > 7) {
        echo json_encode(array("response"=>array("error"=>"INVALID_TRIBE"),"serialNo"=>$engine->session->serialNo(),"time"=>round(microtime(true)*1000)));
        exit();
    }

    query("UPDATE `" . $engine->server->prefix . "user` SET `tribe`=?,`tutorial`=? WHERE `uid`=?;", array($tribe,256,$uid));
    $_SESSION[$engine->server->prefix . 'tutorial'] = 256;
    $_SESSION[$engine->server->prefix . 'tribe'] = $tribe;

    $existing = query("SELECT `wid` FROM `" . $engine->server->prefix . "village` WHERE `owner`=? ORDER BY `wid` ASC LIMIT 1", array($uid))->fetch(PDO::FETCH_ASSOC);
    $vid = $existing ? (int)$existing['wid'] : (int)(-10000-$uid);

    if (!$existing) {
        $engine->village->createVillage($uid, $_SESSION[$engine->server->prefix . 'username'], $vid);
        $engine->building->setBuilding($vid, 20, 10, 0, true);
        $engine->building->setBuilding($vid, 23, 8, 0, true);
        $engine->building->setBuilding($vid, 27, 15, 3);
        $engine->building->setBuilding($vid, 31, 22, 0, true);
        $engine->building->setBuilding($vid, 32, 16, 1);
        $engine->building->setBuilding($vid, 33, 33, 0);
        $engine->building->setBuilding($vid, 34, 18, 0, true);
        $engine->building->setBuilding($vid, 35, 11, 0, true);
        $engine->building->setBuilding($vid, 39, 17, 0, true);
        $engine->building->setBuilding($vid, 40, 13, 0, true);
        $engine->unit->addUnit($vid, 1, 5, $uid);
        $engine->unit->addUnit($vid, 2, 12, $uid);
        $engine->unit->addUnit($vid, 11, 1, $uid);
    }
    setcookie("village", $vid, 0, '/');

    echo json_encode(array(
        "response" => array(),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
        "event" => array(array("name"=>"clearCache","data"=>array())),
        "cache" => $engine->cache->getAll(),
    ));
} elseif ($data['action'] == "selectVillageDirection") {
    $vid = $engine->world->bestPosition($data['params']['direction']);
    $vid = $vid[0];
    $_COOKIE['village'] = $vid;
    $engine->village->createVillage($_SESSION[$engine->server->prefix . 'uid'], $_SESSION[$engine->server->prefix . 'username'], $vid);

    setcookie("village", $vid, 0, $engine->page->baseURI() . '/');

    echo json_encode(array(
        'cache' => $engine->cache->getAll(),
        'time' => round(microtime(true) * 1000),
        'serialNo' => $engine->session->serialNo(),
        'event' => [
            [
                'name' => 'clearCache',
                'data' => []
            ],
        ],
        'response' => [],
    ));
    exit();
} elseif ($data['action'] == "getAll") {
    echo json_encode(array(
        'cache' => $engine->cache->getAll(),
        'time' => round(microtime(true) * 1000),
        'serialNo' => $engine->session->serialNo(),
        'event' => [
            [
                'name' => 'clearCache',
                'data' => [],
            ],
        /* [
          'name' => 'ShowWelcomeScreen',
          'data' => [],
          ] */
        ],
        'response' => array(),
    ));
    exit();
} elseif ($data['action'] == "getInvitationRefLink") {
    echo json_encode(array(
        "response" => array("refLink" => "http:\/\/kingdoms.travian.com\/com\/#action=register;referral=V2999452"),
        "serialNo" => $engine->session->serialNo(),
    ));
} elseif ($data['action'] == "sendTrackingEvent") {
    echo json_encode(array(
        "response" => array(),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getSystemMessage") {
    echo json_encode(array(
        "response" => array(
            "title" => "Test - ทดสอบระบบ",
            "text" => "เซิฟเวอร์นี้กำลังอยู่ในขั้นตอนการทดสอบ ยังไม่สามารถให้บริการได้สมบูรณ์แบบ<br>มีปัญหาติดต่อ <a target='_blank' href='https://fb.com/phoomin2012'>Phumin Devp</a><hr>",
        ),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getPlayerInfo") {
    echo json_encode(array(
        "response" => array(
            "language" => "en",
            "populationRank" => $engine->ranking->getUserRank() - 1,
        ),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getPrestigeConditions") {
    echo json_encode(array(
        "response" => $engine->account->getPrestige(),
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getOpenChatWindows") {
    echo json_encode(array(
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getRobberVillagesAmount") {
    echo json_encode(array(
        "response" => [
            "data" => 0
        ],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getActivityStreams") {
    echo json_encode(array(
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "deleteNotification") {
    $engine->notification->deleteByType($_SESSION[$engine->server->prefix . 'uid'], $data['params']['type']);
    echo json_encode(array(
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "deleteAllNotifications") {
    $engine->notification->delete($_SESSION[$engine->server->prefix . 'uid'], true);
    echo json_encode(array(
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "updatePlayerProfileContent") {
    echo json_encode(array(
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "getCardgameResult") {
    echo json_encode(array(
        "response" => [
            "result" => null,
        ],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "changeSettings") {
    $engine->setting->change($data['params']['newSettings']);
    echo json_encode([
        "cache" => [
            $engine->setting->getAll(true)
        ],
        "response" => [],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ]);
} elseif ($data['action'] == "editProfile") {
    $engine->account->edit('desc', $data['params']['description']);
    echo json_encode([
        "cache" => [
            $engine->account->getProfile()
        ],
        "response" => ["data" => true],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ]);
}