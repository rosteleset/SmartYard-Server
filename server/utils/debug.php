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
        global $script_debug, $cli;

        if ($script_debug) {
            if ($cli) {
                error_log("[" . date("Y-m-d H:i:s") . "] dbg > " . $msg);
            } else {
                error_log("dbg > " . $msg);
            }

            $accounting = loadBackend('accounting');
            if ($accounting) {
                $accounting->raw("127.0.0.1", unit() . ":dbg", $msg);
            }
        }
    }

    function logMsg($msg) {
        global $script_debug, $cli;

        if ($script_debug) {
            if ($cli) {
                error_log("[" . date("Y-m-d H:i:s") . "] log > " . $msg);
            } else {
                error_log("log > " . $msg);
            }
        }

        $accounting = loadBackend('accounting');
        if ($accounting) {
            $accounting->raw("127.0.0.1", unit() . ":log", $msg);
        }
    }