<?php

if (empty($param)) {
    error_log('Snapshot hash required');
    response(400);
}

try {
    $img = $redis->get("shot_" . $param);
} catch (RedisException $e) {
    error_log("Redis error: " . $e->getMessage());
    response(500);
}

if ($img === false) {
    error_log("Snapshot with hash '$param' not found");
    response(404);
}

header('Content-Type: image/jpeg');
echo $img;
exit;
