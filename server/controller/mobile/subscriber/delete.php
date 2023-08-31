<?php

use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorMessage;

$user = auth();

if (isset($postdata)) {
    $validate = validate(@$postdata ?? [], [
        'flatId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
        'subscriberId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]
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
        response(403, message: 'Недостаточно прав для удаления жителя');

    $households = backend('households');

    $subscribers = $households->getSubscribers('id', $validate['subscriberId']);

    if (!$subscribers || count($subscribers) === 0)
        response(404, message: 'Житель не зарегестрирован');

    $subscriber = $subscribers[0];

    $subscriberFlat = get_flat($subscriber['flats'], $flat['flatId']);

    if (!$subscriberFlat)
        response(400, message: 'Житель не заселен в данной квартире');

    if ($subscriberFlat['role'] == 0)
        response(403, message: 'Житель является владельцем квартиры');

    if (!$households->removeSubscriberFromFlat($flat['flatId'], $subscriber['subscriberId']))
        response(400, message: 'Житель не был удален');

    response(200);
}