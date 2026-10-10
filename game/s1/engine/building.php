<?php

/*
 * Develop by Phumin Chanthalert from Thailand
 * Facebook : http://fb.com/phoomin2012
 * Tel. : 091-8585234 (Thai mobile)
 * Copy Rigth © Phumin Chanthalert.
 */

class Building {

    private $html = "";
    public $data = null;
    public $buildArray = null;

    public function getQueue($id = null) {
        global $engine;
        if ($id == null) {
            $id = $engine->village->select;
        }
        $v = query("SELECT * FROM `{$engine->server->prefix}village` WHERE `wid`=?", array($id))->fetch(PDO::FETCH_ASSOC);
        $p = query("SELECT * FROM `{$engine->server->prefix}user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);
        $b1 = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=? ORDER BY `sort` ASC;", array($id, 1))->fetchAll(PDO::FETCH_ASSOC);
        $b2 = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=? ORDER BY `sort` ASC;", array($id, 2))->fetchAll(PDO::FETCH_ASSOC);
        $b4 = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=? ORDER BY `paid` DESC,`sort` ASC;", array($id, 4))->fetchAll(PDO::FETCH_ASSOC);
        $b5 = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=? ORDER BY `sort` ASC;", array($id, 5))->fetchAll(PDO::FETCH_ASSOC);
        $b1n = [];
        $b2n = [];
        $b4n = [];
        $b5n = [];
        foreach ($b1 as $key => $value) {
            $b1n[count($b1n)] = $this->makeQueue($value);
        }
        foreach ($b2 as $key => $value) {
            $b2n[count($b2n)] = $this->makeQueue($value);
        }
        foreach ($b4 as $key => $value) {
            $b4n[count($b4n)] = $this->makeQueue($value);
        }
        foreach ($b5 as $key => $value) {
            $b5n[count($b5n)] = $this->makeQueue($value);
        }
        $slotfree = [1, 1];
        if ($p['tribe'] == 1) {
            $slotfree[0] = 1 - count($b1n);
            $slotfree[1] = 1 - count($b2n);
        } else {
            $slotfree[0] = 1 - count($b1n) - count($b2n);
            $slotfree[1] = 1 - count($b1n) - count($b2n);
        }
        $r = array(
            'name' => 'BuildingQueue:' . $id,
            'data' =>
            array(
                'villageId' => $id,
                'tribeId' => $p['tribe'],
                'freeSlots' => array(
                    1 => $slotfree[0],
                    2 => $slotfree[1],
                    4 => 1 + $p['master'] - count($b4n),
                ),
                'queues' => array(
                    1 => $b1n,
                    2 => $b2n,
                    4 => $b4n,
                    5 => $b5n,
                ),
                'canUseInstantConstruction' => false,
                'canUseInstantConstructionOnlyInVillage' => false,
            ),
        );
        return $r;
    }

    public function getSingleQueue($id) {
        global $engine;

        return query("SELECT * FROM `{$engine->server->prefix}building` WHERE `id`=?;", array($id))->fetch(PDO::FETCH_ASSOC);
    }

    private function makeQueue($data) {
        global $engine;

        $f = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=?", [$data['wid']])->fetchAll(PDO::FETCH_ASSOC);
        $inq = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=? AND `type`=?;", [$data['wid'], $data['location'], $data['type']])->rowCount();

        return array(
            "id" => $data['id'],
            "villageId" => $data['wid'],
            "locationId" => $data['location'],
            "buildingType" => $data['type'],
            "isRubble" => 0,
            "paid" => $data['paid'],
            "queueType" => $data['queue'],
            "timeStart" => $data['start'],
            "finished" => $data['timestamp'],
            "waiting" => false
        );
    }

    public function inQueue($wid, $type, $location = null) {
        global $engine;
        if ($location === null) {
            return query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `type`=?;", array($wid, $type))->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=? AND `type`=?;", array($wid, $location, $type))->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getBuildings($id) {
        global $engine;
        $v = query("SELECT * FROM `{$engine->server->prefix}village` WHERE `wid`=?", array($id))->fetch(PDO::FETCH_ASSOC);
        $p = query("SELECT * FROM `{$engine->server->prefix}user` WHERE `uid`=?", array($v['owner']))->fetch(PDO::FETCH_ASSOC);
        $b = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=?", array($id))->fetchAll(PDO::FETCH_ASSOC);
        $ba = [];
        for ($i = 0; $i < count($b); $i++) {
            $ba[count($ba)] = $this->makeDetail($b[$i]['id'], $id, $b[$i]['location'], $b[$i]['type'], $b[$i]['level']);
        }
        $r = array(
            'name' => 'Collection:Building:' . $id,
            'data' => array(
                'operation' => 1,
                'cache' => $ba
            )
        );
        return $r;
    }

    public function getBuilding($option) {
        global $engine;
        if(isset($option['id'])) $b=query("SELECT * FROM `{$engine->server->prefix}field` WHERE `id`=? LIMIT 1",array((int)$option['id']))->fetch(PDO::FETCH_ASSOC);
        else $b=query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",array((int)$option['wid'],(int)$option['location']))->fetch(PDO::FETCH_ASSOC);
        if(!$b) return $this->makeDetail(0,(int)($option['wid']??0),(int)($option['location']??0),0,0);
        return $this->makeDetail($b['id'],$b['wid'],$b['location'],$b['type'],$b['level']);
    }

    public function makeDetail($id, $wid, $location, $type, $level, $option = [], $status = 0) {
        global $engine;
        $id=(int)$id; $wid=(int)$wid; $location=(int)$location; $type=(int)$type; $level=(int)$level;
        $option=is_array($option)?$option:array();

        if ($type <= 0) {
            return array('name'=>'Building:'.$id,'data'=>array(
                'buildingType'=>0,'villageId'=>$wid,'locationId'=>$location,'lvl'=>0,'lvlNext'=>1,
                'isMaxLvl'=>false,'lvlMax'=>0,'upgradeCosts'=>array(1=>0,2=>0,3=>0,4=>0),
                'upgradeTime'=>0,'nextUpgradeCosts'=>array(),'nextUpgradeTimes'=>array(),
                'upgradeSupplyUsage'=>0,'upgradeSupplyUsageSums'=>array(),'category'=>1,
                'sortOrder'=>$id,'effect'=>array()
            ));
        }

        $field=query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",array($wid,$location))->fetch(PDO::FETCH_ASSOC);
        if(!$field) return array('name'=>'Building:'.$id,'data'=>array(
            'buildingType'=>$type,'villageId'=>$wid,'locationId'=>$location,'lvl'=>$level,'lvlNext'=>$level+1,
            'isMaxLvl'=>false,'lvlMax'=>0,'upgradeCosts'=>array(1=>0,2=>0,3=>0,4=>0),'upgradeTime'=>0,
            'nextUpgradeCosts'=>array(),'nextUpgradeTimes'=>array(),'upgradeSupplyUsage'=>0,
            'upgradeSupplyUsageSums'=>array(),'category'=>1,'sortOrder'=>$id,'effect'=>array()
        ));

        $queue=(int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=?",array($wid,$location))->fetchColumn();
        $max=$this->getMax($wid,$type);
        if(!$max || $max<1) $max=20;
        $displayLevel=($status==1)?0:max(0,$level);
        $upgrade=BuildingData::get($type,$displayLevel+1);
        $current=BuildingData::get($type,$displayLevel);
        if(!$upgrade) return array('name'=>'Building:'.$id,'data'=>array(
            'buildingType'=>$type,'villageId'=>$wid,'locationId'=>$location,'lvl'=>$displayLevel,'lvlNext'=>$displayLevel,
            'isMaxLvl'=>true,'lvlMax'=>$max,'upgradeCosts'=>array(1=>0,2=>0,3=>0,4=>0),'upgradeTime'=>0,
            'nextUpgradeCosts'=>array(),'nextUpgradeTimes'=>array(),'upgradeSupplyUsage'=>0,
            'upgradeSupplyUsageSums'=>array(),'category'=>1,'sortOrder'=>$id,'effect'=>array()
        ));

        $cat=in_array($type,array(1,2,3,4),true)?3:(in_array($type,array(16,19,22,33),true)?2:1);
        if($type===15) $cat=4;
        $speed=max(0.0001,(float)$engine->server->speed_world);
        $mainLevel=$this->getTypeLevel($wid,15);
        $mainData=BuildingData::get(15,$mainLevel);
        $mainEffect=($mainLevel>0 && $mainData && isset($mainData['effect']))?(float)$mainData['effect']:100;

        $result=array('name'=>'Building:'.$id,'data'=>array(
            'buildingType'=>$type,'villageId'=>$wid,'locationId'=>$location,'lvl'=>$displayLevel,
            'lvlNext'=>$displayLevel+1+$queue,'isMaxLvl'=>($displayLevel>=$max),'lvlMax'=>$max,
            'upgradeCosts'=>array(1=>(int)$upgrade['wood'],2=>(int)$upgrade['clay'],3=>(int)$upgrade['iron'],4=>(int)$upgrade['crop']),
            'upgradeTime'=>max(1,(int)round(((float)$upgrade['time']*($mainEffect/100))/$speed)),
            'nextUpgradeCosts'=>array(),'nextUpgradeTimes'=>array(),'upgradeSupplyUsage'=>0,
            'upgradeSupplyUsageSums'=>array(),'category'=>$cat,'sortOrder'=>$id,'effect'=>array()
        ));
        if((int)$field['rubble']===1 && $location>18) $result['data']['rubble']=array(
            1=>(int)$upgrade['wood'],2=>(int)$upgrade['clay'],3=>(int)$upgrade['iron'],4=>(int)$upgrade['crop']
        );

        $supply=0;
        for($n=1;$n<=$displayLevel;$n++){ $d=BuildingData::get($type,$n); if($d && isset($d['pop'])) $supply+=(float)$d['pop']; }
        $result['data']['upgradeSupplyUsage']=$supply;
        if($current && isset($current['effect']) && !in_array($type,array(13,16,18,22,24,25,26,40),true)) $result['data']['effect'][0]=$current['effect'];

        for($n=$displayLevel;$n<min($max,$displayLevel+7);$n++){
            $d=BuildingData::get($type,$n+1); if(!$d) continue;
            $key=($status==1)?($n-$displayLevel):$n;
            $result['data']['nextUpgradeCosts'][$key]=array(1=>(int)$d['wood'],2=>(int)$d['clay'],3=>(int)$d['iron'],4=>(int)$d['crop']);
            $result['data']['nextUpgradeTimes'][$key]=max(1,(int)round(((float)$d['time']*($mainEffect/100))/$speed));
            $sum=0;
            for($x=1;$x<=$n;$x++){ $dd=BuildingData::get($type,$x); if($dd && isset($dd['pop'])) $sum+=(float)$dd['pop']; }
            $result['data']['upgradeSupplyUsageSums'][$key]=$sum;
        }
        foreach($option as $key=>$value) $result['data'][$key]=$value;
        return ($status==1)?$result['data']:$result;
    }

    public function createBuilding($wid, $location, $type, $level = 1) {
        global $engine;
        query("INSERT INTO `{$engine->server->prefix}field` (`type`,`wid`,`location`,`level`) VALUES (?,?,?,?)", [$type, $wid, $location, $level]);
    }

    public function setBuilding($wid, $location, $type, $level = 1, $rubble = false) {
        global $engine;
        query("UPDATE `{$engine->server->prefix}field` SET `type`=?,`level`=?,`rubble`=? WHERE `wid`=? AND `location`=?;", [$type, $level, $rubble ? 1 : 0, $wid, $location]);
    }

    public function getAllBuildlist($vid = null) {
        global $engine;
        $return = [];

        $q = query("SELECT * FROM `{$engine->server->prefix}village` WHERE `owner`=?;", array($engine->session->data->uid));
        $data = $q->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < $q->rowCount(); $i += 1) {
            $b = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? ORDER BY `timestamp` ASC;", array($data[$i]['wid']));
            if ($b->rowCount() == 0) {
                $return[$i] = [];
            } else {
                $this->buildArray = $b->fetchAll(PDO::FETCH_ASSOC);
                $return[$i] = $this->buildArray;
            }
        }
        if ($vid == null) {
            return $return;
        } else {
            return $return;
        }
    }

    public function cancelBuild($id) {
        global $engine;
        $id = (int)$id;
        $b = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `id`=? LIMIT 1", [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$b || !isset($engine->session->data->uid)) {
            return false;
        }

        // Only the owner of this village may cancel its queue entry.
        $owner = $this->getOwnerForVillage((int)$b['wid']);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) {
            return false;
        }

        $f = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1", [$b['wid'], $b['location']])->fetch(PDO::FETCH_ASSOC);
        if (!$f) {
            return false;
        }

        if ((int)$b['paid'] === 1 && (int)$b['queue'] !== 5) {
            // Refund the exact cost paid when the queue entry was created.
            // Older queue rows may have no usable cost JSON, so retain a legacy fallback.
            $request = null;
            if (isset($b['cost']) && is_string($b['cost']) && $b['cost'] !== '') {
                $storedCost = json_decode($b['cost'], true);
                if (is_array($storedCost) && isset($storedCost['wood'], $storedCost['clay'], $storedCost['iron'], $storedCost['crop'])) {
                    $request = $storedCost;
                }
            }
            if ($request === null) {
                $request = BuildingData::get((int)$b['type'], (int)$f['level'] + 1);
            }
            if ($request) {
                if (((int)$f['level'] <= 1) && (int)$b['location'] > 18 && (int)$b['location'] != 41) {
                    query("UPDATE `{$engine->server->prefix}field` SET `type`=0 WHERE `wid`=? AND `location`=?", [$b['wid'], $b['location']]);
                }
                query("UPDATE `{$engine->server->prefix}village` SET `wood`=`wood`+?,`clay`=`clay`+?,`iron`=`iron`+?,`crop`=`crop`+? WHERE `wid`=?", [
                    $request['wood'], $request['clay'], $request['iron'], $request['crop'], $b['wid']
                ]);
            }
        }

        query("DELETE FROM `{$engine->server->prefix}building` WHERE `id`=?", [$id]);
        // Only master-builder entries use the explicit sort order. Do not shift
        // ordinary construction/demolition rows when cancelling them.
        if ((int)$b['queue'] === 4) {
            query("UPDATE `{$engine->server->prefix}building` SET `sort`=`sort`-1 WHERE `wid`=? AND `queue`=4 AND `sort`>?", [(int)$b['wid'], (int)$b['sort']]);
        }
        return true;
    }

    public function shiftMasterBuild($wid, $from, $to) {
        global $engine;
        $bq_from = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `sort`=?", [$wid, $from + 1])->fetch(PDO::FETCH_ASSOC);
        $bq_to = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `sort`=?", [$wid, $to + 1])->fetch(PDO::FETCH_ASSOC);

        query("UPDATE `{$engine->server->prefix}building` SET `sort`=? WHERE `id`=?", [$to + 1, $bq_from['id']]);
        query("UPDATE `{$engine->server->prefix}building` SET `sort`=? WHERE `id`=?", [$from + 1, $bq_to['id']]);
    }

    public function MasterBuild($location, $type = 0, $wid = 0) {
        global $engine;

        $location = (int)$location;
        $type = (int)$type;
        $wid = (int)$wid;
        if ($wid <= 0) {
            $wid = (int)$engine->village->select;
        }
        if ($location < 1 || $location > 40 || $wid <= 0 ||
            !isset($engine->session->data->uid)) {
            return false;
        }

        // Never allow a client to enqueue work in another player's village.
        $owner = $this->getOwnerForVillage($wid);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) {
            return false;
        }

        $field = query(
            "SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",
            array($wid, $location)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$field) {
            return false;
        }

        if ($location === 32) {
            $type = 16;
        } elseif ($location === 33) {
            $type = 30 + (int)$owner['tribe'];
        } elseif ($type <= 0) {
            $type = (int)$field['type'];
        }
        if ($type <= 0) {
            return false;
        }

        // Keep special building slots tied to their correct location.
        if ($type === 16) {
            $location = 32;
        } elseif ($type === 30 + (int)$owner['tribe']) {
            $location = 33;
        }
        $field = query(
            "SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",
            array($wid, $location)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$field) {
            return false;
        }

        // A Master Builder task may follow other Master Builder levels, but it
        // must not overlap a regular build or demolition on the same location.
        $conflict = (int)query(
            "SELECT COUNT(*) FROM `{$engine->server->prefix}building`
             WHERE `wid`=? AND `location`=? AND `queue` IN (1,2,5)",
            array($wid, $location)
        )->fetchColumn();
        if ($conflict > 0 || (int)$field['rubble'] === 1) {
            return false;
        }

        $queuedLevels = (int)query(
            "SELECT COUNT(*) FROM `{$engine->server->prefix}building`
             WHERE `wid`=? AND `location`=? AND `queue`=4",
            array($wid, $location)
        )->fetchColumn();
        $level = (int)$field['level'] + $queuedLevels + 1;
        $max = $this->getMax($wid, $type);
        if ($max && $level > $max) {
            return false;
        }

        $request = BuildingData::get($type, $level);
        if (!is_array($request) ||
            !isset($request['wood'], $request['clay'], $request['iron'], $request['crop'], $request['time'])) {
            return false;
        }

        $engine->auto->procRes($wid);
        $village = query(
            "SELECT * FROM `{$engine->server->prefix}village` WHERE `wid`=? LIMIT 1",
            array($wid)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$village) {
            return false;
        }

        $paid = ((float)$village['wood'] >= (float)$request['wood'] &&
                 (float)$village['clay'] >= (float)$request['clay'] &&
                 (float)$village['iron'] >= (float)$request['iron'] &&
                 (float)$village['crop'] >= (float)$request['crop']);

        $speed = max(0.0001, (float)$engine->server->speed_world);
        $effect = (float)$this->BuildingEffect(15, $this->getTypeLevel($wid, 15));
        if ($effect <= 0) {
            $effect = 100;
        }
        $request['time'] = max(1, (int)round(((float)$request['time'] * ($effect / 100)) / $speed));
        $duration = (int)$request['time'];

        $inqueuePaid = (int)query(
            "SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `paid`=1",
            array($wid)
        )->fetchColumn();

        if ($paid) {
            query(
                "UPDATE `{$engine->server->prefix}village`
                 SET `wood`=`wood`-?,`clay`=`clay`-?,`iron`=`iron`-?,`crop`=`crop`-?
                 WHERE `wid`=?",
                array($request['wood'], $request['clay'], $request['iron'], $request['crop'], $wid)
            );
            query(
                "UPDATE `{$engine->server->prefix}field` SET `type`=? WHERE `wid`=? AND `location`=?",
                array($type, $wid, $location)
            );
            query(
                "UPDATE `{$engine->server->prefix}building` SET `sort`=`sort`+1 WHERE `wid`=? AND `paid`=0",
                array($wid)
            );
            $result = query(
                "INSERT INTO `{$engine->server->prefix}building`
                 (`wid`,`location`,`sort`,`type`,`start`,`duration`,`timestamp`,`queue`,`paid`,`cost`,`level`)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                array($wid, $location, $inqueuePaid + 1, $type, time(), $duration, 0, 4, 1, json_encode($request), $level)
            );
        } else {
            query(
                "UPDATE `{$engine->server->prefix}field` SET `type`=? WHERE `wid`=? AND `location`=?",
                array($type, $wid, $location)
            );
            $sort = (int)query(
                "SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=?",
                array($wid)
            )->fetchColumn() + 1;
            $result = query(
                "INSERT INTO `{$engine->server->prefix}building`
                 (`wid`,`location`,`sort`,`type`,`start`,`duration`,`timestamp`,`queue`,`paid`,`cost`,`level`)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                array($wid, $location, $sort, $type, time(), $duration, 0, 4, 0, json_encode($request), $level)
            );
        }

        return $engine->sql->lastInsertId();
    }

    public function StartBuild($location, $type = 0, $wid = 0) {
        global $engine;
        $location=(int)$location; $type=(int)$type; $wid=(int)$wid;
        if($wid<=0) $wid=(int)$engine->village->select;
        if($location<1 || $wid<=0) return false;

        $owner=$this->getOwnerForVillage($wid);
        if(!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) return false;
        $field=query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",array($wid,$location))->fetch(PDO::FETCH_ASSOC);
        if(!$field) return false;

        if($location===32) $type=16;
        elseif($location===33) $type=30+(int)$owner['tribe'];
        elseif($type<=0) $type=(int)$field['type'];
        if($type<=0) return false;

        // Do not allow a second build/demolition to be queued on a location
        // that already has an active construction or demolition, including rubble.
        if((int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=? AND `queue` IN (1,2,5)",array($wid,$location))->fetchColumn()>0) return false;

        if((int)$field['rubble']===1 && !in_array($type,array(31,32,33),true)){
            $d=BuildingData::get($type,0); if(!$d) return false;
            $now=time(); $duration=max(1,(int)round(((float)$d['time'])/max(0.0001,(float)$engine->server->speed_world)));
            query("INSERT INTO `{$engine->server->prefix}building` (`wid`,`location`,`sort`,`type`,`start`,`duration`,`timestamp`,`queue`,`paid`,`cost`,`level`) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                array($wid,$location,0,$type,$now,$duration,$now+$duration,5,1,'[]',(int)$field['level']));
            return true;
        }

        $level=(int)$field['level']+1;
        $request=BuildingData::get($type,$level); if(!$request) return false;
        $max=$this->getMax($wid,$type); if($max && $level>$max) return false;
        if((int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=? AND `queue` IN (1,2,5)",array($wid,$location))->fetchColumn()>0) return false;

        $queueType=($location<=18)?2:1;
        $normal=(int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue` IN (1,2)",array($wid))->fetchColumn();
        $resource=(int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=2",array($wid))->fetchColumn();
        if((int)$owner['tribe']===1){
            if($queueType===1 && (int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `queue`=1",array($wid))->fetchColumn()>=1) return false;
            if($queueType===2 && $resource>=1) return false;
        } elseif($normal>=1) return false;

        $engine->auto->procRes($wid);
        $v=query("SELECT * FROM `{$engine->server->prefix}village` WHERE `wid`=? LIMIT 1",array($wid))->fetch(PDO::FETCH_ASSOC);
        if(!$v) return false;
        foreach(array('wood','clay','iron','crop') as $res) if((float)$v[$res] < (float)$request[$res]) return false;

        $speed=max(0.0001,(float)$engine->server->speed_world);
        $mainLevel=$this->getTypeLevel($wid,15); $main=BuildingData::get(15,$mainLevel);
        $effect=($mainLevel>0 && $main && isset($main['effect']))?(float)$main['effect']:100;
        $duration=max(1,(int)round(((float)$request['time']*($effect/100))/$speed)); $now=time();

        query("UPDATE `{$engine->server->prefix}village` SET `wood`=`wood`-?,`clay`=`clay`-?,`iron`=`iron`-?,`crop`=`crop`-? WHERE `wid`=?",
            array($request['wood'],$request['clay'],$request['iron'],$request['crop'],$wid));
        query("UPDATE `{$engine->server->prefix}field` SET `type`=? WHERE `wid`=? AND `location`=?",array($type,$wid,$location));
        query("INSERT INTO `{$engine->server->prefix}building` (`wid`,`location`,`sort`,`type`,`start`,`duration`,`timestamp`,`queue`,`paid`,`cost`,`level`) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            array($wid,$location,0,$type,$now,$duration,$now+$duration,$queueType,1,json_encode($request),$level));
        return true;
    }

    private function getOwnerForVillage($wid){
        global $engine;
        return query("SELECT u.* FROM `{$engine->server->prefix}user` u INNER JOIN `{$engine->server->prefix}village` v ON v.owner=u.uid WHERE v.wid=? LIMIT 1",array((int)$wid))->fetch(PDO::FETCH_ASSOC);
    }

    public function getTreasuryTransformations() {
        global $engine;

        $vs = query("SELECT * FROM `{$engine->server->prefix}village` WHERE `area`>? AND `owner`=?;", [0, $engine->session->data->uid])->fetchAll();
        $r = [];
        foreach ($vs as $v) {
            array_push($r, [
                "villageId" => $v['wid'],
                "finished" => $v['area']
            ]);
        }
        return $r;
    }

    public function BuildingEffect($type, $level) {
        global $engine;
        $effect = BuildingData::get($type, $level);
        if ($level == 0) {
            if ($type == 15) {
                $effect = array('effect' => 100);
            }
        }
        return $effect['effect'];
    }

    public function isMax($wid, $location, $type, $level = null) {
        global $engine;
        $q = query("SELECT * FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `location`=? AND `type`=?;", [$wid, $location, $type]);
        $loop = $q->rowCount();
        if ($level === null) {
            $field = $q->fetch(PDO::FETCH_ASSOC);
            $type = $field['type'];
            $level = $field['type'];
        }
        $dataarray = BuildingData::get($type);
        $village = $engine->village->get($wid, false);
        //var_dump([$type, $village['isMainVillage'], $level, $loop, (count($dataarray) - 1)]);exit();
        if ($type <= 4) {
            if ($village['isMainVillage']) {
                return ($level + $loop >= (count($dataarray) - 1 ));
            } else {
                return ($level + $loop >= (count($dataarray) - 11));
            }
        } else {
            return ($level + $loop >= count($dataarray) - 1);
        }
    }

    public function getMax($wid, $type) {
        global $engine;
        $dataarray = BuildingData::get($type);
        $village = $engine->village->get($wid, false);
        if ($type <= 4) {
            if ($village['isMainVillage'] == 1) {
                return (count($dataarray) - 1);
            } elseif ($village['isMainVillage'] == 0) {
                return (count($dataarray) - 11);
            }
        } else {
            return count($dataarray) - 1;
        }
    }

    public function getTypeLevel($wid, $type) {
        global $engine;
        $a = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `type`=?;", array($wid, $type))->fetchAll(PDO::FETCH_ASSOC);
        $c = 0;
        foreach ($a as $b) {
            $b['level'] > $c ? $c = $b['level'] : '';
        }
        return $c;
    }

    public function getLocation($wid, $type) {
        global $engine;
        $a = query("SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `type`=?;", array($wid, $type))->fetchAll(PDO::FETCH_ASSOC);
        $r = [];
        foreach ($a as $b) {
            array_push($r, $b['location']);
        }
        return $r;
    }

    public function destroy($params) {
        global $engine;

        $wid = isset($params['villageId']) ? (int)$params['villageId'] : 0;
        $location = isset($params['locationId']) ? (int)$params['locationId'] : 0;
        if ($wid <= 0 || $location < 1 || $location > 40 ||
            !isset($engine->session->data->uid)) {
            return false;
        }

        $owner = $this->getOwnerForVillage($wid);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) {
            return false;
        }

        $field = query(
            "SELECT * FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=? LIMIT 1",
            array($wid, $location)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$field || (int)$field['level'] < 1 || (int)$field['type'] < 1 ||
            (int)$field['rubble'] === 1) {
            return false;
        }

        $pending = (int)query(
            "SELECT COUNT(*) FROM `{$engine->server->prefix}building`
             WHERE `wid`=? AND `location`=? AND `queue` IN (1,2,4,5)",
            array($wid, $location)
        )->fetchColumn();
        if ($pending > 0) {
            return false;
        }

        $type = (int)$field['type'];
        $level = (int)$field['level'];
        $buildingData = BuildingData::get($type, $level);
        if (!is_array($buildingData) || !isset($buildingData['time'])) {
            return false;
        }

        $start = time();
        $duration = max(1, (int)round(((float)$buildingData['time'] / 2) /
            max(0.0001, (float)$engine->server->speed_world)));
        $timestamp = $start + $duration;

        query(
            "INSERT INTO `{$engine->server->prefix}building`
             (`wid`,`location`,`sort`,`type`,`start`,`duration`,`timestamp`,`queue`,`paid`,`cost`,`level`)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            array($wid, $location, 0, $type, $start, $duration, $timestamp, 5, 0, '[]', $level)
        );
        return true;
    }

    public function reserveResources($bid) {
        global $engine;

        $bid = (int)$bid;
        if ($bid <= 0 || !isset($engine->session->data->uid)) {
            return false;
        }

        $bq = query(
            "SELECT * FROM `{$engine->server->prefix}building` WHERE `id`=? LIMIT 1",
            array($bid)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$bq || (int)$bq['queue'] !== 4) {
            return false;
        }

        $owner = $this->getOwnerForVillage((int)$bq['wid']);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) {
            return false;
        }
        // A paid entry is already reserved; treating it as success avoids a
        // false API failure when clients send reserveResources redundantly.
        if ((int)$bq['paid'] === 1) {
            return true;
        }

        $request = BuildingData::get((int)$bq['type'], (int)$bq['level']);
        if (!is_array($request) ||
            !isset($request['wood'], $request['clay'], $request['iron'], $request['crop'])) {
            return false;
        }

        $engine->auto->procRes((int)$bq['wid']);
        $v = $engine->village->getById((int)$bq['wid']);
        if (!$v || $v['wood'] < $request['wood'] || $v['clay'] < $request['clay'] ||
            $v['iron'] < $request['iron'] || $v['crop'] < $request['crop']) {
            return false;
        }

        query(
            "UPDATE `{$engine->server->prefix}village`
             SET `wood`=`wood`-?,`clay`=`clay`-?,`iron`=`iron`-?,`crop`=`crop`-?
             WHERE `wid`=?",
            array($request['wood'], $request['clay'], $request['iron'], $request['crop'], $bq['wid'])
        );
        query(
            "UPDATE `{$engine->server->prefix}building`
             SET `sort`=`sort`+1 WHERE `wid`=? AND `sort`<? AND `paid`=0",
            array($bq['wid'], $bq['sort'])
        );
        $inqueuePaid = (int)query(
            "SELECT COUNT(*) FROM `{$engine->server->prefix}building` WHERE `wid`=? AND `paid`=1",
            array($bq['wid'])
        )->fetchColumn();
        query(
            "UPDATE `{$engine->server->prefix}building` SET `sort`=?,`paid`=1, `cost`=? WHERE `id`=?",
            array($inqueuePaid + 1, json_encode($request), $bid)
        );
        return true;
    }

    public function getBuildable($wid, $id) {
        global $engine;
        $buildable = [];
        $notBuildable = [];

        $artEffGrt = 0;
        $village = $engine->village->get($wid, false);

        $twall = 32;
        if ($engine->session->data->tribe <= 3) {
            $twall = $engine->session->data->tribe + 30;
        }

        $woodcutter = $this->getTypeLevel($wid, 1);
        $claypit = $this->getTypeLevel($wid, 2);
        $ironmine = $this->getTypeLevel($wid, 3);
        $cropland = $this->getTypeLevel($wid, 4);
        $sawmill = $this->getTypeLevel($wid, 5);
        $brickyard = $this->getTypeLevel($wid, 6);
        $ironfoundry = $this->getTypeLevel($wid, 7);
        $grainmill = $this->getTypeLevel($wid, 8);
        $bakery = $this->getTypeLevel($wid, 9);
        $warehouse = $this->getTypeLevel($wid, 10);
        $granary = $this->getTypeLevel($wid, 11);
        $blacksmith = $this->getTypeLevel($wid, 13);
        $tournamentsquare = $this->getTypeLevel($wid, 14);
        $mainbuilding = $this->getTypeLevel($wid, 15);
        $rallypoint = $this->getTypeLevel($wid, 16);
        $market = $this->getTypeLevel($wid, 17);
        $embassy = $this->getTypeLevel($wid, 18);
        $barrack = $this->getTypeLevel($wid, 19);
        $stable = $this->getTypeLevel($wid, 20);
        $workshop = $this->getTypeLevel($wid, 21);
        $academy = $this->getTypeLevel($wid, 22);
        $cranny = $this->getTypeLevel($wid, 23);
        $townhall = $this->getTypeLevel($wid, 24);
        $residence = $this->getTypeLevel($wid, 25);
        $palace = $this->getTypeLevel($wid, 26);
        $treasury = $this->getTypeLevel($wid, 27);
        $tradeoffice = $this->getTypeLevel($wid, 28);
        $greatbarracks = $this->getTypeLevel($wid, 29);
        $greatstable = $this->getTypeLevel($wid, 30);
        $wall = $this->getTypeLevel($wid, $twall); //31, 32, 33
        $stonemasonslodge = $this->getTypeLevel($wid, 34);
        $brewery = $this->getTypeLevel($wid, 35);
        $trapper = $this->getTypeLevel($wid, 36);
        $greatwarehouse = $this->getTypeLevel($wid, 38);
        $greatgranary = $this->getTypeLevel($wid, 39);
        $wonderworld = $this->getTypeLevel($wid, 40);
        $horsedrinkingtrough = $this->getTypeLevel($wid, 41);
        $waterdutch = $this->getTypeLevel($wid, 42);
        $naterwall = $this->getTypeLevel($wid, 43);
        $teahouse = $this->getTypeLevel($wid, 44);
        $hiddentreasury = $this->getTypeLevel($wid, 45);

        $hasPalaceAnywhere = 0; //$this->hasPalaceAnywhere();
        $hasWW = 0;

        if ($mainbuilding == 0 && !$this->inQueue($wid, 15) && $id != 32 && $id != 33) {

            $b = $this->makeDetail(0, $wid, $id, 15, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 1,
                        'valid' => ($mainbuilding >= 1)
                    )
                )), true, 1);
            if ($mainbuilding >= 1) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ((($cranny == 0 && !$this->inQueue($wid, 23)) || $cranny == 10) && $mainbuilding >= 1 && $id != 32 && $id != 33) {
            if ($cranny == 10) {
                $option = array(
                    'requiredBuildings' => array(
                        array(
                            'buildingType' => 23,
                            'currentLevel' => $cranny,
                            'requiredLevel' => 10,
                            'valid' => ($cranny >= 10)
                        )
                ));
            } else {
                $option = array('requiredBuildings' => []);
            }
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 23, 0, $option, true, 1);
        }
        if ((($granary == 0 && !$this->inQueue($wid, 11)) || $granary == 20) && $mainbuilding >= 1 && $id != 32 && $id != 33) {
            if ($granary == 20) {
                $option = array(
                    'requiredBuildings' => array(
                        array(
                            'buildingType' => 11,
                            'currentLevel' => $granary,
                            'requiredLevel' => 20,
                            'valid' => ($granary >= 20)
                        ),
                        array(
                            'buildingType' => 15,
                            'currentLevel' => $mainbuilding,
                            'requiredLevel' => 1,
                            'valid' => ($mainbuilding >= 1)
                        )
                ));
            } else {
                $option = array('requiredBuildings' => []);
            }
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 11, 0, $option, true, 1);
        }
        if ($wall == 0 && !$this->inQueue($wid, 32)) {
            if ($engine->session->data->tribe == 1 && $id != 32) {
                $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 31, 0, array('requiredBuildings' => []), true, 1);
            }
            if ($engine->session->data->tribe == 2 && $id != 32) {
                $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 32, 0, array('requiredBuildings' => []), true, 1);
            }
            if ($engine->session->data->tribe == 3 && $id != 32) {
                $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 33, 0, array('requiredBuildings' => []), true, 1);
            }
            if ($engine->session->data->tribe == 4 && $id != 32) {
                $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 33, 0, array('requiredBuildings' => []), true, 1);
            }
            if ($engine->session->data->tribe == 5 && $id != 32) {
                $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 33, 0, array('requiredBuildings' => []), true, 1);
            }
        }
        if ((($warehouse == 0 && !$this->inQueue($wid, 10)) || $warehouse == 20) && $id != 32 && $id != 33) {
            if ($warehouse == 20) {
                $option = array(
                    'requiredBuildings' => array(
                        array(
                            'buildingType' => 10,
                            'currentLevel' => $warehouse,
                            'requiredLevel' => 20,
                            'valid' => ($warehouse >= 20)
                        )
                ));
            } else {
                $option = array('requiredBuildings' => array(
                        array(
                            'buildingType' => 15,
                            'currentLevel' => $mainbuilding,
                            'requiredLevel' => 1,
                            'valid' => ($mainbuilding >= 1)
                        )
                ));
            }
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 10, 0, $option, true, 1);
        }
        if ($mainbuilding >= 10 && ($artEffGrt > 0 || $hasWW == 40) && (($warehouse == 20) || ($greatwarehouse == 20)) && $id != 32 && $id != 33) {

//include("avaliable/greatwarehouse.tpl");
        }
        if ($mainbuilding >= 10 && ($artEffGrt > 0 || $hasWW == 40) && (($granary == 20) || ($greatgranary == 20)) && $id != 32 && $id != 33) {
            //include("avaliable/greatgranary.tpl");
        }
        if (($trapper == 0 || $trapper == 20) && !$this->inQueue($wid, 36) && $rallypoint >= 1 && $engine->session->data->tribe == 3 && $id != 32 && $id != 33) {
            if ($trapper == 20) {
                $option = array(
                    'requiredBuildings' => array(
                        array(
                            'buildingType' => 36,
                            'currentLevel' => $trapper,
                            'requiredLevel' => 20,
                            'valid' => ($trapper >= 20)
                        ),
                        array(
                            'buildingType' => 16,
                            'currentLevel' => $rallypoint,
                            'requiredLevel' => 1,
                            'valid' => ($rallypoint >= 1)
                        )
                ));
            } else {
                $option = array('requiredBuildings' => array(
                        array(
                            'buildingType' => 16,
                            'currentLevel' => $rallypoint,
                            'requiredLevel' => 1,
                            'valid' => ($rallypoint >= 1)
                        )
                ));
            }
            $b = $this->makeDetail(0, $wid, $id, 36, 0, $option, true, 1);
            if (($trapper >= 20 && $rallypoint >= 1) || $trapper == 20) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($rallypoint == 0 && !$this->inQueue($wid, 16) && $id != 33) {
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 16, 0, ['requiredBuildings' => []], true);
        }
        if ($embassy == 0 && !$this->inQueue($wid, 18) && $id != 32 && $id != 33) {
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 18, 0, ['requiredBuildings' => []], true);
        }
        if (!$this->inQueue($wid, 19) && $barrack == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 19, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 3,
                        'valid' => ($mainbuilding >= 3)
                    ),
                )), true, 1);
            if ($mainbuilding >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if (!$this->inQueue($wid, 8) && $grainmill == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 8, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 4,
                        'currentLevel' => $cropland,
                        'requiredLevel' => 5,
                        'valid' => ($cropland >= 5)
                    ),
                )), true, 1);
            if ($cropland >= 5) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if (!$this->inQueue($wid, 17) && $market == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 17, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 3,
                        'valid' => ($mainbuilding >= 3)
                    ),
                    array(
                        'buildingType' => 10,
                        'currentLevel' => $warehouse,
                        'requiredLevel' => 1,
                        'valid' => ($warehouse >= 1)
                    ),
                    array(
                        'buildingType' => 11,
                        'currentLevel' => $granary,
                        'requiredLevel' => 1,
                        'valid' => ($granary >= 1)
                    )
                )), true, 1);
            if ($mainbuilding >= 3 && $warehouse >= 1 && $granary >= 1) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if (!$this->inQueue($wid, 25) && !$this->inQueue($wid, 26) && $residence == 0 && $id != 32 && $id != 33 && $palace == 0) {
            $b = $this->makeDetail(0, $wid, $id, 25, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($academy == 0 && !$this->inQueue($wid, 22) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 22, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 3,
                        'valid' => ($mainbuilding >= 3)
                    ),
                    array(
                        'buildingType' => 19,
                        'currentLevel' => $barrack,
                        'requiredLevel' => 3,
                        'valid' => ($barrack >= 3)
                    ),
                )), true, 1);
            if ($mainbuilding >= 3 && $barrack >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($hasWW != 40 && $palace == 0 && !$this->inQueue($wid, 26) && !$this->inQueue($wid, 25) && !$hasPalaceAnywhere && $id != 32 && $id != 33 && $residence == 0) {
            $b = $this->makeDetail(0, $wid, $id, 26, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 18,
                        'currentLevel' => $embassy,
                        'requiredLevel' => 5,
                        'valid' => ($embassy >= 1)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $embassy >= 1) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($blacksmith == 0 && !$this->inQueue($wid, 13) && $academy >= 1 && $mainbuilding >= 3 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 13, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 3,
                        'valid' => ($mainbuilding >= 3)
                    ),
                    array(
                        'buildingType' => 22,
                        'currentLevel' => $academy,
                        'requiredLevel' => 1,
                        'valid' => ($academy >= 1)
                    ),
                )), true, 1);
            if ($mainbuilding >= 3 && $academy >= 1) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($stonemasonslodge == 0 && !$this->inQueue($wid, 34) && $palace >= 3 && $mainbuilding >= 5 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 34, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 26,
                        'currentLevel' => $palace,
                        'requiredLevel' => 3,
                        'valid' => ($palace >= 3)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $palace >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($stable == 0 && !$this->inQueue($wid, 20) && $blacksmith >= 3 && $academy >= 5 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 20, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $blacksmith,
                        'requiredLevel' => 3,
                        'valid' => ($blacksmith >= 3)
                    ),
                    array(
                        'buildingType' => 22,
                        'currentLevel' => $academy,
                        'requiredLevel' => 5,
                        'valid' => ($academy >= 5)
                    ),
                )), true, 1);
            if ($academy >= 5 && $blacksmith >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($hasWW != 40 && $treasury == 0 && !$this->inQueue($wid, 27) && $mainbuilding >= 10 && $id != 32 && $id != 33) {
            $buildable[count($buildable)] = $this->makeDetail(0, $wid, $id, 25, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 10,
                        'valid' => ($mainbuilding >= 10)
                    ),
                )), true, 1);
        }
        if ($brickyard == 0 && !$this->inQueue($wid, 6) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 6, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 2,
                        'currentLevel' => $claypit,
                        'requiredLevel' => 10,
                        'valid' => ($claypit >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $claypit >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($sawmill == 0 && !$this->inQueue($wid, 5) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 5, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 1,
                        'currentLevel' => $woodcutter,
                        'requiredLevel' => 10,
                        'valid' => ($woodcutter >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $woodcutter >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($ironfoundry == 0 && !$this->inQueue($wid, 7) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 7, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 3,
                        'currentLevel' => $ironmine,
                        'requiredLevel' => 10,
                        'valid' => ($ironmine >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $ironmine >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($workshop == 0 && !$this->inQueue($wid, 21) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 21, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 22,
                        'currentLevel' => $academy,
                        'requiredLevel' => 10,
                        'valid' => ($academy >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $academy >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($tournamentsquare == 0 && !$this->inQueue($wid, 14) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 6, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 16,
                        'currentLevel' => $rallypoint,
                        'requiredLevel' => 15,
                        'valid' => ($rallypoint >= 15)
                    ),
                )), true, 1);
            if ($rallypoint >= 15) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($bakery == 0 && !$this->inQueue($wid, 9) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 9, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 8,
                        'currentLevel' => $grainmill,
                        'requiredLevel' => 5,
                        'valid' => ($grainmill >= 5)
                    ),
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 5,
                        'valid' => ($mainbuilding >= 5)
                    ),
                    array(
                        'buildingType' => 4,
                        'currentLevel' => $cropland,
                        'requiredLevel' => 10,
                        'valid' => ($cropland >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 5 && $grainmill >= 5 && $cropland >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($townhall == 0 && !$this->inQueue($wid, 24) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 24, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 10,
                        'valid' => ($mainbuilding >= 10)
                    ),
                    array(
                        'buildingType' => 22,
                        'currentLevel' => $academy,
                        'requiredLevel' => 10,
                        'valid' => ($academy >= 10)
                    ),
                )), true, 1);
            if ($mainbuilding >= 10 && $academy >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($tradeoffice == 0 && !$this->inQueue($wid, 28) && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 28, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $market,
                        'requiredLevel' => 20,
                        'valid' => ($market >= 20)
                    ),
                    array(
                        'buildingType' => 20,
                        'currentLevel' => $stable,
                        'requiredLevel' => 10,
                        'valid' => ($stable >= 10)
                    ),
                )), true, 1);
            if ($market >= 20 && $stable >= 10) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($engine->session->data->tribe == 1 && !$this->inQueue($wid, 41) && $horsedrinkingtrough == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 41, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 16,
                        'currentLevel' => $rallypoint,
                        'requiredLevel' => 10,
                        'valid' => ($rallypoint >= 10)
                    ),
                    array(
                        'buildingType' => 20,
                        'currentLevel' => $stable,
                        'requiredLevel' => 20,
                        'valid' => ($stable >= 20)
                    ),
                )), true, 1);
            if ($rallypoint >= 10 && $stable >= 20) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($engine->session->data->tribe == 2 && !$this->inQueue($wid, 35) && $brewery == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 28, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 11,
                        'currentLevel' => $rallypoint,
                        'requiredLevel' => 20,
                        'valid' => ($rallypoint >= 20)
                    ),
                    array(
                        'buildingType' => 16,
                        'currentLevel' => $granary,
                        'requiredLevel' => 20,
                        'valid' => ($granary >= 20)
                    ),
                )), true, 1);
            if ($rallypoint >= 10 && $granary >= 20) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if (!$this->inQueue($wid, 45) && $hiddentreasury == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 45, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 15,
                        'currentLevel' => $mainbuilding,
                        'requiredLevel' => 3,
                        'valid' => ($mainbuilding >= 3)
                    ),
                )), true, 1);
            if ($mainbuilding >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        $barrack = $this->getTypeLevel($wid, 19);
        $stable = $this->getTypeLevel($wid, 20);
        $greatbarracks = $this->getTypeLevel($wid, 29);
        $greatstable = $this->getTypeLevel($wid, 30);
        if ($greatbarracks == 0 && !$this->inQueue($wid, 29) && $barrack == 20 && $village['isMainVillage'] == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 29, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 19,
                        'currentLevel' => $barrack,
                        'requiredLevel' => 20,
                        'valid' => ($barrack >= 20)
                    ),
                )), true, 1);
            if ($barrack >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }
        if ($greatstable == 0 && !$this->inQueue($wid, 30) && $stable == 20 && $village['isMainVillage'] == 0 && $id != 32 && $id != 33) {
            $b = $this->makeDetail(0, $wid, $id, 30, 0, array(
                'requiredBuildings' => array(
                    array(
                        'buildingType' => 20,
                        'currentLevel' => $stable,
                        'requiredLevel' => 20,
                        'valid' => ($stable >= 20)
                    ),
                )), true, 1);
            if ($stable >= 3) {
                $buildable[count($buildable)] = $b;
            } else {
                $notBuildable[count($notBuildable)] = $b;
            }
        }

        return array(
            'buildings' => array(
                'buildable' => $buildable,
                'notBuildable' => $notBuildable,
            )
        );
    }

}

?>
