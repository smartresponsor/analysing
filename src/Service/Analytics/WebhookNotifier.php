<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\NotifierInterface;

final class WebhookNotifier implements NotifierInterface { public function send(string $endpoint, array $payload): bool { $dir=sys_get_temp_dir().'/analytics_webhook'; if(!is_dir($dir)){ @mkdir($dir,0777, true);} $name=$dir.'/'.md5($endpoint.json_encode($payload)).'.json'; return (bool)file_put_contents($name, json_encode(['endpoint'=>$endpoint,'payload'=>$payload], JSON_UNESCAPED_UNICODE)); }}