<?php

    $script_debug = false;

    function debugOn($enable = true) {
        global $script_debug;

        $script_debug = $enable;
    }

    function unit() {
        return basename(get_included_files()[0]);
    }

    function debugMsg($msg) {
        global $script_debug;

        error_log(">>>>>>>>>>>> " . $msg);

        if ($script_debug) {
            $accounting = loadBackend('accounting');
            if ($accounting) {
                $accounting->raw("127.0.0.1", unit() . ":debug", $msg);
            }
        }
    }

    function logMsg($msg) {
        error_log(">>>>>>>>>>>> " . $msg);
        $accounting = loadBackend('accounting');
        if ($accounting) {
            $accounting->raw("127.0.0.1", unit() . ":log", $msg);
        }
    }