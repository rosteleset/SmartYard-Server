<?php

    /**
     * Polyfill for apache_request_headers() function.
     *
     * Provides a fallback implementation of apache_request_headers() for environments
     * where the Apache module is not available. Extracts HTTP headers from the $_SERVER
     * superglobal and formats them as an associative array with proper HTTP header casing.
     *
     * The function converts $_SERVER keys prefixed with 'HTTP_' into standard HTTP header
     * format by removing the prefix, converting underscores to hyphens, and applying
     * title case to each segment.
     *
     * @return array An associative array of HTTP headers where keys are header names
     *               (e.g., 'Content-Type', 'Authorization') and values are the header values.
     *
     * @example
     * $headers = apache_request_headers();
     * echo $headers['Content-Type']; // Output: 'application/json'
     */

    if (!function_exists('apache_request_headers')) {
        function apache_request_headers () {
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach ($_SERVER as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val) {
                            $rx_matches[$ak_key] = ucfirst($ak_val);
                        }
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            return ($arh );
        }
    }

    /**
     * Polyfill for array_key_first — returns the first key of an array.
     *
     * Provides a fallback implementation for PHP's native array_key_first (available since PHP 7.3).
     * Iterates the array and returns the first encountered key. If the array is empty, returns NULL.
     *
     * @param array $arr The array to retrieve the first key from.
     * @return int|string|null The first key (int or string) if the array is not empty, or NULL if it is empty.
     */

    if (!function_exists('array_key_first')) {
        function array_key_first(array $arr) {
            foreach ($arr as $key => $unused) {
                return $key;
            }
            return NULL;
        }
    }

    /**
     * Determine whether the given array is a list (sequential integer keys starting at 0).
     *
     * An empty array is considered a list and returns true. For non-empty arrays, this returns
     * true only if the array's keys exactly match the sequence 0..(count($arr) - 1), i.e. there
     * are no gaps and all keys are consecutive integers starting from zero.
     *
     * @param array $arr The array to inspect.
     * @return bool True if $arr is a list (sequential integer indices starting at 0), false otherwise.
     *
     * @see https://www.php.net/manual/en/function.array-is-list.php (PHP 8.1+)
     */

    if (!function_exists('array_is_list')) {
        function array_is_list($arr) {
            if ($arr === []) {
                return true;
            }
            return array_keys($arr) === range(0, count($arr) - 1);
        }
    }

    /**
     * Determines the MIME content type of a file based on its extension.
     *
     * This function provides a mapping of common file extensions to their respective MIME types.
     * If the extension is not recognized, it defaults to 'application/octet-stream'.
     *
     * @param string $filename The name of the file whose MIME type is to be determined.
     * @return string The MIME type corresponding to the file extension.
     */

    if (!function_exists('mime_content_type')) {
        function mime_content_type($filename) {
            $mime_types = [

                // common
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',

                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',

                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',

                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',

                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',

                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',

                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            ];

            $f_ = explode('.', $filename);
            $ext = strtolower(array_pop($f_));
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } else {
                return 'application/octet-stream';
            }
        }
    }
