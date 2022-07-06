<?php

    /**
     * check string
     *
     * @param $str
     * @param $options
     * @return bool
     */

    function checkStr(&$str, $options = []) {
        $str = trim($str);

        if (!in_array("dontStrip", $options)) {
            $str = preg_replace('/\s+/', ' ', $str);
        }

        if (!in_array("dontPurify", $options)) {
            $str = htmlPurifier($str);
        }

        if (array_key_exists("minLength", $options) && strlen($str) < $options["minLength"]) {
            return false;
        }

        if (array_key_exists("maxLength", $options) && strlen($str) > $options["maxLength"]) {
            return false;
        }

        return true;
    }
