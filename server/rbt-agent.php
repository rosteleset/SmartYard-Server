<?php

declare(strict_types=1);

require_once __DIR__ . '/edge-agent-controller/bootstrap.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!is_array($headers)) {
    $headers = [];
}
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_') && is_string($value)) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
        $headers[$name] = $value;
    }
}

$requestURI = (string) ($_SERVER['REQUEST_URI'] ?? '');
$path = parse_url($requestURI, PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}
$body = (string) file_get_contents('php://input');
$response = rbtAgentController()->handle(
    (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
    $path,
    $headers,
    $body,
    (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
);

http_response_code($response->status);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $response->body;
