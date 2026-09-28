<?php

if ($data['action'] == "startCoronationCeremony") {

    $owner = $engine->account->getByVillage($data['params']['villageId']);
    if (!$owner || (int)$owner['uid'] !== (int)$engine->session->data->uid) {
        echo json_encode(array("response"=>array("error"=>"NOT_OWNER"),"serialNo"=>$engine->session->serialNo(),"time"=>round(microtime(true)*1000)));
        exit;
    }
    $kid = $engine->kingdom->create($data['params']['tag']);

    echo json_encode(array(
        "response" => [],
        "cache" => [
            $engine->village->get($data['params']['villageId']),
            $engine->account->getAjax($_SESSION[$engine->server->prefix . 'uid']),
            $engine->kingdom->get($kid, true),
            $engine->kingdom->getStats($kid, true),
        ],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
} elseif ($data['action'] == "changeDescription") {

    $pub = isset($data['params']['publicDescription']);
    $desc = $pub ? $data['params']['publicDescription'] : $data['params']['internalDescription'];
    $engine->kingdom->changeDesc($data['params']['groupId'], $desc, $pub);

    echo json_encode(array(
        "response" => [],
        "cache" => [
            $engine->kingdom->get($data['params']['groupId'], true),
        ],
        "serialNo" => $engine->session->serialNo(),
        "time" => round(microtime(true) * 1000),
    ));
}