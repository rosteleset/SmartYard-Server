<?php

    /**
     * returns data to client and terminate script
     *
     * @param integer $code
     * @param mixed $data
     * @return void
     */

    function response($code = 204, $data = false) {
        global $params, $backends;

        header('Content-Type: application/json');
        http_response_code($code);

        if ((int)$code == 204) {
            $backends["accounting"]->log($params, $code);
            exit;
        }

        if ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        if ($backends && $backends["accounting"]) {
            $backends["accounting"]->log($params, $code);
        } else {
            $login = @($params["_login"]?:$params["login"]);
            $login = $login?:"-";
            error_log("{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} [$code]: {$_SERVER['REQUEST_METHOD']} $login {$_SERVER["REQUEST_URI"]}");
        }

        exit;
    }
