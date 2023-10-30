<?php

// TODO: very rude
function parse_url_ext($url)
{
    $url = trim($url);

    if (str_contains($url, '://')) {
        return parse_url($url);
    }

    $parts = explode(':', $url, 3);

    $urlParts['scheme'] = $parts[0];
    $urlParts['host'] = $parts[1];
    $urlParts['port'] = $parts[2] ?? 514;

    return $urlParts;
}
