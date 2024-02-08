<?php

// TODO: very rude
function parse_url_ext($url)
{
    $url = trim($url);

    if (str_contains($url, '://'))
    {  
        $url = parse_url($url);

        if ($url["query"]) {
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

        if ($url["fragment"]) {
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
