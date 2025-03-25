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
        return i18nL(language(), $msg, ...$args);
    }

    function i18nL($l, $msg, ...$args) {
        if (!$l) {
            $l = language();
        }
        $lang = false;
        $clang = false;
        try {
            $lang = json_decode(@file_get_contents(__DIR__ . "/../i18n/$l.json"), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            try {
                $lang = json_decode(@file_get_contents(__DIR__ . "/../i18n/en.json"), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                die("can't load language file\n");
            }
        }
        try {
            $clang = json_decode(@file_get_contents(__DIR__ . "/../i18n/custom/$l.json"), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            try {
                $clang = json_decode(@file_get_contents(__DIR__ . "/../i18n/custom/en.json"), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                //
            }
        }
        if (!$lang || !is_array($lang)) {
            die("can't load language file\n");
        }
        if ($clang && is_array($clang)) {
            $lang = array_replace_recursive($lang, $clang);
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
