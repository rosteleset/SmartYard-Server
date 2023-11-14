<?php

    // response wrapper
    function response($code = 204, $data = false, $name = false, $message = false) {
        global $response_data_source, $response_cache_req, $response_cache_ttl;
        $response_codes = [
            200 => [ 'name' => 'OK', 'message' => 'Хорошо' ],
            201 => [ 'name' => 'Created', 'message' => 'Создано' ],
            202 => [ 'name' => 'Accepted', 'message' => 'Принято' ],
            203 => [ 'name' => 'Non-Authoritative Information', 'message' => 'Информация не авторитетна' ],
            204 => [ 'name' => 'No Content', 'message' => 'Нет содержимого' ],
            205 => [ 'name' => 'Reset Content', 'message' => 'Сбросить содержимое' ],
            206 => [ 'name' => 'Partial Content', 'message' => 'Частичное содержимое' ],
            207 => [ 'name' => 'Multi-Status', 'message' => 'Многостатусный' ],
            208 => [ 'name' => 'Already Reported', 'message' => 'Уже сообщалось' ],
            226 => [ 'name' => 'IM Used', 'message' => 'Использовано IM' ],
            400 => [ 'name' => 'Bad Request', 'message' => 'Плохой, неверный запрос' ],
            401 => [ 'name' => 'Unauthorized', 'message' => 'Не авторизован' ],
            402 => [ 'name' => 'Payment Required', 'message' => 'Необходима оплата' ],
            403 => [ 'name' => 'Forbidden', 'message' => 'Запрещено' ],
            404 => [ 'name' => 'Not Found', 'message' => 'Не найдено' ],
            405 => [ 'name' => 'Method Not Allowed', 'message' => 'Метод не поддерживается' ],
            406 => [ 'name' => 'Not Acceptable', 'message' => 'Неприемлемо' ],
            407 => [ 'name' => 'Proxy Authentication Required', 'message' => 'Необходима аутентификация прокси' ],
            408 => [ 'name' => 'Request Timeout', 'message' => 'Истекло время ожидания' ],
            409 => [ 'name' => 'Conflict', 'message' => 'Конфликт' ],
            410 => [ 'name' => 'Gone', 'message' => 'Удалён' ],
            411 => [ 'name' => 'Length Required', 'message' => 'Необходима длина' ],
            412 => [ 'name' => 'Precondition Failed', 'message' => 'Условие ложно' ],
            413 => [ 'name' => 'Payload Too Large', 'message' => 'Полезная нагрузка слишком велика' ],
            414 => [ 'name' => 'URI Too Long', 'message' => 'URI слишком длинный' ],
            415 => [ 'name' => 'Unsupported Media Type', 'message' => 'Неподдерживаемый тип данных' ],
            416 => [ 'name' => 'Range Not Satisfiable', 'message' => 'Диапазон не достижим' ],
            417 => [ 'name' => 'Expectation Failed', 'message' => 'Ожидание не удалось' ],
            418 => [ 'name' => 'I’m a teapot', 'message' => 'Я — чайник' ],
            419 => [ 'name' => 'Authentication Timeout (not in RFC 2616)', 'message' => 'Обычно ошибка проверки CSRF' ],
            421 => [ 'name' => 'Misdirected Request', 'message' => 'Запрос направлен неверно' ],
            422 => [ 'name' => 'Unprocessable Entity', 'message' => 'Необрабатываемый экземпляр' ],
            423 => [ 'name' => 'Locked', 'message' => 'Заблокировано' ],
            424 => [ 'name' => 'Failed Dependency', 'message' => 'Невыполненная зависимость' ],
            426 => [ 'name' => 'Upgrade Required', 'message' => 'Необходимо обновление' ],
            428 => [ 'name' => 'Precondition Required', 'message' => 'Необходимо предусловие' ],
            429 => [ 'name' => 'Too Many Requests', 'message' => 'Слишком много запросов' ],
            431 => [ 'name' => 'Request Header Fields Too Large', 'message' => 'Поля заголовка запроса слишком большие' ],
            449 => [ 'name' => 'Retry With', 'message' => 'Повторить с' ],
            451 => [ 'name' => 'Unavailable For Legal Reasons', 'message' => 'Недоступно по юридическим причинам' ],
            499 => [ 'name' => 'Client Closed Request', 'message' => 'Клиент закрыл соединение' ],
            503 => [ 'name' => 'Service Unavailable', 'message' => 'Сервис недоступен' ],
        ];
        header('Content-Type: application/json');
        http_response_code($code);
        if ((int)$code < 300 && $response_cache_req && $response_data_source == 'db' && (int)$response_cache_ttl > 0) {
    //        $redis->setEx($response_cache_req, $response_cache_ttl, json_encode([
    //            'code' => $code,
    //            'data' => $data,
    //        ], JSON_UNESCAPED_UNICODE));
        }
        if ((int)$code == 204) {
            exit;
        }
        $ret = [
            'code' => $code,
        ];
        if (!$message) {
            if ($name) {
                $message = $name;
            } else {
                $message = @$response_codes[$code]['message'];
            }
        }
        if (!$name) {
            $name = @$response_codes[$code]['name'];
        }
        if ($name) {
            $ret['name'] = $name;
        }
        if ($message) {
            $ret['message'] = $message;
        }
        if ($data) {
            $ret['data'] = $data;
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        exit;
    }
