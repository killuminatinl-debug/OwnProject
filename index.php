<?php
include_once __DIR__ . '/config.php';
header('Location: ' . $mellon_url . 'authentication/login/', true, 302);
exit;
