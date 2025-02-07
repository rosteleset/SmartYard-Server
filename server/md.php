<?php

    require_once 'vendor/autoload.php';

    use Detection\MobileDetect;
    $detect = new MobileDetect();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($detect->isMobile());
