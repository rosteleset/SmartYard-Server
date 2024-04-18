<?php

/**
 * Test wrapper for API request with error handling
 *
 * @param string $method API method name
 * @param string $url base API URL
 * @param object|false $payload API payload
 * @param string|false $contentType API content type
 * @param string|false $token Bearer Token
 * @param int| $timeout response timeout default 10 seconds
 * @return false|object|string API response or error object
 * @throws Exception
 */
function apiExec(string $method, string $url, $payload = false, $contentType = false, $token = false, $timeout = 10) {
    $curl = curl_init();
    $headers = [];

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($payload) {
                $contentType = $contentType ?: 'application/json';
                $headers[] = 'Content-Type: ' . $contentType;
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($payload) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
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
    if ($token !== false) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($curl);

    // Check for cURL errors
    if ($response === false) {
        $error = new stdClass();

        // Check host not resolved
        if (curl_errno($curl) === CURLE_COULDNT_RESOLVE_HOST) {
            $error->message = "host could not be resolved.";
            $error->code = CURLE_COULDNT_RESOLVE_HOST;
            curl_close($curl);
            return $error;
        }

        // Check connection timeout
        if (curl_errno($curl) === CURLE_OPERATION_TIMEOUTED) {
            $error->message = "connection timeout.";
            $error->code = CURLE_OPERATION_TIMEOUTED;
            curl_close($curl);
            return $error;
        }

        // Otherwise, return regular cURL error
        $error = new stdClass();
        $error->message = curl_error($curl);
        $error->code = curl_errno($curl);
        curl_close($curl);
        return $error;
    }

    // Check if no response received
    if (empty($response)) {
        $error = new stdClass();
        $error->message = "no response received from the server.";
        $error->code = 0;
        curl_close($curl);
        return $error;
    }

    // Get HTTP status code
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Check HTTP status code
    if ($httpCode >= 400) {
        $error = new stdClass();
        $error->message = "HTTP error: {$httpCode}";
        $error->code = $httpCode;
        return $error;
    }

    return $response;
}
