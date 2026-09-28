<?php
include_once __DIR__ . '/config.php';

@session_start();

if (!empty($_SESSION['lobby_uid'])) {
    header('Location: ' . $lobby_url, true, 302);
    exit;
}

header('Location: ' . $mellon_url . 'authentication/login/', true, 302);
exit;
