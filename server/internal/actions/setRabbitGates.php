<?php

    /**
     * @api {post} /actions/setRabbitGates 'gate mode' feature processing
     * @apiVersion 1.0.0
     * @apiDescription *** testing ***
     *
     * @apiGroup internal
     *
     * @apiParam {Object}
     * @apiParam {Number} date timestamp related to the call finished event.
     * @apiParam {string|null} ip IP address associated with the event.
     * @apiParam {string|null} subId subscription ID related to the event
     * @apiParam {Number} prefix house prefix.
     * @apiParam apartmentNumber apartment (flat) number.
     * @apiParam apartmentId apartment ID.
     *
     *
     * @apiSuccess {Number} status code indicating success
     *
     * @apiErrorExample {json} Error Responses:
     *      HTTP/1.1 406 Invalid payload
     *      HTTP/1.1 404 Not found
     */

    if (!isset(
        $postdata["prefix"],
        $postdata["apartmentNumber"],
        $postdata["apartmentId"],
        $postdata["date"],
    )) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    if (!isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    [
        "ip" => $ip,
        "subId" => $subId,
        "prefix" => $prefix,
        "apartmentNumber" => $apartmentNumber,
        "apartmentId" => $apartmentId,
        "date" => $date,
    ] = $postdata;

    //FIXME: move SQL request to backend 'helpers'
    $query = "UPDATE houses_flats SET last_opened = :last_opened
        WHERE (flat = :flat OR house_flat_id = :house_flat_id) AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix = :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE (ip = :ip OR sub_id = :sub_id) AND entrance_type = 'wicket'))";

    $params = [
        "ip" => $ip,
        "sub_id" => $subId,
        "flat" => $apartmentNumber,
        "house_flat_id" => $apartmentId,
        "prefix" => $prefix,
        "last_opened" => $date,
    ];

    $result = $db->modify($query, $params);

    response(202, ["id" => $result]);
    exit();