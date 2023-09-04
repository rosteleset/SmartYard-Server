<?php

use Selpol\Service\CameraService;

$camera_id = $param;
if (!isset($camera_id) || $camera_id === 0)
    return response(404);

$logger = logger('internal');

$logger->debug('camshot() start', ['camera' => $camera_id]);

$cameras = backend("cameras");
if (!$cameras)
    return response(404);

$cam = $cameras->getCamera($camera_id);
if (!$cam)
    return response(404);

$model = container(CameraService::class)->get($cam['model'], $cam['url'], $cam['credentials']);

if (!$model)
    return response(404);

$logger->debug('camshot() end', ['camera' => $camera_id]);

header('Content-Type: image/jpeg');
echo $model->camshot();

exit;