<?php

if (empty($param)) {
    error_log('Snapshot hash required');
    response(400);
    exit;
}

try {
    $snapshot = $redis->get("shot_" . $param);
} catch (RedisException $e) {
    error_log("Redis error (hash: $param): " . $e->getMessage());
    response(500);
    exit;
}

if ($snapshot === false) {
    error_log("Snapshot not found (hash: $param)");
    response(404);
    exit;
}

header('Content-Type: image/jpeg');
echo $snapshot;
exit;
