<?php declare(strict_types=1);
require __DIR__ . '/../src/Metrics.php';
require __DIR__ . '/../src/Tracer.php';

use SmartResponsor\Metrics\MetricRegistry;

use SmartResponsor\Trace\Tracer;

header('Content-Type: application/json');
$metrics = new MetricRegistry();
$tracer  = new Tracer(getenv('ZIPKIN_ENDPOINT') ?: null);
$pdo = new PDO(getenv('PG_DSN'), getenv('PG_USER'), getenv('PG_PASS'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$body = json_decode(file_get_contents('php://input') ?: "{}", true);
$app = $body['app'] ?? 'web'; $env = $body['env'] ?? 'dev'; $from = $body['from'] ?? date('Y-m-d'); $to = $body['to'] ?? $from;
$steps = $body['steps'] ?? ['product.view','product.add_to_cart'];
$labels = ['route'=>'aggregate_funnel','app'=>$app,'env'=>$env];

try{
  $data = $metrics->time(function() use ($pdo,$app,$env,$from,$to,$steps){
    $st = $pdo->prepare("SELECT day, user_count FROM aggregate_funnel_daily WHERE app=? AND env=? AND step_1=? AND step_2=? AND day BETWEEN ? AND ? ORDER BY day");
    $st->execute([$app,$env,$steps[0],$steps[1],$from,$to]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }, 'analytics_request_duration_ms', $labels);
  $tracer->span('aggregate.funnel', fn()=>true, []);
  $metrics->counterInc('analytics_request_total', $labels, 1);
  echo json_encode(['series'=>$data]);
}catch(\Throwable $e){
  $labels['status']='500';
  $metrics->counterInc('analytics_request_total', $labels, 1);
  http_response_code(500); echo json_encode(['error'=>'server_error']);
}
