<?php

include_once __DIR__ . '/../engine/session.php';

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
if (!is_array($data)) $data = [];
header('Content-Type: application/json');

$json = [];
$controller = isset($data['controller']) ? (string)$data['controller'] : '';
$actionName = isset($data['action']) ? (string)$data['action'] : '';
$params = isset($data['params']) && is_array($data['params']) ? $data['params'] : [];

if ($controller === 'cache' && $actionName === 'get') {
    $json['serialNo'] = 1092;
    $json['cache'] = [];
    $json['response'] = [];
    $names = isset($params['names']) && is_array($params['names']) ? $params['names'] : [];

    foreach ($names as $name) {
        $action = explode(':', (string)$name);
        switch ($action[0]) {
            case 'Collection':
                if (($action[1] ?? '') === 'Gold') {
                    $json['cache'][] = [
                        'name' => implode(':', $action),
                        'data' => ['cache' => [], 'operation' => 1]
                    ];
                }
                break;
            case 'Session':
                $json['cache'][] = $engine->session->get();
                break;
            case 'GameWorld':
                if (!empty($action[1])) {
                    $json['cache'][] = [
                        'name' => 'GameWorld:' . $action[1],
                        'data' => $engine->server->getInfo($action[1])
                    ];
                }
                break;
            case 'Player':
                $json['cache'][] = $engine->account->get();
                break;
            case 'Feed':
                $json['cache'][] = [
                    'name' => implode(':', $action),
                    'data' => ['entries' => []]
                ];
                break;
        }
    }
} elseif ($controller === 'player') {
    if ($actionName === 'getPrestigeStars') {
        $json['serialNo'] = 1032;
        $json['response'] = ['level'=>0,'stars'=>['bronze'=>0,'silver'=>0,'gold'=>0]];
    } elseif ($actionName === 'getAllPrestigeData') {
        $json['serialNo'] = 1042;
        $json['response'] = [
            'activeGameWorldsPrestige'=>null,
            'currentLevelPrestigePoints'=>0,
            'finishedGameWorldsPrestige'=>null,
            'globalPrestige'=>(int)($engine->session->data->prestige ?? 0),
            'level'=>(int)($engine->session->data->level ?? 0),
            'nextLevelPrestigePoints'=>25
        ];
    } elseif ($actionName === 'getLastPlayedGameWorld') {
        $json['serialNo'] = 1039;
        $servers = $engine->server->listServer(true);
        $last = !empty($servers) ? $servers[0] : null;
        $json['response'] = $last ? ['id'=>$last['consumersId'],'name'=>$last['worldName']] : null;
    } elseif ($actionName === 'getOtherRegions') {
        $json['serialNo'] = 1040;
        $json['response'] = [];
    } elseif ($actionName === 'ping') {
        $json['serialNo'] = 1153;
        $json['response'] = [];
    } elseif ($actionName === 'getCountries') {
        $json['serialNo'] = 1041;
        $json['response'] = [
            'asia'=>['tr','th'],
            'europe'=>['dk','no','se','fi','fr','nl','de','it','hu','en','gb','us','ru','cz','pl'],
            'middle_east'=>['ae']
        ];
    } elseif ($actionName === 'getAccountDetails') {
        $json['serialNo'] = 1154;
        $json['response'] = [
            'accountType'=>'Account',
            'duals'=>[],'sitters'=>[],
            'email'=>(string)($_SESSION['lobby_email'] ?? $_SESSION['mellon_email'] ?? ''),
            'facebookId'=>null,'googleId'=>null,'vkontakteId'=>null,
            'id'=>(int)($_SESSION['lobby_uid'] ?? 0),
            'isActivated'=>true,'isInstant'=>false,'newEmail'=>null,
            'customerGroup'=>['key'=>3,'name'=>'Workers'],
            'dwhData'=>['customerGroup'=>['key'=>3,'name'=>'Workers']],
            'newsletter'=>[[
                'newsletterId'=>4,'newsletterName'=>'Travian Games',
                'newsletterTerms'=>'','subscribed'=>false
            ]]
        ];
    } elseif ($actionName === 'getAvatarData') {
        $json['serialNo'] = 1043;
        $json['response'] = [['id'=>(string)($_SESSION['lobby_uid'] ?? 0)]];
    } elseif ($actionName === 'getAll') {
        $json['serialNo'] = 1033;
        $json['response'] = [];
        $json['event'] = ['name'=>'clearCache','data'=>[]];
        $json['cache'] = [
            $engine->account->get(),
            $engine->session->get(),
            $engine->prestige->get(),
            $engine->achv->get(),
            $engine->noti->get(),
            $engine->avatar->getImage()
        ];
        $json['cache'] = array_merge($json['cache'], $engine->avatar->getAll());
    } elseif ($actionName === 'saveName') {
        $uid = (int)($_SESSION['lobby_uid'] ?? 0);
        $name = trim((string)($params['name'] ?? ''));
        if ($uid > 0 && $name !== '') {
            query('UPDATE global_user SET username=? WHERE uid=?', [$name, $uid]);
            $_SESSION['lobby_username'] = $name;
        }
        $json['response'] = [];
    } elseif (in_array($actionName, ['savePortrait','switchCountry','logoutAll','abortDeletion'], true)) {
        $json['response'] = [];
    } elseif ($actionName === 'deleteAvatar') {
        $json['response'] = ['data'=>false];
    }
} elseif (in_array($controller, ['sitter','dual','notification','gold'], true)) {
    $json['serialNo'] = 1201;
    $json['response'] = [];
} elseif ($controller === 'achievements') {
    if ($actionName === 'update') $json['response'] = [];
} elseif ($controller === 'gameworld') {
    if ($actionName === 'getPossibleNewGameworlds') {
        $json['response'] = [
            'cluster'=>['en','gb','us'],
            'other'=>[],
            'recommended'=>$engine->server->listServer(true)
        ];
        $json['serialNo'] = 1172;
    }
} elseif ($controller === 'login') {
    if ($actionName === 'logout') {
        $engine->account->Logout();
        $json['response'] = [];
    }
}

$json['time'] = time();
echo json_encode($json, JSON_UNESCAPED_SLASHES);
exit;
