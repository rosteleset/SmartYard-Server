<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Service\CameraService;
use Selpol\Service\RedisService;

class CallController extends Controller
{
    public function camshot(): Response
    {
        $this->getSubscriber();

        $hash = $this->getRoute()->getParam('hash');

        if ($hash === null)
            return $this->rbtResponse(404);

        $image = container(RedisService::class)->getRedis()->get('shot_' . $hash);

        if ($image !== false)
            return $this->response()->withString($image)->withHeader('Content-Type', 'image/jpeg');

        return $this->rbtResponse(404);
    }

    public function live(): Response
    {
        $user = $this->getSubscriber();

        if (!$user)
            return $this->rbtResponse(401);

        $hash = $this->getRoute()->getParam('hash');

        if ($hash === null)
            return $this->rbtResponse(404);

        $json_camera = container(RedisService::class)->getRedis()->get("live_" . $hash);
        $camera_params = json_decode($json_camera, true);

        $camera = container(CameraService::class)->get($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

        if (!$camera)
            return $this->rbtResponse(404);

        return $this->response()->withString($camera->camshot())->withHeader('Content-Type', 'image/jpeg');
    }
}