<?php
if (!is_file(__DIR__.'/config.php')) { header('Location: install.php'); exit; }
require __DIR__.'/bootstrap.php';
if (me()) { header('Location: game.php'); exit; }
header('Location: install.php');
exit;
