<?php

namespace Selpol\Service;

class FrsService
{
    public function request(string $method, string $url, mixed $payload = null, ?string $contentType = null, ?string $token = null): string|bool
    {
        $curl = curl_init();

        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($payload !== null) {
                    if ($contentType !== null)
                        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: ' . $contentType]);
                    else
                        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
                }
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                if ($payload !== null)
                    $url = sprintf("%s?%s", $url, http_build_query($payload));
        }

        if ($token !== null)
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: ' . $token]);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}