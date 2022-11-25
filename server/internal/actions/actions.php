<?php

namespace internal\actions;

    use internal\services\response;
    use PDO;
    use PDO_EXT;

    class Actions
    {
        public mixed $config = false;
        public mixed $households = false;
        public mixed $events = false;
        public PDO_EXT $db;

        function __construct()
        {
            $this->events = json_decode(file_get_contents(__DIR__ . "../../../syslog/utils/events.json"),true);
            $this->config = json_decode(file_get_contents(__DIR__ . "../../../config/config.json"), true);
            $this->db = new PDO_EXT(@$this->config["db"]["dsn"], @$this->config["db"]["username"], @$this->config["db"]["password"], @$this->config["db"]["options"]);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            $this->households = loadBackend("households");
        }

        /**
         * Не готово.
         * TODO:  вернуть frs_server, stream_id для дальнейшего формирования POST запроса к FRS на стороне syslog
         * добавить соответствующие методы для работы с backend:
         */
        public function getStreamID($data): void
        {
            ["host" => $host] = $data;

            Response::res(200, "OK", "getStreamID");
        }

        /**
         * Не готово.
         * TODO: сохраняем информацию в БД по событию открытия двери (RFID\code)
         * добавить соответствующие методы для работы с backend:
         * Поиск ключа по SN
         * Обновление информации о ключе
         * @param {{date:string}}
         */
        public function openDoor($data): void
        {
            [
                "date" => $date,
                "ip" => $ip,
                "event" => $event,
                "door" => $door,
                "detail" => $detail,
                "expire" => $expire
            ] = $data;

            if (!isset($data, $ip, $event, $door, $detail, $expire)) {
                Response::res(406, "Invalid payload");
                exit();
            }

             $plogDoorOpen = $this->db->insert("insert into plog_door_open (date, ip, event, door, detail, expire) values (:date, :ip, :event, :door, :detail, :expire)", [
                "date" => $date,
                "ip" => $ip,
                "event" => $event,
                "door" => $door,
                "detail" => $detail,
                "expire" => $expire,
            ]);

            //Прочие действия в соответствии с типом события
            switch ($event) {
                case $this->events['OPEN_BY_KEY']:
                    break;
                case $this->events['OPEN_BY_CODE']:
                    break;
            }

            Response::res(201, "Event saved", ["id"=>$plogDoorOpen]);
        }

        /**
         * Не готово.
         * TODO: сохраняем информацию по завершенному вызову
         */
        public function callFinished($data): void
        {
            [
                "date" => $date,
                "ip" => $ip,
                "call_id" => $call_id,
                "expire" => $expire
            ] = $data;

            if (!isset($call_id)){ $call_id= "not_set";}
            if (!isset($date, $ip, $expire)){
                Response::res(406, "Invalid payload");
                exit();
            }

            $call_done = $this->db->insert("insert into plog_call_done (date, ip, call_id, expire) values (:date, :ip, :call_id, :expire)", [
                "date" => $date,
                "ip" => $ip,
                "call_id" => $call_id,
                "expire" => $expire,
            ]);

            Response::res(201, "Event call_done saved", ["id" => $call_done]);
        }

        /**
         * Не готово.
         * TODO: функционал whiteRabbit
         */
        public function setRabbitGates($data): void
        {
            //
            Response::res(200, "OK", "setRabbitGates");
        }

        //test endpoint
        public function test($data ): void
        {
            [
                "date" => $date,
                "ip" => $ip,
                "event" => $event,
                "door" => $door,
                "detail" => $detail,
                "expire" => $expire
            ] = $data;

            if (isset($data, $ip, $event, $door, $detail, $expire)) {
                $doorOpen = $this->db->insert("insert into plog_door_open (date, ip, event, door, detail, expire) values (:date, :ip, :event, :door, :detail, :expire)", [
                    "date" => $date,
                    "ip" => $ip,
                    "event" => $event,
                    "door" => $door,
                    "detail" => $detail,
                    "expire" => $expire,
                ]);

                Response::res(200, "TEST | Syslog message saved", $doorOpen);
            }
            else {
                Response::res(406, "Invalid payload");
            }
        }

        //test endpoint
        public function health(): void
        {
            Response::res(200, "TEST | Health Ok", false);
        }
    }