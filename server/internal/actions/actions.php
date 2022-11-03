<?php

    namespace internal\actions;

    use internal\services\response;

    class Actions
    {
        public function lastSeen($data)
        {
            //TODO: запись в бд последней активности от устройства
            Response::res(200, "LastSeen", false);
        }

        public function syslogStore($data)
        {
            //TODO: сохраняем собщение syslog в DB
            Response::res(201, "Syslog message saved", false);
        }

        public function getStreamID($data)
        {
            //TODO: вернуть frs_server, stream_id
            Response::res(200, "getStreamID", false);
        }

        public function openDoor($data)
        {
            //TODO: сохраняем информацию по открытию двери (rfid\code)
            Response::res(200, "openDoor", false);
        }

        public function callFinished($data)
        {
            //TODO: сохраняем инфу по завершенному вызову
            Response::res(200, "callFinished", false);
        }

        public function setRabbitGates($data)
        {
            //TODO: функционал whiteRabbit
            Response::res(200, "setRabbitGates", false);
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
