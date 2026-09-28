<?php

class Database {

    function listServer($full = false) {
        global $engine;
        $s = query("SELECT * FROM `global_server_data`")->fetchAll(PDO::FETCH_ASSOC);
        $r = array();
        for ($i = 0; $i < count($s); $i++) {
            $r[$i] = $this->getInfo($s[$i]);
        }
        return $r;
    }

    public function msid($email = "abcdefghijklmnopqrstuvwxyz") {
        $token = rand(0, 1000);
        $token = $token . $email . rand(1000, 10000);
        $token = hash('adler32', $token);
        $token = time() . $token;
        $token = hash('crc32b', $token);
        $token = $token . hash('crc32', $token);

        $q = query("SELECT * FROM `global_msid` WHERE `email`=? ORDER BY uid DESC LIMIT 1", array($email));
        if ($q->rowCount() == 1) {
            $t = $q->fetch(PDO::FETCH_ASSOC);
            $token = $t['token'];
        } else {
            query("DELETE FROM `global_msid` WHERE `email`=? AND `ip`=?", array($email, $_SERVER['REMOTE_ADDR']));
            query("INSERT INTO `global_msid` (`token`,`email`,`ip`) VALUES (?,?,?);", array($token, $email, $_SERVER['REMOTE_ADDR']));
        }
        return $token;
    }

    public function getInfo($world) {
        global $engine;

        if (!is_array($world)) {
            $world = query("SELECT * FROM `global_server_data` WHERE `sid`=?;",[$world])->fetch(PDO::FETCH_ASSOC);
        }
        $r = [
            "consumersId" => $world['sid'],
            "identifier" => "travian-ks-en-" . $world['tag'],
            "country" => "en",
            "region" => "international",
            "status" => 2,
            "gameId" => 30,
            "applicationId" => "travian-ks",
            "applicationCountryId" => "en",
            "applicationInstanceId" => $world['tag'],
            "worldName" => $world['name'],
            "worldStartTime" => $world['start'],
            "playersRegistered" => query("SELECT * FROM `" . $world['prefix'] . "user`")->rowCount(),
            "playersActive" => 0,
            "playersOnline" => 0,
            "worldCapacity" => 0,
            "recommended" => (int) $world['recommended'],
            "blacklisted" => 0,
            "baseUrl" => "",
            "daysSinceStart" => round((time() - $world['start']) / 86400),
            "speedGame" => $world['speed_world'],
            "speedTroops" => $world['speed_unit'],
            "specialRules" => ["nightPeace"], //"cropDiet"
            "canTransferMoney" => 1,
            "tribes" => [
                "1" => query("SELECT * FROM `" . $world['prefix'] . "user` WHERE `tribe`=?", [1])->rowCount(),
                "2" => query("SELECT * FROM `" . $world['prefix'] . "user` WHERE `tribe`=?", [2])->rowCount(),
                "3" => query("SELECT * FROM `" . $world['prefix'] . "user` WHERE `tribe`=?", [3])->rowCount(),
            ],
            "wwIsActivated" => 0,
            "currentWWLevel" => 0,
            "maxWWLevel" => 100,
        ];
        return $r;
    }

    public function getServerInfo($world) {
        global $engine;
        return query("SELECT * FROM `global_server_data` WHERE `sid`=?;", array($world))->fetch(PDO::FETCH_ASSOC);
    }

}
