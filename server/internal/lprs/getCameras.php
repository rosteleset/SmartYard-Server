<?php

function parseHostPort(string $url): array
{
    $parsed = parse_url($url);
    $host = trim($parsed['host'] ?? '', '[]');
    $port = $parsed['port'] ?? match (strtolower($parsed['scheme'] ?? '')) {
        'https' => 443,
        default => 80,
    };

    return [
        'host' => $host,
        'port' => $port,
    ];
}


$camera_model= $param;

if (empty($camera_model)) {
    response(400);
}

$cameras = loadBackend("cameras");
if (!$cameras) {
    response(500);
}

$model_to_login = [
    "brovotech" => "admin",
    "dahua" => "admin",
    "fake" => "admin",
];

$result = [];
$data = $cameras->getCameras("model_frs", $camera_model);

$login = null;
foreach ($data as $camera) {
    ['host' => $host, 'port' => $port] = parseHostPort($camera["url"]);
    $username = $model_to_login[$camera_model] ?? '';
    $password = $camera['credentials'] ?? '';
    $stream_id = $camera['cameraId'];

    $result[] = [
        "host" => $host,
        "port" => $port,
        "username" => $username,
        "password" => $password,
        "streamId" => $stream_id,
    ];
}

if (count($result) > 0) {
    response(200, $result);
} else {
    response(204);
}
