<?php

    /**
     * Determines the preferred language for the application.
     *
     * This function attempts to extract the language code from the "Accept-Language"
     * HTTP header using Apache request headers. If the header is not present or cannot
     * be parsed, it falls back to the language specified in the global $config array.
     * If neither is available, it defaults to "en".
     *
     * @global array $config Application configuration array containing language settings.
     * @return string The detected or default language code (e.g., "en").
     */

    function language() {
        global $config;

        $al = trim(@apache_request_headers()["Accept-Language"] ? : "");

        $al = explode("-", explode(",", explode(";", $al)[0])[0])[0];

        return $al ?: (@$config["language"] ?: "en");
    }

    /**
     * Determines whether an array is associative.
     *
     * Checks if the given array has string keys or non-sequential numeric keys
     * by comparing it against its re-indexed values.
     *
     * @param array $array The array to check.
     *
     * @return bool True if the array is associative, false if it's a sequential indexed array.
     */

    function isAssoc($array) {
        return ($array !== array_values($array));
    }

    /**
     * Translates a message string using the current language.
     *
     * Retrieves the current language setting and uses it to translate the provided
     * message string. Additional arguments can be passed to replace placeholders
     * within the translated message.
     *
     * @param string $msg The message key or string to be translated.
     * @param mixed ...$args Optional arguments to be used for string replacement
     *                        in the translated message.
     *
     * @return string The translated message with any replacements applied.
     *
     * @see i18nL() For the underlying translation logic.
     * @see language() For retrieving the current language.
     */

    function i18n($msg, ...$args) {
        return i18nL(language(), $msg, ...$args);
    }

    /**
     * Translates a message key to the corresponding localized string.
     *
     * Loads translation strings from JSON language files and optionally merges
     * custom translations. Supports nested message keys using dot notation.
     *
     * @param string|null $l The language code (e.g., 'en', 'fr'). If null or false,
     *                        uses the default language from language() function.
     * @param string $msg The message key to translate. Supports dot notation for
     *                     nested keys (e.g., 'errors.notFound', 'common.welcome').
     * @param mixed ...$args Variable number of arguments to be interpolated into
     *                        the translated string using sprintf().
     *
     * @return string The translated message string, with any provided arguments
     *                interpolated. If translation is not found:
     *                - For error keys: returns the key name
     *                - For other keys: returns the original message key
     *
     * @throws void Dies with error message if language file cannot be loaded.
     *
     * @note Language files are expected in /i18n/{lang}.json relative to this file.
     * @note Custom translations can be provided in /i18n/custom/{lang}.json and
     *       will be merged recursively with standard translations.
     * @note Falls back to English language file if specified language is not found.
     */

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
