<?php

use Selpol\Validator\Rule;

$user = auth();

if (isset($postdata)) {
    $validate = validate(@$postdata ?? [], ['flatId' => [Rule::required(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

    if (!$validate)
        response(400, message: 'Идентификатор квартиры обязателен для заполнения');

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

    $subscribers = loadBackend('households')->getSubscribers('flatId', $flat['flatId']);

    response(
        200,
        array_map(
            static fn(array $subscriber) => [
                'subscriberId' => $subscriber['subscriberId'],
                'name' => $subscriber['subscriber_name'] . ' ' . $subscriber['subscriber_patronymic'],
                'mobile' => substr($subscriber['mobile'], -4, 0),
                'role' => @(get_flat($subscriber['flats'], $flat['flatId'])['role'])
            ],
            $subscribers
        )
    );
}