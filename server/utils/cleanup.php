<?php

    function cleanup() {
        global $config;

        foreach ($config["backends"] as $backend => $_) {
            $n = loadBackend($backend)->cleanup();

            if ($n !== false) {
                echo "$backend: $n items cleaned\n";
            }
        }
    }
