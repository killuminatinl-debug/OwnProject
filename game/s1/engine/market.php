<?php

class Market {

    public function get($wid = null) {
        global $engine;

        $owner = $engine->account->getByVillage($wid);

        $r = [
            "villageId" => $wid,
            "speed" => ($owner['tribe'] == 1) ? 16 : ($owner['tribe'] == 2) ? 24 : 12,
            "max" => $engine->building->getTypeLevel($wid, 17),
            "inTransport" => 0,
            "inOffers" => 0,
            "carry" => ($owner['tribe'] == 1) ? 500 : ($owner['tribe'] == 2) ? 750 : 1000,
        ];
        return $r;
    }

    public function getOffer($wid, $option = ["own" => false, "search" => 0, "offer" => 0, "rate" => 0, "start" => 0, "count" => 8]) {
        global $engine;
        !isset($option['own']) ? $option['own'] = false : '';

        $r = [];
        if ($option['own'])
            $offers = query("SELECT * FROM `{$engine->server->prefix}market` WHERE `wid`=?", [$wid]);
        else
            $offers = query("SELECT * FROM `{$engine->server->prefix}market` WHERE `wid`<>?", [$wid]);
        $offer_count = $offers->rowCount();
        $offers = $offers->fetchAll(PDO::FETCH_ASSOC);

        foreach ($offers as $offer) {
            $owner = $engine->account->getByVillage($offer['wid']);
            $duration = $engine->world->getDuration(16, $wid, $offer['wid']);

            if ($option['own']) {
                array_push($r, [
                    "name" => "TradeOffer:{$offer['id']}",
                    "data" => [
                        "offerId" => $offer['id'],
                        "villageId" => $offer['wid'],
                        "playerId" => $owner['uid'],
                        "playerName" => $owner['username'],
                        "offeredAmount" => $offer['gamt'],
                        "offeredResource" => $offer['gtype'],
                        "searchedAmount" => $offer['wamt'],
                        "searchedResource" => $offer['wtype'],
                        "duration" => $duration,
                        "maximumDuration" => round($offer['maxtime'] / 3600),
                        "limitDuration" => $offer['maxtime'] == 0 ? false : true,
                        "blockedMerchants" => "1",
                        "kingdomId" => $offer['kingdom'],
                        "limitKingdom" => $offer['kingdom'] == 0 ? false : true,
                    ]
                ]);
            } else {
                array_push($r, [
                    "offerId" => $offer['id'],
                    "villageId" => $offer['wid'],
                    "playerId" => $owner['uid'],
                    "playerName" => $owner['username'],
                    "offeredAmount" => $offer['gamt'],
                    "offeredResource" => $offer['gtype'],
                    "searchedAmount" => $offer['wamt'],
                    "searchedResource" => $offer['wtype'],
                    "duration" => $duration,
                    "maximumDuration" => round($offer['maxtime'] / 3600),
                    "limitDuration" => $offer['maxtime'] == 0 ? false : true,
                    "blockedMerchants" => "1",
                    "kingdomId" => $offer['kingdom'],
                    "limitKingdom" => $offer['kingdom'] == 0 ? false : true,
                ]);
            }
        }

        if ($option['own']) {
            return $r;
        } else {
            return [
                "countEntries" => $offer_count,
                "data" => $r,
            ];
        }
    }

    public function createOffer($wid,$offerAmount=1,$offerType=1,$searchAmount=1,$searchType=2,$onlyKingdom=false,$maxtime=0) {
        global $engine;
        $wid=(int)$wid; $offerAmount=(int)$offerAmount; $searchAmount=(int)$searchAmount; $offerType=(int)$offerType; $searchType=(int)$searchType;
        $owner=$engine->account->getByVillage($wid);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid || $offerAmount<=0 || $searchAmount<=0 || !in_array($offerType,[1,2,3,4],true) || !in_array($searchType,[1,2,3,4],true)) return false;
        $engine->auto->procRes($wid);
        $v=query("SELECT * FROM `".$engine->server->prefix."village` WHERE `wid`=?",[$wid])->fetch(PDO::FETCH_ASSOC);
        if (!$v) return false;
        $col=[1=>'wood',2=>'clay',3=>'iron',4=>'crop'][$offerType];
        if ((float)$v[$col]<$offerAmount) return false;
        query("UPDATE `".$engine->server->prefix."village` SET `".$col."`=`".$col."`-? WHERE `wid`=?",[$offerAmount,$wid]);
        query("INSERT INTO `".$engine->server->prefix."market` (`wid`,`gtype`,`gamt`,`wtype`,`wamt`,`maxtime`,`kingdom`,`merchant`) VALUES (?,?,?,?,?,?,?,?)",[$wid,$offerType,$offerAmount,$searchType,$searchAmount,(int)$maxtime,$onlyKingdom?1:0,1]);
        return true;
    }

    public function cancelOffer($id) {
        global $engine;
        $offer = query("SELECT * FROM `{$engine->server->prefix}market` WHERE `id`=?", [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$offer) return false;
        $owner = $engine->account->getByVillage($offer['wid']);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) return false;
        $engine->auto->procRes($offer['wid']);
        if ($offer['gtype'] == 1)
            query("UPDATE `{$engine->server->prefix}village` SET `wood`=`wood`+? WHERE `wid`=?", [$offer['gamt'], $offer['wid']]);
        elseif ($offer['gtype'] == 2)
            query("UPDATE `{$engine->server->prefix}village` SET `clay`=`clay`+? WHERE `wid`=?", [$offer['gamt'], $offer['wid']]);
        elseif ($offer['gtype'] == 3)
            query("UPDATE `{$engine->server->prefix}village` SET `iron`=`iron`+? WHERE `wid`=?", [$offer['gamt'], $offer['wid']]);
        elseif ($offer['gtype'] == 4)
            query("UPDATE `{$engine->server->prefix}village` SET `crop`=`crop`+? WHERE `wid`=?", [$offer['gamt'], $offer['wid']]);
        query("DELETE FROM `{$engine->server->prefix}market` WHERE `id`=?", [$id]);
        return $offer['wid'];
    }

    public function checkTarget($from, $to) {
        global $engine;

        $target = $engine->account->getByVillage($to);
        $owner = $engine->account->getByVillage($from);
        $duration = $engine->world->getDuration(($owner['tribe'] == 1) ? 16 : ($owner['tribe'] == 2) ? 24 : 12, $from, $to);
        $village = $engine->village->get($to, false);
        return [
            "playerId" => $target['uid'],
            "villageId" => $village['villageId'],
            "villageName" => $village['name'],
            "mayCreateRoute" => true,
            "duration" => $duration
        ];
    }

    public function send($from,$to,$recurrences,$res) {
        global $engine;
        $from=(int)$from; $to=(int)$to;
        $owner=$engine->account->getByVillage($from);
        if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) return false;
        $amount=array(1=>max(0,(int)($res[1]??0)),2=>max(0,(int)($res[2]??0)),3=>max(0,(int)($res[3]??0)),4=>max(0,(int)($res[4]??0)));
        if (($amount[1]+$amount[2]+$amount[3]+$amount[4])<=0) return false;
        $engine->auto->procRes($from);
        $v=query("SELECT * FROM `".$engine->server->prefix."village` WHERE `wid`=?",[$from])->fetch(PDO::FETCH_ASSOC);
        if (!$v || (float)$v['wood']<$amount[1] || (float)$v['clay']<$amount[2] || (float)$v['iron']<$amount[3] || (float)$v['crop']<$amount[4]) return false;
        $tid=$engine->unit->createUnit($from,[1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0]);
        $speed=($owner['tribe']==1)?16:(($owner['tribe']==2)?24:12);
        $duration=$engine->world->getDuration($speed,$from,$to); $start=time(); $end=$start+$duration;
        $p=$owner['uid'];
        query("INSERT INTO `".$engine->server->prefix."troop_move` (`from`,`owner`,`to`,`type`,`spy`,`redeployHero`,`start`,`end`,`unit`,`merchant`,`data`) VALUES (?,?,?,?,?,?,?,?,?,?,?)",[$from,$p,$to,7,0,0,$start,$end,$tid,1,json_encode($amount)]);
        query("UPDATE `".$engine->server->prefix."village` SET `wood`=`wood`-?,`clay`=`clay`-?,`iron`=`iron`-?,`crop`=`crop`-? WHERE `wid`=?",[$amount[1],$amount[2],$amount[3],$amount[4],$from]);
        return $tid;
    }

}
