<?php

    function language() {
        global $config;

        $al = trim(@apache_request_headers()["Accept-Language"] ? : "");

        $al = explode("-", explode(",", explode(";", $al)[0])[0])[0];

        return $al ?: (@$config["language"] ?: "en");
    }

    function isAssoc($array) {
        return ($array !== array_values($array));
    }

    function i18n($msg, ...$args) {
        $lang = language();
        try {
            $lang = json_decode(file_get_contents(__DIR__ . "/../i18n/$lang.json"), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            try {
                $lang = json_decode(file_get_contents(__DIR__ . "/../i18n/en.json"), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                die("can't load language file\n");
            }
        }
        if (!$lang) {
            die("can't load language file\n");
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
