<?php

use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorMessage;

$user = auth();

if (isset($postdata)) {
    $validate = validate(@$postdata ?? [], [
        'flatId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
        'mobile' => [Rule::required(), Rule::int(), Rule::min(70000000000), Rule::max(79999999999), Rule::nonNullable()]
    ]);

    if ($validate instanceof ValidatorMessage)
        response(400, message: $validate->getMessage());

    function get_flat(array $value, int $id): ?array
    {
        foreach ($value as $item)
            if ($item['flatId'] === $id)
                return $item;

        return null;
    }

    $flat = get_flat($user['flats'], $validate['flatId']);

    if ($flat === null)
        response(404, message: 'Квартира не найдена');

    if ($flat['role'] !== 0)
        response(403, message: 'Недостаточно прав для добавления нового жителя');

    $households = backend('households');

    if (!$households)
        response(500);

    $subscribers = $households->getSubscribers('mobile', $validate['mobile']);

    if (!$subscribers || count($subscribers) === 0)
        response(404, message: 'Житель не зарегестрирован');

    $subscriber = $subscribers[0];

    $subscriberFlat = get_flat($subscriber['flats'], $flat['flatId']);

    if ($subscriberFlat)
        response(400, message: 'Житель уже добавлен');

    if (!$households->addSubscriberToFlat($flat['flatId'], $subscriber['subscriberId']))
        response(400, message: 'Житель не был добавлен');

    response(200);
}