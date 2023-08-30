<?php

use Selpol\Validator\Rule;

$user = auth();

if (isset($postdata)) {
    $validate = validate(@$postdata ?? [], [
        'flatId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
        'mobile' => [Rule::required(), Rule::int(), Rule::min(11), Rule::max(11), Rule::nonNullable()]
    ]);

    if (!$validate)
        response(400, message: 'Идентификатор квартиры или номер телефона указан не верно');

    $flat = get_flat($user['flats'], $validate['flatId']);

    if ($flat === null)
        response(404, message: 'Квартира не найдена');

    if ($flat['role'] !== 0)
        response(403, message: 'Недостаточно прав для добавления нового жителя');

    $households = loadBackend('households');

    $subscribers = $households->getSubscribers('mobile', $validate['mobile']);

    if (!$subscribers || count($subscribers) === 0)
        response(404, message: 'Житель не зарегестрирован');

    $subscriber = $subscribers[0];

    $subscriberFlat = get_flat($subscriber['flats'], $flat['flatId']);

    if ($subscriberFlat)
        response(400, message: 'Житель уже добавлен');

    if (!$households->addSubscriberToFlat($flat['flatId'], $subscriber['subscriberId']))
        response(400, message: 'Житель не был добавлен');
}