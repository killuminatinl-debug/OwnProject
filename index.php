<?php
if (!is_file(__DIR__.'/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__.'/bootstrap.php';
if (me()) { header('Location: game.php'); exit; }
header('Location: auth.php'); exit;
