<?php
    //Find the intercom panel in the gate mode, we find the timestamp of the door opening for the requested apartment
    [
        "date" => $date,
        "ip" => $ip,
        "prefix" => $prefix,
        "apartment" => $apartment
    ] = $postdata;

    $query = "UPDATE houses_flats SET last_opened = :last_opened 
        WHERE  flat= :flat AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix= :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE ip = :ip AND entrance_type = 'wicket'))";
    $params = [
            "ip" => $ip,
            "flat" => $apartment,
            "prefix" => $prefix,
            "last_opened" => $date
        ];

    $result = $db->modify($query, $params);

    response(202, ["id" => $result]);
    exit();