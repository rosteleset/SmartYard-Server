<?php

namespace hw\ip\common\basip\Models;

/**
 * Trait providing common functionality related to BasIP AA-12FB devices.
 */
trait AA12FB
{
    protected function setDevicePassword(string $password): void
    {
        $this->client->request('/v1/security/password/admin?', 'POST', [
            'old_password' => $this->defaultPassword,
            'new_password' => $password,
        ]);
    }
}
