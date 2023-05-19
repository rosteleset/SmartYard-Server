<?php

/**
 * @api {post} /cctv/camMap отношения домофонов и камер
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup CCTV
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив c настройками
 * @apiSuccess {String} -.id id домофона
 * @apiSuccess {String} -.url url камеры
 * @apiSuccess {String} -.token токен
 * @apiSuccess {String="t","f"} -.frs подключен FRS
 * @apiSuccess {String="nimble","flussonic", "macroscop", "trassir"} [-.serverType] тип видео-сервера ('flussonic' by default)
 */
auth();

$ret = [];

$house_id = (int)@$postdata['houseId'];
$households = loadBackend("households");
$cameras = loadBackend("cameras");

$houses = [];
$cams = [];

foreach($subscriber['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];
    
    if (array_key_exists($houseId, $houses)) {
        $house = &$houses[$houseId];
        
    } else {
        $houses[$houseId] = [];
        $house = &$houses[$houseId];
        $house['houseId'] = strval($houseId);
        $house['doors'] = [];
    }
    
    $flatDetail = $households->getFlat($flat['flatId']);
    foreach ($flatDetail['entrances'] as $entrance) {
        if (in_array($entrance['entranceId'], $house['doors'])) {
            continue;
        }
        
        $e = $households->getEntrance($entrance['entranceId']);
        
        if ($e['cameraId'] && !array_key_exists($entrance['entranceId'], $cams)) {
            $cam = $cameras->getCamera($e["cameraId"]);
            $cams[$entrance['entranceId']] = $cam;
        }
        
        $house['doors'][] = $entrance['entranceId'];
        
    }
    
}

foreach($cams as $entrance_id => $cam) {
    $e = $households->getEntrance($entrance_id);
    $dvr = loadBackend("dvr")->getDVRServerByStream($cam['dvrStream']);
    $frs = 'f';
    $cameras = loadBackend("cameras");
    if ($cameras) {
        $vstream = $cameras->getCamera($e['cameraId']);
        $frs = strlen($vstream["frs"]) > 1 ? 't' : 'f';
    }
    $ret[] = [
        'id' => strval($e['domophoneId']),
        'url' => $cam['dvrStream'],
        'token' => loadBackend("dvr")->getDVRTokenForCam($cam, $subscriber['subscriberId']),
        'frs' => $frs,
        'serverType' => $dvr['type']
    ];
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}


/*
$server_map = [
    '193.203.61.11' => 'https://fl2.lanta.me:8443/',
    '193.203.61.5' => 'https://fl3.lanta.me:8443/',
];

$r = [];

$d = implode(', ', all_domophones());
if ($d) {
    $token = @trim(file_get_contents("http://fl2.lanta.me:8081/token"));
    $qr = mysql("select * from dm.cams where domophone_id in ($d)");
    while ($row = mysqli_fetch_assoc($qr)) {
        $r[] = [
            'id' => $row['domophone_id'],
            'url' => $server_map[$row['server']].$row['name'],
            'token' => $token,
            'frs' => ((int)$row['frs'])?'t':'f',
        ];
    }
}

if (count($r)) {
    response(200, $r);
} else {
    response();
}
*/
