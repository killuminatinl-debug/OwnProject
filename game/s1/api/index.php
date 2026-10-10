<?php
include_once dirname(__FILE__) . '/../engine/engine.php';

header('Content-Type: application/json; charset=utf-8');

// engine.php already runs the request-driven world tick during normal API
// requests. Do not run Auto::work() a second time here: duplicate processing
// can race building/training/movement completion when requests overlap.

$request_body=file_get_contents('php://input');
$data=json_decode($request_body,true);
if (!is_array($data)) $data=array();
if (!isset($data['params']) || !is_array($data['params'])) $data['params']=array();

$controller=isset($data['controller'])?(string)$data['controller']:'';
$action=isset($data['action'])?(string)$data['action']:'';

try {
    switch ($controller) {
        case 'login': include_once dirname(__FILE__).'/controller/login.php'; break;
        case 'cache': include_once dirname(__FILE__).'/controller/cache.php'; break;
        case 'troops': include_once dirname(__FILE__).'/controller/troops.php'; break;
        case 'village': include_once dirname(__FILE__).'/controller/village.php'; break;
        case 'map': include_once dirname(__FILE__).'/controller/map.php'; break;
        case 'player': include_once dirname(__FILE__).'/controller/player.php'; break;
        case 'quest': include_once dirname(__FILE__).'/controller/quest.php'; break;
        case 'building': include_once dirname(__FILE__).'/controller/building.php'; break;
        case 'premiumFeature': include_once dirname(__FILE__).'/controller/premiumFeature.php'; break;
        case 'hero': include_once dirname(__FILE__).'/controller/hero.php'; break;
        case 'ranking': include_once dirname(__FILE__).'/controller/ranking.php'; break;
        case 'payment': include_once dirname(__FILE__).'/controller/payment.php'; break;
        case 'reports': include_once dirname(__FILE__).'/controller/report.php'; break;
        case 'trade': include_once dirname(__FILE__).'/controller/trade.php'; break;
        case 'kingdom': include_once dirname(__FILE__).'/controller/kingdom.php'; break;
        case 'auctions': include_once dirname(__FILE__).'/controller/auction.php'; break;
        default:
            echo json_encode(array('serialNo'=>0,'response'=>array('error'=>'UNKNOWN_CONTROLLER'),'time'=>round(microtime(true)*1000)));
            break;
    }
} catch (Throwable $e) {
    error_log('[OwnProject] API '.$controller.'/'.$action.' failed: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
    // Keep the API response valid JSON even if session serial generation itself
    // fails while handling the original exception.
    $serialNo = 0;
    try {
        if (isset($engine) && isset($engine->session)) {
            $serialNo = (int)$engine->session->serialNo();
        }
    } catch (Throwable $serialError) {
        error_log('[OwnProject] Could not generate error-response serial: '.$serialError->getMessage());
    }
    echo json_encode(array(
        'serialNo'=>$serialNo,
        'response'=>array('error'=>'SERVER_ERROR'),
        'cache'=>array(),
        'time'=>round(microtime(true)*1000)
    ), JSON_INVALID_UTF8_SUBSTITUTE);
}
