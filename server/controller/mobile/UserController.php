<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;

class UserController extends Controller
{
    public function ping(): Response
    {
        $this->getSubscriber();

        return $this->rbtResponse();
    }

    public function registerPushToken(): Response
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $households = backend('households');
        $isdn = backend('isdn');

        $old_push = $user["pushToken"];

        $push = trim(@$body['pushToken']);
        $voip = trim(@$body['voipToken'] ?: '');

        $production = trim(@$body['production']);

        if (!array_key_exists('platform', $body) || ($body['platform'] != 'ios' && $body['platform'] != 'android' && $body['platform'] != 'huawei'))
            return $this->rbtResponse(422);

        if ($push && (strlen($push) < 16 || strlen($push) >= 1024))
            return $this->rbtResponse(400);

        if ($voip && (strlen($voip) < 16 || strlen($voip) >= 1024))
            return $this->rbtResponse(400);

        if ($body['platform'] == 'ios') {
            $platform = 1;
            if ($voip) {
                $type = ($production == 'f') ? 2 : 1; // apn:apn.dev
            } else {
                $type = 0; // fcm (resend)
            }
        } elseif ($body['platform'] == 'huawei') {
            $platform = 0;
            $type = 3; // huawei
        } else {
            $platform = 0;
            $type = 0; // fcm
        }

        $households->modifySubscriber($user["subscriberId"], ["pushToken" => $push, "tokenType" => $type, "voipToken" => $voip, "platform" => $platform]);

        if (!$push)
            $households->modifySubscriber($user["subscriberId"], ["pushToken" => "off"]);
        else {
            if ($old_push && $old_push != $push) {
                $md5 = md5($push . $old_push);
                $payload = [
                    "token" => $old_push,
                    "messageId" => $md5,
                    "msg" => urlencode("Произведена авторизация на другом устройстве"),
                    "badge" => "1",
                    "pushAction" => "logout"
                ];

                $isdn->push($payload);
            }
        }

        if (!$voip)
            $households->modifySubscriber($user["subscriberId"], ["voipToken" => "off"]);

        return $this->rbtResponse();
    }

    public function sendName(): Response
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $name = htmlspecialchars(trim(@$body['name']));
        $patronymic = htmlspecialchars(trim(@$body['patronymic']));

        if (!$name)
            return $this->rbtResponse(400);

        if ($patronymic) backend('households')->modifySubscriber($user['subscriberId'], ["subscriberName" => $name, "subscriberPatronymic" => $patronymic]);
        else backend('households')->modifySubscriber($user["subscriberId"], ["subscriberName" => $name]);

        return $this->rbtResponse();
    }
}