<?php

namespace internal\actions;

    use internal\services\response;

    class Actions
    {
        public $config = false;
        public $households = false;
        public $events = false;
        function __construct()
        {
            $this->config = json_decode(file_get_contents(__DIR__ . "../../../config/config.json"), true);
            $this->households = loadBackend("households");
            $this->events = json_decode(file_get_contents(__DIR__ . "../../syslog/utils/events.json"),true);
        }

        /***
         * Не готово.
         * TODO: запись в бд последней активности от устройства?
         * Вероятно не актально в связи с использованием clickhouse
         */
        public function lastSeen($data)
        {
            ["ip" => $ip, "date" => $date] = $data;
            //mysql.query(`update dm.domophones set last_seen=now() where ip='${value.host}'`);
            Response::res(200, "OK", "LastSeen");
        }

        /**
         * Не готово.
         * TODO:  вернуть frs_server, stream_id для дальнейшего формирования POST запроса к FRS на стороне syslog
         * добавить соответствующие методы для работы с backend:
         */
        public function getStreamID($data)
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
         */
        public function openDoor($data)
        {
            [
                "host" => $host,
                "event" => $event,
                "door" => $door,
                "detail" => $detail
            ] = $data;

        switch ($event) {
            case $this->events['OPEN_BY_KEY']:
                Response::res(200, "OK", "openDoor by key");
                break;
            case $this->events['OPEN_BY_CODE']:
                Response::res(200, "OK", "openDoor by code");
                break;
        }

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
        public function test($data )
        {
            //$key = $households->getKeys("flat", $data['flat_id']);
            $key = $this->households->getKeys("rfid", $data['rfid']);
            Response::res(200, "TEST | Syslog message saved", $key);
        }

        //test endpoint
        public function health()
        {
            Response::res(200, "TEST | Health Ok", false);
        }
    }