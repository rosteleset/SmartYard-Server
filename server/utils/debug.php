<?php

    $script_debug = false;

    function debugOn($enable = true) {
        global $script_debug;

        $script_debug = $enable;
    }

    function debug($msg, $unit = 'debug') {
        global $script_debug;

        if ($script_debug) {
            $accounting = loadBackend('accounting');
            if ($accounting) {
                $accounting->raw("127.0.0.1", $unit, $msg);
            }
        }
    }
