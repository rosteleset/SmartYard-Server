<?php

namespace hw\ip\domophone\akuvox;

use hw\Interface\DisplayTextInterface;
use hw\ip\domophone\akuvox\Enums\AnalogType;

/**
 * Represents an Akuvox S532 intercom.
 */
class s532 extends akuvox implements DisplayTextInterface
{
    /**
     * @var array<string, AnalogType> Mapping of CMS model codes from the DB to their corresponding AnalogType enums.
     */
    protected const CMS_MODEL_MAP = [
        '' => AnalogType::None,
        'BK-100' => AnalogType::Vizit,
        'BK-400' => AnalogType::Vizit,
        'COM-25U' => AnalogType::Metakom,
        'COM-100U' => AnalogType::Metakom,
        'COM-220U' => AnalogType::Metakom,
        'DIGITAL' => AnalogType::Laskomex,
        'KM20-1' => AnalogType::Eltis,
        'KM100-7.1' => AnalogType::Eltis,
        'KM100-7.2' => AnalogType::Eltis,
        'KM100-7.3' => AnalogType::Eltis,
        'KM100-7.5' => AnalogType::Eltis,
        'KMG-100' => AnalogType::Cyfral,
    ];

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

    public function setCmsModel(string $model = ''): void
    {
        $this->setConfigParams(['Config.DoorSetting.ANALOG.Type' => self::CMS_MODEL_MAP[$model]->value]);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.Theme' => '1',
            'Config.DoorSetting.CUSTOMIZED.Text' => $textLines[0] ?? '',
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['cmsModel'] = self::CMS_MODEL_MAP[$dbConfig['cmsModel']]->value;
        return $dbConfig;
    }

    protected function getCmsModel(): string
    {
        return $this->getConfigParams(['Config.DoorSetting.ANALOG.Type'])[0] ?? '';
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
