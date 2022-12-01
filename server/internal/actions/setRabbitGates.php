<?php
//Находим домофонную панель в режиме калитки, устанавливаем временную метку открытия двери для запрошенной квартиры
    [
        "date" => $date,
        "ip" => $ip,
        "prefix" => $prefix,
        "apartment" => $apartment
    ] = $postdata;

    $query = 'SELECT house_flat_id, last_opened, white_rabbit 
                FROM houses_flats 
                WHERE  flat= :flat AND white_rabbit > 0 AND address_house_id = (
                SELECT address_house_id from houses_houses_entrances 
                WHERE prefix= :prefix AND house_entrance_id = (
                SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
                WHERE ip = :ip AND entrance_type = "wicket"))';
    $params = [
        "ip" => $ip,
        "flat" => $apartment,
        "prefix" => $prefix,
    ];
    [
        0 => [
            'house_flat_id' => $house_flat_id,
            "last_opened" => $last_opened,
            "white_rabbit" => $white_rabbit
        ]
    ] = $db->get($query, $params);

    $query_update = "UPDATE houses_flats SET last_opened = :last_opened WHERE house_flat_id= :house_flat_id ";
    $params_update = ["last_opened" => $date, "house_flat_id" => $house_flat_id];
    $res_update = $db->modify($query_update, $params_update);

    response(202, ["id" => $res_update]);
    exit();