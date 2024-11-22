<?php

    /**
     * parse check and convert string to integer
     *
     * @param $int
     * @return bool
     */

    function checkInt(&$int) {
        if ($int === true) {
            $int = 1;
            return true;
        }
        if ($int === false) {
            $int = 0;
            return true;
        }
        $int_ = trim($int);
        $_int = strval((int)$int);
        if ($int_ != $_int) {
            return false;
        } else {
            $int = (int)$_int;
            return true;
        }
    }
