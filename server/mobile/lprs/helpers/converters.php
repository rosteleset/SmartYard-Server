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

function isValidPlateNumber(string $number): bool {
    $digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $chars = ['A', 'B', 'C', 'E', 'H', 'K', 'M', 'O', 'P', 'T', 'X', 'Y'];

    if (strlen($number) < 8 || strlen($number) > 9) {
        return false;
    }

    for ($i = 0; $i < strlen($number); $i++) {
        if ($i == 0 || $i == 4 || $i == 5) {
            if (!in_array($number[$i], $chars)) {
                return false;
            }
        } elseif (!in_array($number[$i], $digits)) {
            return false;
        }
    }

    return true;
}
