<?php

use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorMessage;

$user = auth();

if (isset($postdata)) {
    $validate = validate(@$postdata ?? [], ['flatId' => [Rule::required(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

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

    $subscribers = backend('households')->getSubscribers('flatId', $flat['flatId']);

    response(
        200,
        array_map(
            static fn(array $subscriber) => [
                'subscriberId' => $subscriber['subscriberId'],
                'name' => $subscriber['subscriberName'] . ' ' . $subscriber['subscriberPatronymic'],
                'mobile' => substr($subscriber['mobile'], -4),
                'role' => @(get_flat($subscriber['flats'], $flat['flatId'])['role'])
            ],
            $subscribers
        )
    );
}