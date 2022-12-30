<?php

    function cleanup() {
        global $config;

        foreach ($config["backends"] as $backend => $_) {
            $b = loadBackend($backend);

            if ($b) {
                $n = $b->cleanup();

                if ($n !== false) {
                    echo "$backend: $n items cleaned\n";
                }
            } else {
                echo "$backend: not found\n";
            }
        }
    }
