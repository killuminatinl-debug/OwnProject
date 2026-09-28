<?php
$ignoreLoad=true;
require_once dirname(__FILE__).'/../engine/engine.php';
header('Content-Type: application/json; charset=utf-8');
$fp=@fopen(sys_get_temp_dir().'/ownproject_kingdoms_tick.lock','c+');
if(!$fp||!flock($fp,LOCK_EX|LOCK_NB)){echo json_encode(['ok'=>true,'busy'=>true,'time'=>time()]);exit;}
try{
 $meta=stream_get_contents($fp);
 $last=(int)trim($meta);
 if($last>0 && $last>=time()){$lastRun=$last;}else{$lastRun=0;}
 if($lastRun<time()){
  ftruncate($fp,0);rewind($fp);fwrite($fp,(string)time());fflush($fp);
  $engine->auto->work();
 }
 echo json_encode(['ok'=>true,'time'=>time()]);
}catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'tick failed']);}
flock($fp,LOCK_UN);fclose($fp);
