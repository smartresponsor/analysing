<?php declare(strict_types=1);

use SmartResponsor\Metrics\MetricRegistry;

use SmartResponsor\Trace\Tracer;

use SmartResponsor\Privacy\PrivacyGuard;

require __DIR__ . '/../src/Metrics.php';
require __DIR__ . '/../src/Tracer.php';
require __DIR__ . '/../src/PrivacyGuard.php';

$metrics = new MetricRegistry();
$tracer  = new Tracer(getenv('ZIPKIN_ENDPOINT') ?: null);
$privacy = new PrivacyGuard(getenv('PRIVACY_SALT') ?: 'salt');

$tp = $_SERVER['HTTP_TRACEPARENT'] ?? null;
$ctx = $tracer->fromTraceparent($tp);

header('Content-Type: application/json');
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ''; $expected = getenv('INGEST_API_KEY') ?: '';
if (!$expected || !hash_equals($expected, $apiKey)) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }

$body = file_get_contents('php://input');
$ev = json_decode($body, true); if (!is_array($ev) || !isset($ev['id']) || !isset($ev['occurredAt'])) { http_response_code(400); echo json_encode(['error'=>'bad_request']); exit; }

// privacy
if (!empty($ev['pii'])) {
  $ev['pii']['emailHash'] = $privacy->hash($ev['pii']['email'] ?? null);
  $ev['pii']['phoneHash'] = $privacy->hash($ev['pii']['phone'] ?? null);
  unset($ev['pii']['email'], $ev['pii']['phone']);
}

$pdo = new PDO(getenv('PG_DSN'), getenv('PG_USER'), getenv('PG_PASS'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$labels = ['route'=>'ingest','app'=>$ev['source']['app'] ?? 'app','env'=>$ev['source']['env'] ?? 'env'];
try {
  $res = $metrics->time(function() use ($pdo, $ev, $ctx){
    $sql = "INSERT INTO analytics_event (id,type,version,occurred_at,received_at,source_app,source_env,actor_id,actor_id_type,session_id,context_json,data_json,pii_json,trace_id,span_id)
            VALUES (:id,:type,:version,:occurred_at,now(),:source_app,:source_env,:actor_id,:actor_id_type,:session_id,:context_json::jsonb,:data_json::jsonb,:pii_json::jsonb,:trace_id,:span_id)
            ON CONFLICT (id) DO NOTHING";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':id'=>$ev['id'], ':type'=>$ev['type'] ?? 'unknown', ':version'=>$ev['version'] ?? 'v1', ':occurred_at'=>$ev['occurredAt'],
      ':source_app'=>$ev['source']['app'] ?? 'app', ':source_env'=>$ev['source']['env'] ?? 'prod',
      ':actor_id'=>$ev['actor']['id'] ?? null, ':actor_id_type'=>$ev['actor']['idType'] ?? 'anon', ':session_id'=>$ev['actor']['session'] ?? null,
      ':context_json'=>json_encode($ev['context'] ?? new stdClass()), ':data_json'=>json_encode($ev['data'] ?? new stdClass()), ':pii_json'=>json_encode($ev['pii'] ?? new stdClass()),
      ':trace_id'=>$ev['trace']['traceId'] ?? ($ctx['traceId'] ?? null), ':span_id'=>$ev['trace']['spanId'] ?? ($ctx['spanId'] ?? null),
    ]);
    return true;
  }, 'analytics_request_duration_ms', $labels);

  // enqueue job
  $day = substr($ev['occurredAt'], 0, 10);
  $q = $pdo->prepare("INSERT INTO analytics_queue(kind, payload, visible_at) VALUES('materialize.daily', :payload, now())");
  $q->execute([':payload'=>json_encode(['day'=>$day,'app'=>$ev['source']['app']??'app','env'=>$ev['source']['env']??'prod'])]);

  http_response_code(202);
  echo json_encode(['status'=>'accepted']);
  $metrics->counterInc('analytics_request_total', $labels, 1);
  $tracer->span('ingest', fn()=>true, $ctx);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'server_error']);
  $labels['status'] = '500';
  $metrics->counterInc('analytics_request_total', $labels, 1);
}
