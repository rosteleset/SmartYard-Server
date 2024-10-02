<?php

    function schema($schema) {
        global $config, $db;

        $tables = @json_decode(file_get_contents("data/tables.json"), true);

        if ($tables && is_array($tables) && count($tables)) {
            foreach ($tables as $table) {
                echo "ALTER TABLE IF EXISTS public.$table SET SCHEMA $schema\n";
                $db->exec("ALTER TABLE IF EXISTS public.$table SET SCHEMA $schema");
            }
        }
    }