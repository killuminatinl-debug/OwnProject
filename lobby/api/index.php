<?php

include_once __DIR__ . '/../engine/session.php';

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
if (!is_array($data)) {
    $data = [];
}
header('Content-Type: application/json');

$json = array();
$controller = isset($data['controller']) ? (string)$data['controller'] : '';
$actionName = isset($data['action']) ? (string)$data['action'] : '';
if (!isset($data['params']) || !is_array($data['params'])) { $data['params'] = []; }

if ($controller == "cache") {
    if ($data['action'] == "get") {
        $json['serialNo'] = 1092;
        $json['cache'] = [];
        $json['response'] = [];

        for ($i = 0; $i < count($data['params']['names']); $i++) {
            $action = explode(":", $data['params']['names'][$i]);
            switch ($action[0]) {
                case "Collection": {
                        if ($action[1] == "Gold") {
                            array_push($json['cache'], [
                                "name" => join(":", $action),
                                "data" => [
                                    "cache" => [],
                                    "operation" => 1
                                ],
                            ]);
                        }
                    }
                    break;
                case "Session": {
                        array_push($json['cache'], $engine->session->get());
                    }
                    break;
                case "GameWorld": {
                        array_push($json['cache'], [
                            'name' => 'GameWorld:' . $action[1],
                            'data' => $engine->server->getInfo($action[1])
                        ]);
                    }
                    break;
                case "Player": {
                        array_push($json['cache'], $engine->account->get());
                    }
                    break;
                case "Feed": {
                        array_push($json['cache'], [
                            "name" => "Feed:" . $action[1] . ":" . $action[2],
                            "data" => [
                                "entries" => [
                                ],
                            ],
                        ]);
                    }
                    break;
            }
        }
    }
} elseif ($controller == "player") {
    if ($data['action'] == "getPrestigeStars") {
        $json['serialNo'] = 1032;
        $json['response'] = array(
            "level" => 0,
            "stars" => array(
                "bronze" => 0,
                "silver" => 0,
                "gold" => 0
            )
        );
    } elseif ($data['action'] == "getAllPrestigeData") {
        $json['serialNo'] = 1042;
        $json['response'] = array(
            "activeGameWorldsPrestige" => null,
            "currentLevelPrestigePoints" => 0,
            "finishedGameWorldsPrestige" => null,
            "globalPrestige" => 0,
            "level" => 0,
            "nextLevelPrestigePoints" => 25
        );
    } elseif ($data['action'] == "getLastPlayedGameWorld") {
        $json['serialNo'] = 1039;
        $servers = $engine->server->listServer(true);
        $last = !empty($servers) ? $servers[0] : null;
        $json['response'] = $last ? [
            "id" => $last['consumersId'],
            "name" => $last['worldName']
        ] : null;
    } elseif ($data['action'] == "getOtherRegions") {
        $json['serialNo'] = 1040;
        $json['response'] = array();
    } elseif ($data['action'] == "ping") {
        $json['serialNo'] = 1153;
        $json['response'] = array();
    } elseif ($data['action'] == "getCountries") {
        $json['serialNo'] = 1041;
        $json['response'] = array(
            "asia" => array("tr", "th"),
            "europe" => array("dk", "no", "se", "fi", "fr", "nl", "de", "it", "hu", "en", "gb", "us", "ru", "cz", "pl"),
            "middle_east" => array("ae")
        );
    } elseif ($data['action'] == "getAccountDetails") {
        $json['serialNo'] = 1154;
        $json['response'] = [
            "accountType" => "Account",
            "duals" => [],
            "sitters" => [],
            "email" => (string)($_SESSION['mellon_email'] ?? ''),
            "facebookId" => null,
            "googleId" => null,
            "vkontakteId" => null,
            "id" => (int)($_SESSION['lobby_uid'] ?? 0),
            "isActivated" => true,
            "isInstant" => false,
            "newEmail" => null,
            "customerGroup" => [
                "key" => 3,
                "name" => "Workers"
            ],
            "dwhData" => [
                "customerGroup" => [
                    "key" => 3,
                    "name" => "Workers"
                ]
            ],
            "newsletter" => [
                [
                    "newsletterId" => 4,
                    "newsletterName" => "Travian Games",
                    "newsletterTerms" => "",
                    "subscribed" => false,
                ]
            ]
        ];
    } elseif ($data['action'] == "getAvatarData") {
        $json['serialNo'] = 1043;
        $json['response'] = [
            ["id" => (string)($_SESSION['lobby_uid'] ?? 0)]
        ];
    } elseif ($data['action'] == "getAll") {
        $json['serialNo'] = 1033;
        $json['response'] = [];
        $json['event'] = [
            "name" => "clearCache",
            "data" => [],
        ];
        $json['cache'] = [
            $engine->account->get(),
            $engine->session->get(),
            $engine->prestige->get(),
            $engine->achv->get(),
            $engine->noti->get(),
            $engine->avatar->getImage(),
        ];
        $json['cache'] = array_merge($json['cache'], $engine->avatar->getAll());
    }
    } elseif ($actionName === "getPrestigeOnWorlds") {
        $json['serialNo'] = 1200;
        $json['response'] = [
            "activeGameWorlds" => [],
            "finishedGameWorlds" => [],
            "activeGameWorldsPrestige" => 0,
            "finishedGameWorldsPrestige" => 0
        ];
    } elseif ($actionName === "saveName") {
        $uid = (int)($_SESSION['lobby_uid'] ?? 0);
        $name = trim((string)($data['params']['name'] ?? ''));
        if ($uid > 0 && $name !== '') {
            query("UPDATE global_user SET username=? WHERE uid=?", [$name, $uid]);
            $_SESSION['lobby_username'] = $name;
        }
        $json['response'] = [];
    } elseif (in_array($actionName, ["savePortrait","switchCountry","logoutAll","abortDeletion"], true)) {
        $json['response'] = [];
    } elseif ($actionName === "deleteAvatar") {
        $json['response'] = ["data" => false];
} elseif ($controller == "sitter" || $controller == "dual" || $controller == "notification" || $controller == "gold") {
    $json['serialNo'] = 1201;
    $json['response'] = [];
} elseif ($controller == "achievements") {
    if ($data['action'] == "update") {
        $json['response'] = [];
    }
} elseif ($controller == "gameworld") {
    if ($data['action'] == "getPossibleNewGameworlds") {
        $json['response'] = [
            "cluster" => ["en", "gb", "us"],
            "other" => [],
            "recommended" => $engine->server->listServer(true)
        ];
        $json['serialNo'] = 1172;
    }
} elseif ($controller == "login") {
    if ($data['action'] == "logout") {
        $engine->account->Logout();
        $json['response'] = [];
        echo json_encode($json);
        exit();
    }
}

$json['time'] = time();
echo json_encode($json);
exit();
