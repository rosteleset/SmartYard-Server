<?php

    function formatUsage($str) {
        $str = explode("\n", trim($str));
        $usage = "";
        $usage .= $str[0] . "\n";
        for ($i = 1; $i < count($str); $i++) {
            $s = trim($str[$i]);
            if (!$s) {
                $usage .= "\n";
                continue;
            }
            if ($s[0] == "[") {
                $usage .= "    " . $s . "\n";
                continue;
            }
            $usage .= "  " . $s . "\n";
        }

        return trim($usage) . "\n\n";
    }