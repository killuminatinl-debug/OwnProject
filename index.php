<?php
if (!is_file(__DIR__.'/config.php')) {
    header('Location: install.php', true, 302);
    exit;
}

require_once __DIR__.'/mellon/engine/session.php';

if (isset($engine->session->data) && !empty($engine->session->data->islogin)) {
    header('Location: /game/s1/', true, 302);
    exit;
}

header('Location: /mellon/authentication/login/', true, 302);
exit;
