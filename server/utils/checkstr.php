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

        if (array_key_exists("validChars", $options)) {
            $t = "";

            for ($i = 0; $i < mb_strlen($str); $i++) {
                if (in_array(mb_substr($str, $i, 1), $options["validChars"])) {
                    $t .= mb_substr($str, $i, 1);
                }
            }

            $str = $t;
        }

        if (!in_array("dontStrip", $options)) {
            $str = preg_replace('/\s+/', ' ', $str);
        }

        if (!in_array("dontPurify", $options)) {
            $str = htmlPurifier($str);
        }

        if (array_key_exists("minLength", $options) && mb_strlen($str) < $options["minLength"]) {
            return false;
        }

        if (array_key_exists("maxLength", $options) && mb_strlen($str) > $options["maxLength"]) {
            return false;
        }

        return true;
    }
