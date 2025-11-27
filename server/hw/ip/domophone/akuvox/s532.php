<?php

namespace hw\ip\domophone\akuvox;

use hw\Interface\DisplayTextInterface;

/**
 * Represents an Akuvox S532 intercom.
 */
class s532 extends akuvox implements DisplayTextInterface
{
    protected static function getMaxUsers(): int
    {
        return 4000; // TODO: check
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478,
    ): void
    {
        // With STUN enabled, the device doesn't register with the SIP server (it shows "Failed" in the web interface)
        $encodedPassword = base64_encode($password);
        parent::configureSip($login, $encodedPassword, $server, $port, $stunEnabled, $stunServer, $stunPort);
    }

    public function getDisplayText(): array
    {
        $text = $this->getConfigParams(['Config.DoorSetting.CUSTOMIZED.Text'])[0] ?? null;
        return $text ? [$text] : [];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setHttpsEnabled(false);
    }

    public function setAdminPassword(string $password): void
    {
        $this->setWebPassword($password);
        $this->setRtspPassword($password);
        $this->setApiPassword($password);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.Theme' => '1',
            'Config.DoorSetting.CUSTOMIZED.Text' => $textLines[0] ?? '',
        ]);
    }

    protected function setApiPassword(string $password): void
    {
        $this->setConfigParams(['Config.DoorSetting.APIFCGI.Password' => base64_encode($password)]);
    }

    protected function setRtspPassword(string $password): void
    {
        $this->setConfigParams(['Config.DoorSetting.RTSP.Password' => base64_encode($password)]);
    }

    protected function setWebPassword(string $password): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'security_basic',
            'action' => 'set',
            'data' => [
                'firstLogin' => '1',
                'userName' => base64_encode($this->login),
                'newPassword' => base64_encode($password),
            ],
        ]);
    }
}
