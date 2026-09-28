<?php
include_once dirname(__FILE__) . '/../engine/engine.php';
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
header('Content-Type: application/json');
ob_start();

$controller = isset($controller) ? (string)$controller : '';
$action = isset($action) ? (string)$action : '';
if (!isset($data['params']) || !is_array($data['params'])) { $data['params'] = []; }

if ($controller == "login") {
    include_once dirname(__FILE__) . '/controller/login.php';
} elseif ($controller == "cache") {
    include_once dirname(__FILE__) . '/controller/cache.php';
} elseif ($controller == "troops") {
    include_once dirname(__FILE__) . '/controller/troops.php';
} elseif ($controller == "village") {
    include_once dirname(__FILE__) . '/controller/village.php';
} elseif ($controller == "map") {
    include_once dirname(__FILE__) . '/controller/map.php';
} elseif ($controller == "player") {
    include_once dirname(__FILE__) . '/controller/player.php';
} elseif ($controller == "quest") {
    include_once dirname(__FILE__) . '/controller/quest.php';
} elseif ($controller == "building") {
    include_once dirname(__FILE__) . '/controller/building.php';
} elseif ($controller == "premiumFeature") {
    include_once dirname(__FILE__) . '/controller/premiumFeature.php';
} elseif ($controller == "hero") {
    include_once dirname(__FILE__) . '/controller/hero.php';
} elseif ($controller == "ranking") {
    include_once dirname(__FILE__) . '/controller/ranking.php';
} elseif ($controller == "payment") {
    include_once dirname(__FILE__) . '/controller/payment.php';
} elseif ($controller == "reports") {
    include_once dirname(__FILE__) . '/controller/report.php';
} elseif ($controller == "trade") {
    include_once dirname(__FILE__) . '/controller/trade.php';
} elseif ($controller == "kingdom") {
    include_once dirname(__FILE__) . '/controller/kingdom.php';
} elseif ($controller == "auctions") {
    include_once dirname(__FILE__) . '/controller/auction.php';
}