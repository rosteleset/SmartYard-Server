<?php

/**
 * Test wrapper for API request
 * @param string $method API method name
 * @param string $url base API URL
 * @param object|false $payload API payload
 * @param string|false $contentType API content type
 * @param string|false $token Bearer Token
 * @return false|object
 */
function apiExec(string $method, string $url, $payload = false, $contentType = false, $token = false) {
    $curl = curl_init();

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($payload) {
                $contentType = $contentType ?: 'application/json';
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: ' . $contentType]);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($payload) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            }
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        default:
            if ($payload) {
                $url = sprintf("%s?%s", $url, http_build_query($payload));
            }
    }

    // Add Bearer Token header in the request
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}
