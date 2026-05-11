<?php

namespace hw\ip\common\basip\Models;

/**
 * Trait providing common functionality related to BasIP AA-07FB devices.
 */
trait AA07FB
{
    protected function setDevicePassword(string $password): void
    {
        $params = [
            'oldPassword' => $this->defaultPassword,
            'newPassword' => $password,
        ];

        $this->client->request('/v1/security/password/admin?' . http_build_query($params), 'POST');
    }
}
