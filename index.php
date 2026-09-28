<?php
include_once __DIR__ . '/config.php';

@session_start();

/* One-world installation: skip the lobby for normal players. */
if (!empty($_SESSION['mellon_msid'])) {
    $msid = (string)$_SESSION['mellon_msid'];
    header('Location: ' . $game_dir . 'api/login.php?token=' . rawurlencode(md5($msid)) . '&msid=' . rawurlencode($msid), true, 302);
    exit;
}

header('Location: ' . $mellon_url . 'authentication/login/', true, 302);
exit;
