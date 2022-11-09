<?php

namespace internal\actions;

    use internal\services\response;

    class Actions
    {
        public $config = false;
        public $conn;

        function __construct()
        {
            $this->config = json_decode(file_get_contents(__DIR__ . "../../../config/config.json"), true);

            $this->conn = new \mysqli(
                $this->config["clickhouse"]["host"],
                $this->config["clickhouse"]["username"],
                $this->config["clickhouse"]["password"],
                "default",
                $this->config["clickhouse"]["port"]
            );
        }

        /***
         * Не готово.
         * TODO: запись в бд последней активности от устройства
         */
        public function lastSeen($data)
        {
            ["ip" => $ip, "date" => $date] = $data;
            //mysql.query(`update dm.domophones set last_seen=now() where ip='${value.host}'`);
            Response::res(200, "OK", "LastSeen");
        }

        /**
         * Не используется.
         * Сохраняем syslog сообщения в БД
         */
        public function syslogStore($data)
        {
            [
                "date" => $date,
                "ip" => $ip,
                "unit" => $unit,
                "msg" => $msg
            ] = $data;

            if (!isset($data, $date, $ip, $unit, $msg)){
                Response::res(400,"Bad Request","Invalid POST request body");
                exit();
            }

            $result = $this->conn->query("INSERT INTO default.syslog_buffer (date, ip, unit, msg) VALUES ('$date','$ip','$unit','$msg')");
            if ($result) {
                Response::res(201, "Created", "Syslog message saved");
            }
            $this->conn->close();
        }

        /**
         * Не готово.
         * TODO:  вернуть frs_server, stream_id
         */
        public function getStreamID($data)
        {
            ["host" => $host] = $data;

            Response::res(200, "OK", "getStreamID");
        }

        /**
         * Не готово.
         * TODO: сохраняем информацию в БД по событию открытия двери (RFID\code)
         */
        public function openDoor($data)
        {
            [
                "host" => $host,
                "event" => $event,
                "door" => $door,
                "detail" => $detail
            ] = $data;

            Response::res(200, "OK", "openDoor");
        }

        /**
         * Не готово.
         * TODO: сохраняем информацию по завершенному вызову
         */
        public function callFinished($data)
        {
            Response::res(200, "OK", "callFinished");
        }

        /**
         * Не готово.
         * TODO: функционал whiteRabbit
         */
        public function setRabbitGates($data)
        {
            //
            Response::res(200, "OK", "setRabbitGates");
        }

        //test endpoint
        public function test($data)
        {
            Response::res(200, "TEST | Syslog message saved", $data);
        }

        //test endpoint
        public function health()
        {
            Response::res(200, "TEST | Health Ok", false);
        }
    }