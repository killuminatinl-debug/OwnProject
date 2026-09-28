<?php
$ignoreLoad=true;
require_once dirname(__FILE__).'/../engine/engine.php';
header('Content-Type: application/json; charset=utf-8');
try{$engine->auto->work();echo json_encode(['ok'=>true,'time'=>time()]);}
catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'tick failed']);}
