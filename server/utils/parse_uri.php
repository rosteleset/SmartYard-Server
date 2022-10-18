<?php

    function parseURI($uri) {
        $parts = explode(':', $uri);

        $scheme = explode('.', $parts[0]);
        $uri_parts['scheme'] = $scheme[0];
        if (isset($scheme[1])) {
            $uri_parts['transport'] = $scheme[1];
        }

        if (isset($parts[1])) {
            $uri_parts['host'] = $parts[1];
        }

        if (isset($parts[2])) {
            $uri_parts['port'] = $parts[2];
        }

        return $uri_parts;
    }
