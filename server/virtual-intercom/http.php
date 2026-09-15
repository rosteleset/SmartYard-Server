<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    require_once __DIR__ . '/bootstrap.php';
    $service = \VirtualIntercom\Service::configured();
    $action = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $method = $_SERVER['REQUEST_METHOD'];
    $id = (string)($_GET['id'] ?? '');
    $token = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($method === 'POST') {
        $service->checkOrigin($_SERVER['HTTP_ORIGIN'] ?? null);
    }
    $result = [];
    if ($method === 'GET' && $action === 'panel') {
        $result = $service->metadata((string)($_GET['panel'] ?? ''));
    } elseif ($method === 'GET' && $action === 'status') {
        $result = $service->status($id, $token);
    } elseif ($method === 'POST' && in_array($action, ['session', 'open-code'], true)) {
        if (($_SERVER['CONTENT_TYPE'] ?? '') !== 'application/json') {
            throw new RuntimeException('Ожидается JSON', 415);
        }
        $body = file_get_contents('php://input', false, null, 0, 4097);
        if (strlen($body) > 4096) {
            throw new RuntimeException('Слишком большой запрос', 413);
        }
        $data = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
        if ($action === 'open-code') {
            if (!is_string($data['panel'] ?? null) || !is_string($data['code'] ?? null)) {
                throw new RuntimeException('Некорректный запрос', 400);
            }
            $result = $service->openByCode($data['panel'], $data['code'], $_SERVER['REMOTE_ADDR']);
        } else {
            $result = $service->create((string)($data['panel'] ?? ''), (int)($data['flatId'] ?? 0), $_SERVER['REMOTE_ADDR']);
        }
    } elseif ($method === 'POST' && $action === 'frame') {
        $service->frame($id, $token, file_get_contents('php://input', false, null, 0, 180001));
    } elseif ($method === 'POST' && $action === 'cancel') {
        $service->cancel($id, $token);
    } else {
        throw new RuntimeException('Не найдено', 404);
    }
    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code > 499) {
        $code = 503;
    }
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $code === 503 ? 'Сервис временно недоступен' : $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
