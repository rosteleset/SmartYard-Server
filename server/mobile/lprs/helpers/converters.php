<?php

const RU_TO_LAT = [
    'А' => 'A',
    'В' => 'B',
    'Е' => 'E',
    'К' => 'K',
    'М' => 'M',
    'Н' => 'H',
    'О' => 'O',
    'Р' => 'P',
    'С' => 'C',
    'Т' => 'T',
    'У' => 'Y',
    'Х' => 'X',
];

function toLatin(string $number): string {
    $number = mb_strtoupper($number);
    return strtr($number, RU_TO_LAT);
}

function isValidPlateNumber(string $number, string $countryCode = 'ru'): bool {
    $countryCode = strtolower(trim($countryCode));

    switch ($countryCode) {
        case 'ru':
            $regex = '/^[ABCEHKMOPTXY]\d{3}[ABCEHKMOPTXY]{2}\d{2,3}$/';
            return preg_match($regex, $number) === 1;
        default:
            return false;
    }
}
