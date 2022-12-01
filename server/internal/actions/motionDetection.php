<?php
    /**TODO: Принимаем запрос от syslog. payload: ip.
     * Не готово
     * -v1. По ip устройства имеющего функционал "детекция движения" вернуть stream_id видеопотока,
     * далее nodejs выполнить POST запрос к FRS инициализации распознования объекта.
     *
     * +v2 Получив ip устройства имеющего функционал "детекция движения" найти stream_id видеопотока.
     * Выполнить POST запрос: https://frs-host-example.dev/motionDetection
     * payload {stream_id:number, start:boolean}
    */

    [
        "date" => $date,
        "ip" => $ip,
        "motionStart" => $motionStart
    ] = $postdata;

    //тест

    response(200);