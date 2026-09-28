<?php
include_once __DIR__ . '/engine/session.php';
if (!$engine->session->data || empty($engine->session->data->uid)) {
    header('Location: ' . $lobby_url);
    exit;
}
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$world = $engine->server->getServerInfo($sid);
if (!$world || empty($world['folder'])) {
    $servers = $engine->server->listServer(true);
    $world = !empty($servers) ? $engine->server->getServerInfo($servers[0]['consumersId']) : false;
}
if (!$world || empty($world['folder'])) {
    http_response_code(503);
    echo 'No game world configured.';
    exit;
}
$msid = $engine->database->msid($_SESSION['lobby_email']);
header('Location: ' . rtrim($world['folder'],'/') . '/api/login.php?token=' . rawurlencode(md5($msid)) . '&msid=' . rawurlencode($msid) . '&msname=msid');
exit;
