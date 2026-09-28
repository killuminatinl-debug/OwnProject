<?php

/*
 * Develop by Phumin Chanthalert from Thailand
 * Facebook : http://fb.com/phoomin2012
 * Tel. : 091-8585234 (Thai mobile)
 * Copy Rigth © Phumin Chanthalert.
 */
@session_start();
define('SERVER_TAG', 'server1');
date_default_timezone_set('Asia/Bangkok');
include_once(dirname(__FILE__) . "/../../../config.php");
include_once(dirname(__FILE__) . "/building.php");
include_once(dirname(__FILE__) . "/village.php");
include_once(dirname(__FILE__) . "/account.php");
include_once(dirname(__FILE__) . "/session.php");
include_once(dirname(__FILE__) . "/database.php");
include_once(dirname(__FILE__) . "/unit.php");
include_once(dirname(__FILE__) . "/world.php");
include_once(dirname(__FILE__) . "/generater.php");
include_once(dirname(__FILE__) . "/setting.php");
include_once(dirname(__FILE__) . "/page.php");
include_once(dirname(__FILE__) . "/ranking.php");
include_once(dirname(__FILE__) . "/hero.php");
include_once(dirname(__FILE__) . "/item.php");
include_once(dirname(__FILE__) . "/notification.php");
include_once(dirname(__FILE__) . "/essentials.php");
include_once(dirname(__FILE__) . "/technology.php");
include_once(dirname(__FILE__) . "/movement.php");
include_once(dirname(__FILE__) . "/cache.php");
include_once(dirname(__FILE__) . "/report.php");
include_once(dirname(__FILE__) . "/market.php");
include_once(dirname(__FILE__) . "/battle.php");
include_once(dirname(__FILE__) . "/oasis.php");
include_once(dirname(__FILE__) . "/auto.php");
include_once(dirname(__FILE__) . "/quest.php");
include_once(dirname(__FILE__) . "/kingdom.php");
include_once(dirname(__FILE__) . "/celebrate.php");
include_once(dirname(__FILE__) . "/auction.php");

include_once(dirname(__FILE__) . "/data/buidata.php");
include_once(dirname(__FILE__) . "/data/hero_full.php");
include_once(dirname(__FILE__) . "/data/unitdata.php");
include_once(dirname(__FILE__) . "/data/resdata.php");
include_once(dirname(__FILE__) . "/data/cpdata.php");

$engine = (object) array(
            "sql" => new PDO("mysql:host=" . SQL_HOST . "; dbname=" . SQL_DATB . ";", SQL_USER, SQL_PASS),
            "error" => (object) array(
                "sql" => null
            ),
            "server" => null,
            "kingdom" => new Kingdom(),
            "cache" => new Cache(),
            "session" => new Session(),
            "database" => new Database(),
            "account" => new Account(),
            "world" => new World(),
            "unit" => new Unit(),
            "oasis" => new Oasis(),
            "battle" => new Battle(),
            "building" => new Building(),
            "ranking" => new Ranking(),
            "setting" => new Setting(),
            "item" => new Item(),
            "market" => new Market(),
            "report" => new Report(),
            "quest" => new Quest(),
            "celebrate" => new Celebrate(),
            "page" => (isset($_SERVER['REQUEST_URI'])) ? new Page() : null,
            "notification" => new Notification(),
            "tech" => new Technology(),
            "move" => new Movement(),
            "auction" => new Auction(),
            "data" => (object) array(
                "building" => new BuildingData(),
            ),
            "village" => new Village(),
            "hero" => new Hero(),
            "gen" => new Generater(),
            "auto" => new Auto(),
);
$engine->sql->exec("SET CHARACTER SET utf8");
$engine->sql->exec("SET character_set_results=utf8");
$engine->sql->exec("SET character_set_client=utf8");
$engine->sql->exec("SET character_set_connection=utf8");
$engine->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// The original Kingdoms SQL/game logic expects the legacy non-strict mode.
try { $engine->sql->exec("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'"); } catch (Exception $e) { /* keep server defaults */ }
$engine->server = (object) $engine->database->getServer();
define('TB_PREFIX', $engine->server->tag);

if (!isset($ignoreLoad) || $ignoreLoad !== true) {
    if (!$engine->session->checkLogin()) {
        header("Location: " . $mellon_url . "authentication/login/");
        exit;
    }
    // Single-world bootstrap: every logged-in account must have a valid tribe, kingdom and playable village.
    // This also repairs older/incomplete local accounts before the Kingdoms client requests its caches.
    $uid = (int)($engine->session->data->uid ?? 0);
    if ($uid > 0) {
        $player = query("SELECT * FROM `{$engine->server->prefix}user` WHERE `uid`=? LIMIT 1", [$uid])->fetch(PDO::FETCH_ASSOC);
        if ($player) {
            $tribe = (int)($player['tribe'] ?? 0);
            if ($tribe < 1 || $tribe > 7) {
                $tribe = 1;
                query("UPDATE `{$engine->server->prefix}user` SET `tribe`=? WHERE `uid`=?", [$tribe, $uid]);
                $player['tribe'] = $tribe;
            }
            $engine->session->data->tribe = $tribe;
            $_SESSION[$engine->server->prefix . 'tribe'] = $tribe;
            if ((int)($player['tutorial'] ?? 0) < 256) {
                query("UPDATE `{$engine->server->prefix}user` SET `tutorial`=256 WHERE `uid`=?", [$uid]);
                $player['tutorial'] = 256;
                $_SESSION[$engine->server->prefix . 'tutorial'] = 256;
            }
            $hasVillage = (int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}village` WHERE `owner`=?", [$uid])->fetchColumn();
            if ($hasVillage === 0) {
                $engine->village->createVillage($uid, $player['username'] ?? $_SESSION[$engine->server->prefix . 'username']);
            }
            // Repair incomplete villages produced by older installer revisions.
            $vrow = query("SELECT `wid` FROM `{$engine->server->prefix}village` WHERE `owner`=? ORDER BY `wid` ASC LIMIT 1", [$uid])->fetch(PDO::FETCH_ASSOC);
            if ($vrow) {
                $wid = (int)$vrow['wid'];
                $resourceTypes = [1,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2];
                for ($loc = 1; $loc <= 40; $loc++) {
                    $exists = (int)query("SELECT COUNT(*) FROM `{$engine->server->prefix}field` WHERE `wid`=? AND `location`=?", [$wid, $loc])->fetchColumn();
                    if ($exists === 0) {
                        $type = ($loc <= 18) ? (int)$resourceTypes[$loc - 1] : (($loc === 27) ? 15 : 0);
                        $level = ($loc <= 18 || $loc === 27) ? 1 : 0;
                        $engine->building->createBuilding($wid, $loc, $type, $level, 0);
                    }
                }
                query("UPDATE `{$engine->server->prefix}field` SET `level`=1,`rubble`=0 WHERE `wid`=? AND `location` BETWEEN 1 AND 18 AND `level`<1", [$wid]);
                query("UPDATE `{$engine->server->prefix}field` SET `type`=15,`level`=1,`rubble`=0 WHERE `wid`=? AND `location`=27 AND (`type`=0 OR `level`<1)", [$wid]);
                setcookie('village', (string)$wid, 0, '/');
            }
        }
    }
    $engine->auto->tick();
    $engine->village->LoadData();
}