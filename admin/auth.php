<?php
require_once dirname(__DIR__).'/config.php'; if(session_status()!==PHP_SESSION_ACTIVE)session_start(); if(empty($_SESSION['own_admin'])){header('Location: login.php');exit;}
