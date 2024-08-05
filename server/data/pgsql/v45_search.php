<?php

    function v45_search($db)
    {
        global $config;

        try {
            $text_search_config = $config["db"]["text_search_config"] ?? "simple";
            $db->modify("DROP INDEX IF EXISTS addresses_houses_house_full_gin");
            $db->modify("CREATE INDEX IF NOT EXISTS addresses_houses_house_full_gin ON addresses_houses USING GIN(to_tsvector('$text_search_config', house_full))");
            echo "CREATE INDEX IF NOT EXISTS addresses_houses_house_full_gin ON addresses_houses USING GIN(to_tsvector('$text_search_config', house_full))\n";
            return true;
        } catch (\PDOException $e) {
            echo $e->getMessage() . "\n";
            return false;
        }
    }
