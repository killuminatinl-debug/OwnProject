<?php
include_once dirname(__FILE__) . '/../engine/session.php';

$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
$msid = isset($_GET['msid']) ? (string)$_GET['msid'] : '';

if ($msid !== '' && $token !== '' && hash_equals(md5($msid), $token)) {
    $engine->account->Login($msid);
} else {
    header("Location: " . $index_url);
    exit;
}
