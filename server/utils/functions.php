<?php

    /**
     * Recursively computes the difference between two associative arrays.
     *
     * This function compares the values of two associative arrays, including nested arrays,
     * and returns the differences. It supports both strict (type-sensitive) and non-strict (type-coercive) comparisons.
     *
     * @param array $array1 The first array to compare.
     * @param array $array2 The second array to compare against.
     * @param bool $strict (Optional) Whether to perform strict type comparisons (default: `true`).
     *
     * @return array The differences between `$array1` and `$array2`.
     */

    function array_diff_assoc_recursive(array $array1, array $array2, bool $strict = true): array {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $newDiff = array_diff_assoc_recursive($value, $array2[$key], $strict);

                    if (!empty($newDiff)) {
                        $difference[$key] = $newDiff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || ($strict ? $array2[$key] !== $value : $array2[$key] != $value)) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * parse check and convert string to integer
     *
     * @param $int
     * @return bool
     */

    function checkInt(&$int) {
        if ($int === true) {
            $int = 1;
            return true;
        }
        if ($int === false || $int == null) {
            $int = 0;
            return true;
        }
        $int_ = trim($int);
        $_int = strval((int)$int);
        if ($int_ != $_int) {
            return false;
        } else {
            $int = (int)$_int;
            return true;
        }
    }

    /**
     * check string
     *
     * @param $str
     * @param $options
     * @return bool
     */

    function checkStr(&$str, $options = []) {
        if (is_null($str)) {
            return $str;
        }

        $str = trim($str);

        if (array_key_exists("validChars", $options)) {
            $t = "";

            for ($i = 0; $i < mb_strlen($str); $i++) {
                if (in_array(mb_substr($str, $i, 1), $options["validChars"])) {
                    $t .= mb_substr($str, $i, 1);
                }
            }

            $str = $t;
        }

        if (!in_array("dontStrip", $options)) {
            $str = preg_replace('/\s+/', ' ', $str);
        }

        if (!in_array("dontPurify", $options)) {
            $str = htmlPurifier($str);
        }

        if (array_key_exists("minLength", $options) && mb_strlen($str) < $options["minLength"]) {
            return false;
        }

        if (array_key_exists("maxLength", $options) && mb_strlen($str) > $options["maxLength"]) {
            return false;
        }

        if (array_key_exists("variants", $options) && !in_array($str, $options["variants"])) {
            return false;
        }

        return true;
    }

    /**
     * Generates a random password string of specified length.
     *
     * The password consists of lowercase and uppercase letters, and digits.
     *
     * @param int $length The desired length of the generated password. Default is 8.
     * @return string The randomly generated password.
     */

    function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

    /**
     * Returns a GUIDv4 string
     *
     * Uses the best cryptographically secure method
     * for all supported pltforms with fallback to an older,
     * less secure version.
     *
     * @param bool $trim
     * @return string
     */

    function GUIDv4($trim = true) {
        // copyright (c) by Dave Pearson (dave at pds-uk dot com)
        // https://www.php.net/manual/ru/function.com-create-guid.php#119168

        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
            substr($charid,  0,  8) . $hyphen .
            substr($charid,  8,  4) . $hyphen .
            substr($charid, 12,  4) . $hyphen .
            substr($charid, 16,  4) . $hyphen .
            substr($charid, 20, 12) .
            $rbrace;

        return $guidv4;
    }

    /**
     * Checks if a given filename is executable either as an absolute/relative path
     * or by searching for it in the system's PATH environment variable.
     *
     * @param string $filename The name or path of the file to check.
     * @return bool Returns true if the file is executable, false otherwise.
     */

    function isExecutablePathenv($filename) {
        if (is_executable($filename)) {
            return true;
        }
        if ($filename !== basename($filename)) {
            return false;
        }
        $paths = explode(PATH_SEPARATOR, getenv("PATH"));
        foreach ($paths as $path) {
            if (is_executable($path . DIRECTORY_SEPARATOR . $filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the maximum allowed file upload size based on PHP configuration.
     *
     * This function calculates the minimum value between 'post_max_size' and
     * 'upload_max_filesize' from the PHP ini settings, converting both to bytes.
     * The result represents the largest file size that can be uploaded via POST.
     *
     * @return int The maximum file upload size in bytes.
     */

    function getMaximumFileUploadSize() {
        return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
    }

    /**
     * Converts a PHP size string (e.g., "2M", "512K") to its equivalent value in bytes.
     *
     * @param string $sSize The size string to convert. Can be a plain integer or a string ending with a unit suffix ('K', 'M', 'G', 'T', 'P').
     *                      Supported suffixes are:
     *                      - K: Kilobytes
     *                      - M: Megabytes
     *                      - G: Gigabytes
     *                      - T: Terabytes
     *                      - P: Petabytes
     * @return int The size in bytes.
     */

    function convertPHPSizeToBytes($sSize) {
        $sSuffix = strtoupper(substr($sSize, -1));
        if (!in_array($sSuffix, [ 'P', 'T', 'G', 'M', 'K' ])) {
            return (int)$sSize;
        }
        $iValue = substr($sSize, 0, -1);
        switch ($sSuffix) {
            case 'P':
                $iValue *= 1024;
            case 'T':
                $iValue *= 1024;
            case 'G':
                $iValue *= 1024;
            case 'M':
                $iValue *= 1024;
            case 'K':
                $iValue *= 1024;
                break;
        }
        return (int)$iValue;
    }

    /**
     * Recursively converts an object to an associative array.
     *
     * If the input is neither an array nor an object, it returns the input as-is.
     * For objects, it casts them to arrays and recursively processes their properties.
     * For arrays, it recursively processes each element.
     *
     * @param mixed $data The input data to convert (can be an object, array, or scalar).
     * @return mixed The converted associative array, or the original scalar value.
     */

    function object_to_array($data) {
        if ((!is_array($data)) and (!is_object($data))) {
            return $data;
        }

        $result = [];

        $data = (array)$data;

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $value = (array)$value;
            }
            if (is_array($value)) {
                $result[$key] = object_to_array($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Parses a URL and returns its components with extended query and fragment parsing.
     *
     * If the URL contains a scheme (e.g., '://'), it uses PHP's parse_url to extract components.
     * Additionally, it parses the 'query' and 'fragment' parts into associative arrays:
     * - 'queryExt': An array of query parameters, with keys and values if available.
     * - 'fragmentExt': An array of fragment parameters, with keys and values if available.
     *
     * If the URL does not contain a scheme, it splits the string by ':' into up to three parts:
     * - 'scheme': The first part.
     * - 'host': The second part.
     * - 'port': The third part, or defaults to 514 if not provided.
     *
     * @param string $url The URL to parse.
     * @return array An associative array containing the parsed URL components and extended query/fragment arrays.
     */

    function parse_url_ext($url) {
        $url = trim($url);

        if (str_contains($url, '://')) {
            $url = parse_url($url);

            if (isset($url["query"])) {
                $queryExt = [];
                $q = explode("&", $url["query"]);
                foreach ($q as $e) {
                    $e = explode("=", $e);
                    if (@$e[1]) {
                        $queryExt[$e[0]] = $e[1];
                    } else {
                        $queryExt[] = $e[0];
                    }
                }
                $url["queryExt"] = $queryExt;
            }

            if (isset($url["fragment"])) {
                $fragmentExt = [];
                $q = explode("&", $url["fragment"]);
                foreach ($q as $e) {
                    $e = explode("=", $e);
                    if (@$e[1]) {
                        $fragmentExt[$e[0]] = $e[1];
                    } else {
                        $fragmentExt[] = $e[0];
                    }
                }
                $url["fragmentExt"] = $fragmentExt;
            }

            return $url;
        }

        $parts = explode(':', $url, 3);

        $urlParts['scheme'] = $parts[0];
        $urlParts['host'] = $parts[1];
        $urlParts['port'] = $parts[2] ?? 514;

        return $urlParts;
    }

    /**
     * Calculates the time-to-live (TTL) in seconds based on a given date/time string.
     *
     * Attempts to parse the provided `$val` as a date/time string and computes the TTL
     * as the difference between that time and the current time. If `$val` is invalid or
     * results in a negative TTL, it falls back to parsing `$default`. If both result in
     * negative TTLs, returns 0.
     *
     * @param string $val     The date/time string to parse for TTL.
     * @param string $default The fallback date/time string if `$val` is invalid or expired.
     * @return int            The TTL in seconds (non-negative).
     */

    function ttl($val, $default) {
        $ttl = @strtotime($val) - time();

        if ($ttl < 0) {
            $ttl = @strtotime($default) - time();
        }

        if ($ttl < 0) {
            $ttl = 0;
        }

        return $ttl;
    }

    /**
     * Calculates the expiration timestamp by adding a time-to-live (TTL) value to the current time.
     *
     * @param mixed $val The value representing the TTL duration.
     * @param mixed $default The default TTL value to use if $val is not set or invalid.
     * @return int The expiration timestamp (Unix time).
     */

    function expire($val, $default) {
        return time() + ttl($val, $default);
    }
