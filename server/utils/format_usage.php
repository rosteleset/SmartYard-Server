<?php

    function formatUsage($str) {
        $str = explode("\n", $str);
        echo $str[0] . "\n";
        for ($i = 1; $i < count($str); $i++) {
            $s = trim($str[$i]);
            if (!$s) {
                echo "\n";
                continue;
            }
            if ($s[0] == "[") {
                echo "    " . $s . "\n";
                continue;
            }
            echo "  " . $s . "\n";
        }
    }