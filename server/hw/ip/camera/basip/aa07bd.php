<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;

/**
 * Class representing a BASIP camera.
 */
class aa07bd extends camera
{
    use \hw\ip\common\basip\aa07bd {
        transformDbConfig as protected commonTransformDbConfig;
    }

    /**
     * @var string stored bearer token used for authentication
     */
    public string $token;


    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: too slow (~2 sec)
        return $this->apiCall('/v1/photo/file', 'GET', [], 5);
    }

    public function setOsdText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);
        $dbConfig['osdText'] = '';
        $dbConfig['motionDetection'] = [];
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function getOsdText(): string
    {
        // Empty implementation
        return '';
    }

    protected function initConnection(): void
    {
        $loginResult = $this->login();
        if ($loginResult && array_key_exists("token", $loginResult)) {
            $this->token = $loginResult["token"];
        } else {
            throw new Exception("Login failed");
        }
    }

    protected function login(): array|string
    {
        $req = $this->url . $this->apiPrefix . "/v1/login?username=" . $this->login . "&password=" . md5($this->password);

        $ch = curl_init($req);

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }

    protected function apiCall(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array|string
    {
        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_BEARER,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_XOAUTH2_BEARER => $this->token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }
}
