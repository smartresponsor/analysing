<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\CsvImportInterface;

final class CsvImporter implements CsvImportInterface { public function read(string $csvPath, string $delimiter=','): array { $rows=[]; if(!is_file($csvPath)) return $rows; if(($h=fopen($csvPath,'r'))===false) return $rows; $headers=fgetcsv($h,0,$delimiter)?:[]; while(($data=fgetcsv($h,0,$delimiter))!==false){ $row=[]; foreach($headers as $i=>$name){ $row[$name]=$data[$i]??''; } $rows[]=$row; } fclose($h); return $rows; }}