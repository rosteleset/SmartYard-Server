<?php

    if (!function_exists('array_is_list')) {
        function array_is_list($arr) {
            if ($arr === []) {
                return true;
            }
            return array_keys($arr) === range(0, count($arr) - 1);
        }
    }
