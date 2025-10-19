<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\NotifierInterface;

final class FileNotifier implements NotifierInterface { public function __construct(private string $logPath = '') {} public function send(string $endpoint, array $payload): bool { $path=$this->logPath ?: (sys_get_temp_dir().'/analytics_notify.log'); $line=date('c').'|'.$endpoint.'|'.json_encode($payload,JSON_UNESCAPED_UNICODE)."\n"; return (bool)file_put_contents($path,$line,FILE_APPEND); }}