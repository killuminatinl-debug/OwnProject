<?php
include_once dirname(__FILE__) . '/../engine/engine.php';

// Web-mode catch-up: advance timestamp-based game state on normal API traffic.
// This keeps the original UI while removing the dependency on the old Node worker.
try {
    $engine->auto->work();
} catch (Throwable $e) {
    error_log('[OwnProject] auto catch-up failed: ' . $e->getMessage());
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
if (!is_array($data)) { $data = array(); }
header('Content-Type: application/json; charset=utf-8');

$controller = isset($data['controller']) ? (string)$data['controller'] : '';
$action = isset($data['action']) ? (string)$data['action'] : '';
if (!isset($data['params']) || !is_array($data['params'])) { $data['params'] = array(); }

switch ($controller) {
    case 'login': include_once dirname(__FILE__) . '/controller/login.php'; break;
    case 'cache': include_once dirname(__FILE__) . '/controller/cache.php'; break;
    case 'troops': include_once dirname(__FILE__) . '/controller/troops.php'; break;
    case 'village': include_once dirname(__FILE__) . '/controller/village.php'; break;
    case 'map': include_once dirname(__FILE__) . '/controller/map.php'; break;
    case 'player': include_once dirname(__FILE__) . '/controller/player.php'; break;
    case 'quest': include_once dirname(__FILE__) . '/controller/quest.php'; break;
    case 'building': include_once dirname(__FILE__) . '/controller/building.php'; break;
    case 'premiumFeature': include_once dirname(__FILE__) . '/controller/premiumFeature.php'; break;
    case 'hero': include_once dirname(__FILE__) . '/controller/hero.php'; break;
    case 'ranking': include_once dirname(__FILE__) . '/controller/ranking.php'; break;
    case 'payment': include_once dirname(__FILE__) . '/controller/payment.php'; break;
    case 'reports': include_once dirname(__FILE__) . '/controller/report.php'; break;
    case 'trade': include_once dirname(__FILE__) . '/controller/trade.php'; break;
    case 'kingdom': include_once dirname(__FILE__) . '/controller/kingdom.php'; break;
    case 'auctions': include_once dirname(__FILE__) . '/controller/auction.php'; break;
    default:
        echo json_encode(array('serialNo'=>0,'response'=>array(),'time'=>round(microtime(true)*1000)));
        break;
}
