<?php

    require __DIR__ . '/mzfc/mobiledetect/vendor/autoload.php';

    use Detection\MobileDetect;
    $detect = new MobileDetect();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($detect->isMobile());
