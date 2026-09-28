<?php
ini_set('display_errors', '0');

$root = dirname(__DIR__, 4);
$placeholder = $root . '/cdn/lobby/live/images-ltr/bg_avatar.png';

if (is_file($placeholder)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($placeholder);
    exit;
}

http_response_code(404);
header('Content-Type: image/png');
exit;
