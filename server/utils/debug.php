<?php

    $script_debug = false;

    function debugOn($enable = true) {
        global $script_debug;

        $script_debug = $enable;
    }

    function unit() {
        $it = get_included_files();

        $path = '';

        foreach ($it as $f) {
            $path .= basename($f) . '\\';
        }

        return substr($path, 0, -1);
    }

    function debugMsg($msg) {
        global $script_debug;

        if ($script_debug) {
            $accounting = loadBackend('accounting');
            if ($accounting) {
                $accounting->raw("127.0.0.1", unit(), "debug: " . $msg);
            }
        }
    }

    function logMsg($msg) {
        $accounting = loadBackend('accounting');
        if ($accounting) {
            $accounting->raw("127.0.0.1", unit(), "log" . $msg);
        }
    }