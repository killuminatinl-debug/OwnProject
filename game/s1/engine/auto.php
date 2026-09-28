<?php

class Auto {

    var $last = ['pong' => 0, 'freeSilver' => 0, 'hpHero' => 0];

    public function emitEvent($uid = null, $data = []) {
        global $engine;
        if ($uid === null || $uid == "" || is_array($uid)) {
            if (is_array($uid)) {
                $data = $uid;
                $uid = null;
            }
            $online = query("SELECT * FROM `{$engine->server->prefix}user` WHERE `online`=?", [1])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($online as $user) {
                $r = [
                    "event" => [$data],
                    "response" => [],
                    "serial" => $engine->session->serialNo($user['uid']),
                    "ts" => time() * 1000,
                ];
                query("INSERT INTO `{$engine->server->prefix}nodejs` (`uid`,`data`) VALUES (?,?)", array($user['uid'], json_encode($r)));
            }
        } else {
            $r = [
                "event" => [$data],
                "response" => [],
                "serial" => $engine->session->serialNo($uid),
                "ts" => time() * 1000,
            ];
            query("INSERT INTO `{$engine->server->prefix}nodejs` (`uid`,`data`) VALUES (?,?)", array($uid, json_encode($r)));
        }
    }

    public function emitCache($uid, $data = []) {
        global $engine;
        if ($uid === null || $uid == "" || is_array($uid)) {
            if (is_array($uid)) {
                $data = $uid;
                $uid = null;
            }
            $online = query("SELECT * FROM `{$engine->server->prefix}user` WHERE `online`=?", [1])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($online as $user) {
                $serial = $engine->session->serialNo($user['uid']);
                $r = [
                    "cache" => [$data],
                    "response" => [],
                    "serial" => $serial,
                    "serialNo" => $serial,
                    "ts" => time() * 1000,
                ];
                query("INSERT INTO `{$engine->server->prefix}nodejs` (`uid`,`data`) VALUES (?,?)", array($user['uid'], json_encode($r)));
            }
        } else {
            $serial = $engine->session->serialNo($uid);
            $r = [
                "cache" => [$data],
                "response" => [],
                "serial" => $serial,
                "serialNo" => $serial,
                "ts" => time() * 1000,
            ];
            query("INSERT INTO `{$engine->server->prefix}nodejs` (`uid`,`data`) VALUES (?,?)", array($uid, json_encode($r)));
        }
    }

    private function buildComplete() {
        global $engine;

        //Process building queue first 
        $bs = query("SELECT * FROM `" . $engine->server->prefix . "building` WHERE `timestamp`<=? AND `queue`<>?", array(time(), 4))->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($bs); $i++) {
            $b = $bs[$i];
            if ($b['queue'] == "1" || $b['queue'] == "2") {
                //If is normal building (1 is in village / 2 is resource field)

                $f = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=?;", [$b['wid'], $b['location']])->fetch(PDO::FETCH_ASSOC);
                query("UPDATE `" . $engine->server->prefix . "field` SET `level`=`level`+1,`type`=?,`rubble`=0 WHERE `wid`=? AND `location`=?;", [$b['type'], $b['wid'], $b['location']]);
                $level = $f['level'] + 1;

                query("DELETE FROM `" . $engine->server->prefix . "building` WHERE `id`=?;", array($b['id']));
                $bD = BuildingData::get($b['type'], $level);
                query("UPDATE `" . $engine->server->prefix . "village` SET `pop`=`pop`+?,`cp`=`cp`+? WHERE `wid`=?;", array($bD['pop'], $bD['cp'], $b['wid']));
                $engine->world->calInf($b['wid']);

                $v = query("SELECT * FROM `" . $engine->server->prefix . "village` WHERE `wid`=?", array($b['wid']))->fetch(PDO::FETCH_ASSOC);
                $p = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);

                if ($b['type'] == 10) { //Warehouse
                    $max = $engine->village->getVillageField($b['wid'], "maxstore");
                    if ($level == '1' && $max == $engine->server->base_storage) {
                        $max -= $engine->server->base_storage;
                    }
                    if ($level > 1)
                        $max -= BuildingData::get(10, $level - 1)['effect'] * $engine->server->multiple_storage;
                    $max += BuildingData::get(10, $level)['effect'] * $engine->server->multiple_storage;
                    $engine->village->setVillageField($b['wid'], "maxstore", $max);
                }elseif ($b['type'] == 11) { //Granary
                    $max = $engine->village->getVillageField($b['wid'], "maxcrop");
                    if ($level == '1' && $max == $engine->server->base_storage) {
                        $max -= $engine->server->base_storage;
                    }
                    if ($level > 1)
                        $max -= BuildingData::get(11, $level - 1)['effect'] * $engine->server->multiple_storage;
                    $max += BuildingData::get(11, $level)['effect'] * $engine->server->multiple_storage;
                    $engine->village->setVillageField($b['wid'], "maxcrop", $max);
                }elseif ($b['type'] == 25) { //Residence
                    if ($level == 10) {
                        $engine->village->setVillageField($b['wid'], "settler", 3);
                    } elseif ($level == 20) {
                        $engine->village->setVillageField($b['wid'], "settler", 6);
                    }
                } elseif ($b['type'] == 26) { //Palance
                    if ($level == 10) {
                        $engine->village->setVillageField($b['wid'], "settler", 3);
                    } elseif ($level == 15) {
                        $engine->village->setVillageField($b['wid'], "settler", 6);
                    } elseif ($level == 20) {
                        $engine->village->setVillageField($b['wid'], "settler", 9);
                    }
                } elseif ($b['type'] == 17) { //Market
                    $this->emitCache($p['uid'], [
                        "name" => "Merchants:{$b['wid']}",
                        "data" => $engine->market->get($b['wid']),
                    ]);
                }

                if ($p['tutorial'] == 6) {
                    if ($b['location'] == "33") {
                        $engine->account->edit('tutorial', 7, $p['uid']);
                        $engine->auto->emitCache($p['uid'], $engine->quest->get($p['uid']));
                    }
                } elseif ($p['tutorial'] == 8) {
                    if ($b['location'] == "29") {
                        $engine->account->edit('tutorial', 9, $p['uid']);
                        $engine->auto->emitCache($p['uid'], $engine->quest->get($p['uid']));
                        // Reseach unit tier2 free
                        query("UPDATE `" . $engine->server->prefix . "tdata` SET `t2`=? WHERE `wid`=?", array(0, $b['wid']));
                        // Set village resource
                        if ($p['tribe'] == 1)
                            query("UPDATE `" . $engine->server->prefix . "village` SET `wood`=?,`clay`=?,`iron`=?,`crop`=? WHERE `wid`=?", array(240, 300, 480, 300, $b['wid']));
                        elseif ($p['tribe'] == 2)
                            query("UPDATE `" . $engine->server->prefix . "village` SET `wood`=?,`clay`=?,`iron`=?,`crop`=? WHERE `wid`=?", array(375, 150, 195, 300, $b['wid']));
                        elseif ($p['tribe'] == 3)
                            query("UPDATE `" . $engine->server->prefix . "village` SET `wood`=?,`clay`=?,`iron`=?,`crop`=? WHERE `wid`=?", array(255, 300, 150, 300, $b['wid']));
                        else
                            query("UPDATE `" . $engine->server->prefix . "village` SET `wood`=?,`clay`=?,`iron`=?,`crop`=? WHERE `wid`=?", array(500, 500, 500, 300, $b['wid']));
                    }
                } elseif ($p['tutorial'] == 15) {
                    if ($b['type'] == "4") {
                        $engine->account->edit('tutorial', 16, $p['uid']);
                        $engine->auto->emitCache($p['uid'], $engine->quest->get($p['uid']));
                    }
                }

                $this->emitEvent($p['uid'], array(
                    "name" => "flashNotification",
                    "data" => "39",
                ));
                $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                $this->procRes($b['wid']);
                $this->emitCache($p['uid'], $engine->village->get($b['wid']));


                $test_wid = $engine->world->id2xy($b['wid']);
                $test_wid[0] -= 1;
                $test_wid[1] += 1;
                $test_wid = $engine->world->xy2id($test_wid);
                $this->emitEvent([
                    "name" => "invalidateCache",
                    "data" => "MapDetails:{$b['wid']}",
                ]);
                $this->emitEvent([
                    "name" => "mapChanged",
                    "data" => $b['wid'],
                ]);
            } elseif ($b['queue'] == "5") {
                //If is rubble

                $f = query("SELECT * FROM `" . $engine->server->prefix . "field` WHERE `wid`=? AND `location`=?", [$b['wid'], $b['location']])->fetch(PDO::FETCH_ASSOC);
                if ($f['rubble'] != 1) {
                    query("UPDATE `" . $engine->server->prefix . "field` SET `rubble`=? WHERE `wid`=? AND `location`=?", array(1, $b['wid'], $b['location']));
                } else {
                    $bD = BuildingData::get($f['type'], $f['level']);
                    $this->procRes($b['wid']);
                    query("UPDATE `" . $engine->server->prefix . "field` SET `rubble`=?,`level`=?,`type`=? WHERE `wid`=? AND `location`=?", array(0, 0, 0, $b['wid'], $b['location']));
                    query("UPDATE `" . $engine->server->prefix . "village` SET `wood`=`wood`+?,`clay`=`clay`+?,`iron`=`iron`+?,`crop`=`crop`+? WHERE `wid`=?", array($bD['wood'], $bD['clay'], $bD['iron'], $bD['crop'], $b['wid']));
                }

                $v = query("SELECT * FROM `" . $engine->server->prefix . "village` WHERE `wid`=?", array($b['wid']))->fetch(PDO::FETCH_ASSOC);
                $p = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);

                $this->emitEvent($p['uid'], array(
                    "name" => "flashNotification",
                    "data" => "41",
                ));
                query("DELETE FROM `" . $engine->server->prefix . "building` WHERE `id`=?;", array($b['id']));
                $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                $this->procRes($b['wid']);
                $this->emitCache($p['uid'], $engine->village->get($b['wid']));
            }
        }

        //Process master building queue
        $bs = query("SELECT * FROM `" . $engine->server->prefix . "building` WHERE `queue`=?;", [4])->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($bs); $i++) {
            $b = $bs[$i];
            $v = query("SELECT * FROM `" . $engine->server->prefix . "village` WHERE `wid`=?", array($b['wid']))->fetch(PDO::FETCH_ASSOC);
            $p = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);
            $b1 = query("SELECT * FROM `" . $engine->server->prefix . "building` WHERE `wid`=? AND `queue`=? ORDER BY `sort` ASC;", array($b['wid'], 1))->rowCount();
            $b2 = query("SELECT * FROM `" . $engine->server->prefix . "building` WHERE `wid`=? AND `queue`=? ORDER BY `sort` ASC;", array($b['wid'], 2))->rowCount();
            $f = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=?", [$b['wid'], $b['location']])->fetch(PDO::FETCH_ASSOC);
            $level = $f['level'] + 1;

            if ($b['sort'] == 1) {
                if ($p['tribe'] == 1 && (($b1 == 0 && $b['location'] >= 19) || ($b2 == 0 && $b['location'] < 19))) {
                    if ($b['paid'] == 0) {
                        if ($engine->building->StartBuild($b['location'], $b['type'], $b['wid'])) {
                            $this->emitEvent($p['uid'], array(
                                "name" => "flashNotification",
                                "data" => "33",
                            ));
                            query("DELETE FROM `" . $engine->server->prefix . "building` WHERE `id`=?;", array($b['id']));
                            $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                            $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                            $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                            $this->procRes($b['wid']);
                            $this->emitCache($p['uid'], $engine->village->get($b['wid']));
                        }
                    } else {

                        $bD = BuildingData::get($b['type'], $level);
                        $time = time() + round(($bD['time'] * ($engine->building->BuildingEffect(15, $engine->building->getTypeLevel($b['wid'], 15)) / 100)) / $engine->server->speed_world);
                        $queue = ($b['location'] < 19) ? 2 : 1;
                        query("UPDATE `" . $engine->server->prefix . "building` SET `start`=?,`timestamp`=?,`queue`=? WHERE `id`=?", array(time(), $time, $queue, $b['id']));
                        $this->emitEvent($p['uid'], array(
                            "name" => "flashNotification",
                            "data" => "33",
                        ));
                        $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                        $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                        $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                        $this->procRes($b['wid']);
                        $this->emitCache($p['uid'], $engine->village->get($b['wid']));
                    }
                } elseif ($b1 + $b2 == 0) {
                    if ($b['paid'] == 0) {
                        if ($engine->building->StartBuild($b['location'], $b['type'], $b['wid'])) {
                            $this->emitEvent($p['uid'], array(
                                "name" => "flashNotification",
                                "data" => "33",
                            ));
                            query("DELETE FROM `" . $engine->server->prefix . "building` WHERE `id`=?;", array($b['id']));
                            $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                            $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                            $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                            $this->procRes($b['wid']);
                            $this->emitCache($p['uid'], $engine->village->get($b['wid']));
                        }
                    } else {
                        $bD = BuildingData::get($b['type'], $level);
                        $time = time() + round(($bD['time'] * ($engine->building->BuildingEffect(15, $engine->building->getTypeLevel($b['wid'], 15)) / 100)) / $engine->server->speed_world);
                        $queue = ($b['location'] < 19) ? 2 : 1;
                        query("UPDATE `" . $engine->server->prefix . "building` SET `start`=?,`timestamp`=?,`queue`=? WHERE `id`=?", array(time(), $time, $queue, $b['id']));
                        $this->emitEvent($p['uid'], array(
                            "name" => "flashNotification",
                            "data" => "33",
                        ));
                        $this->emitCache($p['uid'], $engine->building->getQueue($b['wid']));
                        $this->emitCache($p['uid'], $engine->building->getBuilding(array("wid" => $b['wid'], "location" => $b['location'])));
                        $this->emitCache($p['uid'], $engine->building->getBuildings($b['wid']));
                        $this->procRes($b['wid']);
                        $this->emitCache($p['uid'], $engine->village->get($b['wid']));
                    }
                }
            } else {
                $sort1 = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `sort`=?;", [$b['wid'], 1])->rowCount();
                if ($sort1 == 0) {
                    query("UPDATE `" . $engine->server->prefix . "building` SET `sort`=`sort`-1 WHERE `wid`=?;", [$b['wid']]);
                }
            }
        }
    }

    public function procRes($wid = null) {
        global $engine;

        $sql = "SELECT * FROM `" . $engine->server->prefix . "village`";
        $params = array();
        if ($wid !== null) {
            $sql .= " WHERE `wid`=?";
            $params[] = (int)$wid;
        }
        $data = query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        $now = microtime(true);

        foreach ($data as $v) {
            $id = (int)$v['wid'];
            $last = (float)$v['lastupdate'];
            if ($last <= 0 || $last > $now) { $last = $now; }
            $elapsed = max(0, $now - $last);

            // Calculate production once and persist it so the UI and the
            // construction system always see the same current values.
            $pw = max(0, (float)$engine->village->getProc($id, 1));
            $pc = max(0, (float)$engine->village->getProc($id, 2));
            $pi = max(0, (float)$engine->village->getProc($id, 3));
            $pg = (float)$engine->village->getProc($id, 4);

            // A legacy village can contain level-0 resource fields. Repair the
            // production floor here as well, so old installations recover.
            $fields = query(
                "SELECT `type`,`level` FROM `" . $engine->server->prefix . "field` WHERE `wid`=? AND `location` BETWEEN 1 AND 18",
                array($id)
            )->fetchAll(PDO::FETCH_ASSOC);
            $fallback = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
            foreach ($fields as $f) {
                $t = (int)$f['type'];
                $l = max(1, (int)$f['level']);
                if ($t >= 1 && $t <= 4) {
                    $bd = BuildingData::get($t, $l);
                    if ($bd) { $fallback[$t] += max(0, (float)$bd['effect']) * (float)$engine->server->speed_world; }
                }
            }
            if ($pw <= 0) { $pw = $fallback[1]; }
            if ($pc <= 0) { $pc = $fallback[2]; }
            if ($pi <= 0) { $pi = $fallback[3]; }
            if ($pg == 0 && $fallback[4] > 0) {
                $pg = $fallback[4] - ((float)$v['pop'] + (float)$engine->unit->getVillageSupply($id));
            }

            $wood = min((float)$v['maxstore'], (float)$v['wood'] + ($pw / 3600) * $elapsed);
            $clay = min((float)$v['maxstore'], (float)$v['clay'] + ($pc / 3600) * $elapsed);
            $iron = min((float)$v['maxstore'], (float)$v['iron'] + ($pi / 3600) * $elapsed);
            $crop = min((float)$v['maxcrop'], (float)$v['crop'] + ($pg / 3600) * $elapsed);

            // Never leave resource fields/producers at zero after the first
            // request on an old world.
            query(
                "UPDATE `" . $engine->server->prefix . "village` SET `wood`=?,`clay`=?,`iron`=?,`crop`=?,`pwood`=?,`pclay`=?,`piron`=?,`pcrop`=?,`lastupdate`=? WHERE `wid`=?",
                array($wood, $clay, $iron, $crop, $pw, $pc, $pi, $pg, $now, $id)
            );
        }
    }

    private function researchComplete() {
        global $engine;
        $t = query("SELECT * FROM `" . $engine->server->prefix . "tqueue` WHERE `end`<=?", array(time()))->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($t); $i++) {
            $tl = $engine->tech->getTech($t[$i]['wid'], $t[$i]['type']);
            if ($tl == -2) {
                $nl = 0;
            } else {
                $nl = $tl + 1;
            }
            query("UPDATE `" . $engine->server->prefix . "tdata` SET `t" . ($t[$i]['type'] % 10) . "`=? WHERE `wid`=?", array($nl, $t[$i]['wid']));
            query("DELETE FROM `" . $engine->server->prefix . "tqueue` WHERE `id`=?;", array($t[$i]['id']));
            $v = query("SELECT * FROM `" . $engine->server->prefix . "village` WHERE `wid`=?", array($t[$i]['wid']))->fetch(PDO::FETCH_ASSOC);
            $p = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);
            $this->emitCache($p['uid'], $engine->tech->getResearch($t[$i]['wid']));
            $this->emitCache($p['uid'], $engine->tech->getResearchQueue($t[$i]['wid']));
            $engine->auto->emitEvent($p['uid'], array(
                "name" => "flashNotification",
                "data" => "42",
            ));
        }
    }

    public function trainComplete($wid = null) {
        global $engine;

        if ($wid === null) {
            $t = query("SELECT * FROM `" . $engine->server->prefix . "train` WHERE `next`<=?", array(time()))->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $t = query("SELECT * FROM `" . $engine->server->prefix . "train` WHERE `next`<=? AND `wid`=?", array(time(), $wid))->fetchAll(PDO::FETCH_ASSOC);
        }
        for ($i = 0; $i < count($t); $i++) {
            $unit = 0;
            $all_unit = 0;
            $last_unit = 0;
            $time = 0;
            $tsub = query("SELECT * FROM `" . $engine->server->prefix . "train_queue` WHERE `tid`=?", array($t[$i]['id']))->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tsub as $value) {
                $all_unit += $value['amount'];
                $last_unit = 0;
                if ($time < 1) {
                    for ($l = 0; $l <= $value['amount']; $l++) {
                        if ($time < 1) {
                            $unit += 1;
                            $last_unit += 1;
                            $time += $value['duration'];
                        } else {
                            if ($value['amount'] - $last_unit == 0) {
                                query("DELETE FROM `" . $engine->server->prefix . "train_queue` WHERE `id`=?", array($value['id']));
                            } else {
                                query("UPDATE `" . $engine->server->prefix . "train_queue` SET `amount`=`amount`-? WHERE `id`=?", array($last_unit, $value['id']));
                            }
                            $l = $value['amount'];
                        }
                    }
                }
            }
            $p = $engine->account->getByVillage($t[$i]['wid']);
            $engine->unit->addUnit($t[$i]['wid'], ($t[$i]['type'] - (($p['tribe'] - 1) * 10)), $unit);
            if ($all_unit == 0) {
                query("DELETE FROM `" . $engine->server->prefix . "train` WHERE `id`=?", array($t[$i]['id']));
            } else {
                query("UPDATE `" . $engine->server->prefix . "train` SET `next`=?,`start`=? WHERE `id`=?", array(time() + $time, time(), $t[$i]['id']));
            }
            $this->emitCache($p['uid'], $engine->unit->getTraining($t[$i]['wid']));
            $this->emitCache($p['uid'], $engine->unit->getStay($t[$i]['wid']));
        }
    }

    private function freeSilver() {
        global $engine;
        if ($engine->auto->last['freeSilver'] + 60 < time()) {
            $engine->auto->last['freeSilver'] = time();
            $ps = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `online`=?", [1])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($ps as $p) {
                query("UPDATE `" . $engine->server->prefix . "user` SET `silver`=`silver`+50 WHERE `uid`=?", array($p['uid']));
                $this->emitCache($p['uid'], $engine->account->getAjax($p['uid']));
            }
        }
    }

    private function movement() {
        global $engine;

        $m = query("SELECT * FROM `" . $engine->server->prefix . "troop_move` WHERE `end`<=?", [time()])->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($m); $i++) {
            if ($m[$i]['type'] == "3") {
                $engine->move->attack($m[$i]);
            } elseif ($m[$i]['type'] == "4") {
                $engine->move->raid($m[$i]);
            } elseif ($m[$i]['type'] == "5") {
                $engine->move->reinforcement($m[$i]);
            } elseif ($m[$i]['type'] == "7") {
                $engine->move->sendResource($m[$i]);
            } elseif ($m[$i]['type'] == "9") {
                $engine->move->reinforcement($m[$i]);
            } elseif ($m[$i]['type'] == "10") {
                $engine->move->settle($m[$i]);
            } elseif ($m[$i]['type'] == "20") {
                $engine->move->adventure($m[$i]);
            } elseif ($m[$i]['type'] == "27") {
                $engine->move->reinforcement($m[$i]);
            } elseif ($m[$i]['type'] == "33") {
                $engine->move->reinforcement($m[$i]);
            }
        }
    }

    public function procTreasuryTransformations() {
        global $engine;

        $vs = query("SELECT * FROM `{$engine->server->prefix}village` WHERE `area`<=? AND (`area`<>? AND `area`<>?)", [time(), 0, -1])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($vs as $v) {
            $l = $engine->building->getLocation($v['wid'], 45)[0];

            query("UPDATE `{$engine->server->prefix}field` SET `type`=? WHERE `location`=? AND `wid`=?;", [27, $l, $v['wid']]);
            query("UPDATE `{$engine->server->prefix}village` SET `area`=? WHERE `wid`=?;", [-1, $v['wid']]);
            $engine->world->calInf($v['wid']);

            $this->emitCache($v['owner'], $engine->building->getBuilding(["wid" => $v['owner'], "location" => $l]));
            $this->emitCache($v['owner'], $engine->building->getBuildings($v['wid']));
            $this->emitCache($v['owner'], $engine->village->get($v['wid']));
            $this->emitEvent([
                "name" => "invalidateCache",
                "data" => "MapDetails:{$v['wid']}",
            ]);
            $this->emitEvent([
                "name" => "mapChanged",
                "data" => $v['wid'],
            ]);
        }
    }

    public function procAdventurePoint() {
        global $engine;

        $hs = query("SELECT * FROM `{$engine->server->prefix}hero` WHERE `advNext`<=?", [time()])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($hs as $h) {

            $next = time() + (min($h['advPoint'] * 600, 86400)) / $engine->server->speed_world;

            query("UPDATE `{$engine->server->prefix}hero` SET `advPoint`=`advPoint`+1,`advNext`=? WHERE `id`=?;", [$next, $h['id']]);
            $this->emitCache($h['owner'], $engine->hero->get($h['owner']));
        }
    }

    public function healthHero() {
        global $engine;

        $hs = query("SELECT * FROM `{$engine->server->prefix}hero` WHERE `dead`=?;", [0])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($hs as $h) {
            $hp = $h['health'];
            $hp = $hp + (($h['regen'] * $engine->server->speed_world / 86400) * (microtime(true) - $h['lastupdate']));
            ($hp > 100) ? $hp = 100 : '';
            query("UPDATE `{$engine->server->prefix}hero` SET `health`=?,`lastupdate`=? WHERE `id`=?;", [$hp, microtime(true), $h['id']]);

            /* if ($engine->server->speed_world >= 100)
              $timer = 10;
              else
              $timer = 120;
              if ($this->last['hpHero'] + $timer < time()) {
              $this->last['hpHero'] = time();
              $this->emitCache($h['owner'], $engine->hero->get($h['owner']));
              } */
        }
    }

    public function reviveHero() {
        global $engine;

        $hs = query("SELECT * FROM `{$engine->server->prefix}hero` WHERE `revive`<=? AND `revive`<>?", [time(), 0])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($hs as $h) {
            $engine->unit->addUnit($h['village'], 11, 1, $h['village']);
            $engine->auto->emitCache($h['owner'], $engine->unit->getStay($h['village']));
            query("UPDATE `{$engine->server->prefix}hero` SET `revive`=?,`health`=?,`dead`=? WHERE `id`=?;", [0, 100, 0, $h['id']]);
            $this->emitCache($h['owner'], $engine->hero->get($h['owner']));
            $this->emitCache($h['owner'], $engine->quest->get('',$h['owner']));
            $this->emitCache($h['owner'], $engine->quest->giver($h['owner']));
        }
    }

    public function tick() {
        // Request-driven server worker for installations without Node/cron.
        // The individual methods only process records whose timestamps are due.
        $this->buildComplete();
        $this->procRes();
        $this->researchComplete();
        $this->trainComplete();
        $this->movement();
        $this->procTreasuryTransformations();
        $this->procAdventurePoint();
        $this->healthHero();
        $this->reviveHero();
        $this->freeSilver();
    }

    public function work() {
        global $engine;

        $this->buildComplete();
        $this->procRes();
        $this->researchComplete();
        $this->trainComplete();
        $this->movement();
        $this->procTreasuryTransformations();
        $this->procAdventurePoint();
        $this->healthHero();
        $this->reviveHero();

        $this->freeSilver();

        if ($engine->auto->last['pong'] + 120 < time()) {
            $engine->auto->last['pong'] = time();

            $user_online = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `online`=?", [1])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($user_online as $u) {
                $this->emitEvent($u['uid'], array(
                    "name" => "pong",
                    "data" => 1,
                ));
            }
        }
    }

}
