<?php

if (empty($param)) {
    error_log('Snapshot hash required');
    response(400);
    exit;
}

try {
    $cameraDataRaw = $redis->get("live_" . $param);

    if ($cameraDataRaw === false) {
        error_log("Camera data not found (hash: $param)");
        response(404);
        exit;
    }

    $cameraData = json_decode($cameraDataRaw, true, 512, JSON_THROW_ON_ERROR);

    $model = $cameraData['model'] ?? null;
    $url = $cameraData['url'] ?? null;
    $credentials = $cameraData['credentials'] ?? null;

    if ($model === null || $url === null || $credentials === null) {
        error_log("Invalid camera data (hash: $param)");
        response(500);
        exit;
    }

    $camera = loadDevice('camera', $model, $url, $credentials);

    header('Content-Type: image/jpeg');
    echo $camera->getCamshot();
    exit;
} catch (RedisException $e) {
    error_log("Redis error (hash: $param): " . $e->getMessage());
    response(500);
    exit;
} catch (JsonException $e) {
    error_log("Error decoding JSON (hash: $param): " . $e->getMessage());
    response(500);
    exit;
} catch (Exception $e) {
    error_log("Error getting live snapshot (hash: $param): " . $e->getMessage());
    response(500);
    exit;
}
