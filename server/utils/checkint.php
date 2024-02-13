<?php

    /**
     * parse check and convert string to integer
     *
     * @param $int
     * @return bool
     */

    function checkInt(&$int) {
        $int_ = trim($int);
        $_int = strval((int)$int);
        if ($int_ != $_int) {
            return false;
        } else {
            $int = (int)$_int;
            return true;
        }
    }
