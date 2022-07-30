<?php

/**
 
 **/

    auth();

    // отвечает за отображение раздела оплаты и городских камер
    response(200, [
        "cityCams" => "f",
        "payments" => "f",
        "paymentsUrl" => "https://your.url.of.payments.page", 
        "supportPhone" => "+7(4752)429999"
    ]);
    