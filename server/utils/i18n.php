<?php

    function language() {
        global $config;

        return @$config["language"]?:"ru";
    }

    function isAssoc($array)
    {
        return ($array !== array_values($array));
    }

    function i18n($msg, ...$args) {
        $lang = language();
        try {
            $lang = json_decode(file_get_contents(__DIR__ . "/../i18n/$lang.json"), true);
        } catch (\Exception $e) {
            $lang = [];
        }
        try {
            $t = explode(".", $msg);
            if (count($t) > 2) {
                $st = [];
                $st[0] = array_shift($t);
                $st[1] = implode(".", $t);
                $t = $st;
            }
            if (count($t) === 2) {
                $loc = $lang[$t[0]][$t[1]];
            } else {
                $loc = $lang[$t[0]];
            }
            if ($loc) {
                if (is_array($loc) && !isAssoc($loc)) {
                    $loc = nl2br(implode("\n", $loc));
                }
                $loc = sprintf($loc, ...$args);
            }
            if (!$loc) {
                if ($t[0] === "errors") {
                    return $t[1];
                } else {
                    return $msg;
                }
            }
            return $loc;
        } catch (\Exception $e) {
            return $msg;
        }
    }
