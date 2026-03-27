<?php declare(strict_types=1);
require __DIR__ . '/../src/Metrics.php';
require __DIR__ . '/../src/Tracer.php';

use SmartResponsor\Metrics\MetricRegistry;

use SmartResponsor\Trace\Tracer;

$pdo = new PDO(getenv('PG_DSN'), getenv('PG_USER'), getenv('PG_PASS'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$metrics = new MetricRegistry();
$tracer  = new Tracer(getenv('ZIPKIN_ENDPOINT') ?: null);

const MAX_ATTEMPT = 5;

function dequeue(PDO $pdo): ?array {
  $pdo->beginTransaction();
  $s = $pdo->prepare("SELECT id, kind, payload, attempt FROM analytics_queue WHERE visible_at <= now() ORDER BY id FOR UPDATE SKIP LOCKED LIMIT 1");
  $s->execute();
  $j = $s->fetch(PDO::FETCH_ASSOC);
  if (!$j){ $pdo->commit(); return null; }
  $pdo->prepare("UPDATE analytics_queue SET reserved_at=now(), attempt=attempt+1 WHERE id=?")->execute([$j['id']]);
  $pdo->commit();
  $j['payload'] = json_decode($j['payload'], true);
  return $j;
}
function ack(PDO $pdo, int $id): void { $pdo->prepare("DELETE FROM analytics_queue WHERE id=?")->execute([$id]); }
function dlq(PDO $pdo, array $job, string $err): void {
  $pdo->prepare("INSERT INTO analytics_dlq(id,kind,payload,attempt,error,trace_id) VALUES(?,?,?,?,?,NULL) ON CONFLICT (id) DO NOTHING")->execute([$job['id'],$job['kind'],json_encode($job['payload']),$job['attempt'],$err]);
  ack($pdo, (int)$job['id']);
}
function matDaily(PDO $pdo, string $day, string $app, string $env): void {
  $from = $day." 00:00:00+00"; $to = $day." 23:59:59.999+00";
  $pdo->exec("CREATE TABLE IF NOT EXISTS aggregate_funnel_daily(day date, app text, env text, step_1 text, step_2 text, user_count bigint, PRIMARY KEY(day,app,env,step_1,step_2))");
  $pdo->exec("CREATE TABLE IF NOT EXISTS retention_cohort_daily(cohort date, app text, env text, day_offset int, active_user bigint, PRIMARY KEY(cohort,app,env,day_offset))");
  $pdo->exec("CREATE TABLE IF NOT EXISTS path_transition_daily(day date, app text, env text, from_event text, to_event text, transition_count bigint, PRIMARY KEY(day,app,env,from_event,to_event))");
  // funnel
  $q=$pdo->prepare("WITH s AS (SELECT actor_id,type FROM analytics_event WHERE occurred_at>=:f AND occurred_at<:t AND source_app=:a AND source_env=:e AND actor_id IS NOT NULL), u AS (SELECT actor_id, MAX(CASE WHEN type='product.view' THEN 1 ELSE 0 END) d1, MAX(CASE WHEN type='product.add_to_cart' THEN 1 ELSE 0 END) d2 FROM s GROUP BY actor_id) SELECT COUNT(*) FROM u WHERE d1=1 AND d2=1");
  $q->execute([':f'=>$from,':t'=>$to,':a'=>$app,':e'=>$env]); $c=(int)$q->fetchColumn();
  $pdo->prepare("INSERT INTO aggregate_funnel_daily(day,app,env,step_1,step_2,user_count) VALUES(:d,:a,:e,'product.view','product.add_to_cart',:c) ON CONFLICT (day,app,env,step_1,step_2) DO UPDATE SET user_count=EXCLUDED.user_count")->execute([':d'=>$day,':a'=>$app,':e'=>$env,':c'=>$c]);
  // retention D0
  $q=$pdo->prepare("SELECT COUNT(DISTINCT actor_id) FROM analytics_event WHERE occurred_at>=:f AND occurred_at<:t AND source_app=:a AND source_env=:e AND actor_id IS NOT NULL");
  $q->execute([':f'=>$from,':t'=>$to,':a'=>$app,':e'=>$env]); $active=(int)$q->fetchColumn();
  $pdo->prepare("INSERT INTO retention_cohort_daily(cohort,app,env,day_offset,active_user) VALUES(:d,:a,:e,0,:u) ON CONFLICT (cohort,app,env,day_offset) DO UPDATE SET active_user=EXCLUDED.active_user")->execute([':d'=>$day,':a'=>$app,':e'=>$env,':u'=>$active]);
  // path transition
  $q=$pdo->prepare("WITH ordered AS (SELECT actor_id,type,occurred_at, LEAD(type) OVER (PARTITION BY actor_id ORDER BY occurred_at) AS next_type FROM analytics_event WHERE occurred_at>=:f AND occurred_at<:t AND source_app=:a AND source_env=:e AND actor_id IS NOT NULL) SELECT COUNT(*) FROM ordered WHERE type='product.view' AND next_type='product.add_to_cart'");
  $q->execute([':f'=>$from,':t'=>$to,':a'=>$app,':e'=>$env]); $tc=(int)$q->fetchColumn();
  $pdo->prepare("INSERT INTO path_transition_daily(day,app,env,from_event,to_event,transition_count) VALUES(:d,:a,:e,'product.view','product.add_to_cart',:c) ON CONFLICT (day,app,env,from_event,to_event) DO UPDATE SET transition_count=EXCLUDED.transition_count")->execute([':d'=>$day,':a'=>$app,':e'=>$env,':c'=>$tc]);
}

while (true){
  $job = dequeue($pdo);
  if(!$job){ usleep(300000); continue; }
  $labels = ['worker'=>'materialize','kind'=>$job['kind']];
  $metrics->counterInc('analytics_job_attempt_total', $labels, 1);
  try{
    (new Tracer(getenv('ZIPKIN_ENDPOINT') ?: null))->span('worker.job', function() use ($pdo,$job){
      if ($job['kind']==='materialize.daily'){ $p=$job['payload']; matDaily($pdo, $p['day'], $p['app'], $p['env']); }
      return true;
    }, []);
    ack($pdo, (int)$job['id']);
    $metrics->counterInc('analytics_job_success_total', $labels, 1);
  }catch(\Throwable $e){
    if ((int)$job['attempt'] >= MAX_ATTEMPT){
      dlq($pdo, $job, $e->getMessage());
      $metrics->counterInc('analytics_job_dlq_total', $labels, 1);
    }else{
      $pdo->prepare("UPDATE analytics_queue SET visible_at=now()+interval '30 seconds' WHERE id=?")->execute([$job['id']]);
      $metrics->counterInc('analytics_job_retry_total', $labels, 1);
    }
  }
}
