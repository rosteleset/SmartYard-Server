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
 * @apiSuccess {Number} -.id id домофона
 * @apiSuccess {String} -.url url камеры
 * @apiSuccess {String} -.token токен
 * @apiSuccess {String="t","f"} -.frs подключен FRS
 */

auth();

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
