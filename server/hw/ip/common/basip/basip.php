<?php

namespace hw\ip\common\basip;

/**
 * Trait providing common functionality related to BASIP devices.
 */
trait basip
{
    public function getSysinfo(): array
    {
        $info = $this->apiCall('/info', 'GET', [], 3);

        if (
            empty($info['device_serial_number']) ||
            empty($info['device_model']) ||
            empty($info['mcu_version']) ||
            empty($info['firmware_version'])
        ) {
            return [];
        }

        return [
            'DeviceID' => $info['device_serial_number'],
            'DeviceModel' => $info['device_model'],
            'HardwareVersion' => $info['mcu_version'],
            'SoftwareVersion' => $info['firmware_version'],
        ];
    }

    public function reboot(): void
    {
        $this->apiCall('/v1/system/reboot/run');
    }

    public function reset(): void
    {
        $this->apiCall('/v1/system/settings/default', 'POST');
    }

    protected function apiCall(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array
    {
        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => "$this->login:$this->password",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? [];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
        $this->apiPrefix = '/api';
    }
}
