<?php

    function autoconfigure_domophone($domophone_id, $first_time = false) {
        $households = loadBackend('households');
        $domophone = $households->getDomophone($domophone_id);

        try {
            $panel = loadDomophone($domophone['model'], $domophone['url'], $domophone['credentials'], $first_time);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        if ($first_time) {
            $panel->prepare();
        }

        print_r($panel->get_sysinfo());
    }
