<?php

use Selpol\Validator\Rule;

$user = auth();

if (isset($params)) {
    $validate = validate(@$params ?? [], ['_id' => [Rule::required(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

    if (!$validate)
        response(400);

    function get_flat(array $value, int $id): ?array
    {
        $index = array_search(static fn(array $item) => $item['flatId'] == $id, $value);

        if ($index === false)
            return null;

        return $value[$index];
    }

    $flat = get_flat($user['flats'], $validate['_id']);

    if ($flat === null)
        response(404);

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
