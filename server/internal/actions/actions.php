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

        public function lastSeen($data)
        {
            //TODO: запись в бд последней активности от устройства
            Response::res(200, "OK", "LastSeen");
        }

        /**
        Сохраняем syslog сообщения в БД
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

        public function getStreamID($data)
        {
            //TODO: вернуть frs_server, stream_id
            Response::res(200, "OK", "getStreamID");
        }

        public function openDoor($data)
        {
            //TODO: сохраняем информацию по открытию двери (rfid\code)
            Response::res(200, "OK", "openDoor");
        }

        public function callFinished($data)
        {
            //TODO: сохраняем инфу по завершенному вызову
            Response::res(200, "OK", "callFinished");
        }

        public function setRabbitGates($data)
        {
            //TODO: функционал whiteRabbit
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