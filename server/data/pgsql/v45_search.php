<?php

    function v45_search($db)
    {
        global $config;

        try {
            $text_search_config = $config["db"]["text_search_config"] ?? "simple";
            $db->modify("CREATE INDEX IF NOT EXISTS addresses_houses_house_full_fts ON addresses_houses USING GIN (to_tsvector('$text_search_config', house_full))");
            $db->modify("CREATE INDEX IF NOT EXISTS houses_subscribers_mobile_subscriber_full_fts ON houses_subscribers_mobile USING GIN (to_tsvector('$text_search_config', subscriber_full))");
            echo "CREATE INDEX IF NOT EXISTS addresses_houses_house_full_gin ON addresses_houses USING GIN (to_tsvector('$text_search_config', house_full))\n";
            echo "CREATE INDEX IF NOT EXISTS houses_subscribers_mobile_subscriber_full_fts ON houses_subscribers_mobile USING GIN (to_tsvector('$text_search_config', subscriber_full))\n";
            return true;
        } catch (\PDOException $e) {
            echo $e->getMessage() . "\n";
            return false;
        }
    }
