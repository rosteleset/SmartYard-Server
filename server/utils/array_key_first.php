<?php

    if (!function_exists('array_key_first')) {
        function array_key_first(array $arr) {
            foreach ($arr as $key => $unused) {
                return $key;
            }
            return NULL;
        }
    }
