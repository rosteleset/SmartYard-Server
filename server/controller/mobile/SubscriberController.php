<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorMessage;

class SubscriberController extends Controller
{
    public function index(): Response
    {
        $user = $this->getSubscriber();

        $flatId = $this->getRoute()->getParamInt('flatId');

        if ($flatId === null)
            return $this->rbtResponse(400, message: 'Квартира не указана');

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return $this->rbtResponse(404);

        $subscribers = backend('households')->getSubscribers('flatId', $flat['flatId']);

        return $this->rbtResponse(
            data: array_map(
                static fn(array $subscriber) => [
                    'subscriberId' => $subscriber['subscriberId'],
                    'name' => $subscriber['subscriberName'] . ' ' . $subscriber['subscriberPatronymic'],
                    'mobile' => substr($subscriber['mobile'], -4),
                    'role' => @($this->getFlat($subscriber['flats'], $flat['flatId'])['role'])
                ],
                $subscribers
            )
        );
    }

    public function store(): Response
    {
        $user = $this->getSubscriber();

        $flatId = $this->getRoute()->getParamInt('flatId');

        if ($flatId === null)
            return $this->rbtResponse(400, message: 'Квартира не указана');

        $validate = validate($this->request->getParsedBody(), [
            'mobile' => [Rule::required(), Rule::int(), Rule::min(70000000000), Rule::max(79999999999), Rule::nonNullable()]
        ]);

        if ($validate instanceof ValidatorMessage)
            return $this->rbtResponse(400, message: $validate);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return $this->rbtResponse(404);
        if ($flat['role'] !== 0)
            return $this->rbtResponse(403, message: 'Недостаточно прав для добавления нового жителя');

        $households = backend('households');

        $subscribers = $households->getSubscribers('mobile', $validate['mobile']);

        if (!$subscribers || count($subscribers) === 0)
            return $this->rbtResponse(404, message: 'Житель не зарегестрирован');

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if ($subscriberFlat)
            $this->rbtResponse(400, message: 'Житель уже добавлен');

        if (!$households->addSubscriberToFlat($flat['flatId'], $subscriber['subscriberId']))
            $this->rbtResponse(400, message: 'Житель не был добавлен');

        return $this->rbtResponse();
    }

    public function delete(): Response
    {
        $user = $this->getSubscriber();

        $flatId = $this->getRoute()->getParamInt('flatId');

        if ($flatId === null)
            return $this->rbtResponse(400, message: 'Квартира не указана');

        $validate = validate(['subscriberId' => $this->request->getQueryParam('subscriberId')], [
            'subscriberId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]
        ]);

        if ($validate instanceof ValidatorMessage)
            return $this->rbtResponse(400, message: $validate);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            $this->rbtResponse(404, message: 'Квартира не найдена');

        if ($flat['role'] !== 0)
            $this->rbtResponse(403, message: 'Недостаточно прав для удаления жителя');

        $households = backend('households');

        $subscribers = $households->getSubscribers('id', $validate['subscriberId']);

        if (!$subscribers || count($subscribers) === 0)
            $this->rbtResponse(404, message: 'Житель не зарегестрирован');

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if (!$subscriberFlat)
            $this->rbtResponse(400, message: 'Житель не заселен в данной квартире');

        if ($subscriberFlat['role'] == 0)
            $this->rbtResponse(403, message: 'Житель является владельцем квартиры');

        if (!$households->removeSubscriberFromFlat($flat['flatId'], $subscriber['subscriberId']))
            $this->rbtResponse(400, message: 'Житель не был удален');

        return $this->rbtResponse();
    }

    private function getFlat(array $value, int $id): ?array
    {
        foreach ($value as $item)
            if ($item['flatId'] === $id)
                return $item;

        return null;
    }
}