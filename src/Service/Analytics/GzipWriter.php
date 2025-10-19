<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\GzipWriterInterface;

final class GzipWriter implements GzipWriterInterface { public function write(string $path, array $rows): string { $tmp=tempnam(sys_get_temp_dir(),'ana-')?:($path.'.tmp'); $h=fopen($tmp,'wb'); foreach($rows as $r){ fwrite($h,json_encode($r,JSON_UNESCAPED_UNICODE)."\n"); } fclose($h); $gz=$path.'.gz'; $in=fopen($tmp,'rb'); $out=gzopen($gz,'wb9'); while(!feof($in)){ gzwrite($out, fread($in,8192)); } fclose($in); gzclose($out); @unlink($tmp); return $gz; }}