<?php

    function object_to_array($data) {
        if ((!is_array($data)) and (!is_object($data))) {
            return $data;
        }

        $result = [];

        $data = (array)$data;

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $value = (array)$value;
            }
            if (is_array($value)) {
                $result[$key] = object_to_array($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
