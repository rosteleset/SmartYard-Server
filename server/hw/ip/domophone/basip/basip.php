<?php

namespace hw\ip\domophone\basip;

use hw\Interface\LanguageInterface;
use hw\ip\domophone\domophone;

/**
 * Abstract class representing an BASIP intercom.
 */
abstract class basip extends domophone implements LanguageInterface
{
    use \hw\ip\common\basip\basip {
        transformDbConfig as protected commonTransformDbConfig;
    }

    protected const DISABLED_STUN_ADDRESS = '127.0.0.1';

    public function addRfid(string $code, int $apartment = 0): void
    {
        // TODO: Implement addRfid() method.
    }

    public function addRfids(array $rfids): void
    {
        // TODO: Implement addRfids() method.
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        // TODO: Implement configureApartment() method.
    }

    public function configureEncoding(): void
    {
        $this->apiCall('/v1/device/settings/video', 'POST', [
            'fps' => 25, // No way to change this via WEB, so let it be the default value from the POST payload
            'video_resolution' => '1280x720',
        ]);

        $this->apiCall('/v1/device/settings/payload', 'POST', ['payload_codec_h264' => 102]);
    }

    public function configureMatrix(array $matrix): void
    {
        // TODO: Implement configureMatrix() method.
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
        $this->apiCall('/v1/device/sip/settings', 'POST', [
            'outbound' => '',
            'password' => $password,
            'proxy' => "sip:$server:$port",
            'realm' => "$server:$port",
            'registration_interval' => 900, // Max allowed value
            'transport' => 'udp',
            'user' => $login,
            'user_id' => $password, // Use this field to store password. The password field always says "WebPass".
            'stun' => [
                'ip' => $stunEnabled ? $stunServer : self::DISABLED_STUN_ADDRESS,
                'port' => $stunPort,
            ],
        ]);

        $this->apiCall('/v1/device/sip/enable', 'POST', ['sip_enable' => $login !== '']);
    }

    public function configureUserAccount(string $password): void
    {
        // TODO: Implement configureUserAccount() method.
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // TODO: Implement deleteApartment() method.
    }

    public function deleteRfid(string $code = ''): void
    {
        // TODO: Implement deleteRfid() method.
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // TODO: Implement getLineDiagnostics() method.
        return 0;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/v1/access/general/lock/open/remote/accepted/' . $lockNumber + 1);
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 2) {
            $this->apiCall('/v1/device/settings/volume', 'POST', ['volume_level' => $levels[0]]);
            $this->apiCall('/v1/device/settings/mic', 'POST', ['mic_gain_level' => $levels[1]]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        // TODO: Implement setCallTimeout() method.
    }

    public function setCmsModel(string $model = ''): void
    {
        // TODO: Implement setCmsModel() method.
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // TODO: Implement setConciergeNumber() method.
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        // TODO: Implement setDtmfCodes() method.
    }

    public function setLanguage(string $language): void
    {
        $lang = match ($language) {
            'es' => 'Spanish',
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'pl' => 'Polish',
            'nl' => 'Dutch',
            'tr' => 'Turkish',
            'fr' => 'French',
            'da' => 'Danish',
            'pt' => 'Portuguese',
            'de' => 'Deutsch',
            default => 'English',
        };

        $this->apiCall("/v1/device/language?language=$lang", 'POST');
    }

    public function setPublicCode(int $code = 0): void
    {
        // TODO: Implement setPublicCode() method.
    }

    public function setSosNumber(int $sipNumber): void
    {
        // TODO: Implement setSosNumber() method.
    }

    public function setTalkTimeout(int $timeout): void
    {
        // TODO: Implement setTalkTimeout() method.
    }

    public function setUnlockTime(int $time = 3): void
    {
        // TODO: Implement setUnlockTime() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        if ($dbConfig['sip']['stunEnabled'] === false) {
            $dbConfig['sip']['stunServer'] = self::DISABLED_STUN_ADDRESS;
        }

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getAudioLevels(): array
    {
        $volumeLevel = $this->apiCall('/v1/device/settings/volume')['volume_level'];
        $micLevel = $this->apiCall('/v1/device/settings/mic')['mic_gain_level'];

        return [$volumeLevel, $micLevel];
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
    }

    protected function getDtmfConfig(): array
    {
        // TODO: Implement getDtmfConfig() method.
        return [];
    }

    protected function getMatrix(): array
    {
        // TODO: Implement getMatrix() method.
        return [];
    }

    protected function getRfids(): array
    {
        // TODO: Implement getRfids() method.
        return [];
    }

    protected function getSipConfig(): array
    {
        $sipSettings = $this->apiCall('/v1/device/sip/settings');

        $realmParts = explode(':', $sipSettings['realm'], 2);

        return [
            'server' => $realmParts[0],
            'port' => $realmParts[1] ?? 5060,
            'login' => $sipSettings['user'],
            'password' => $sipSettings['user_id'], // See the comment in the configureSip() method
            'stunEnabled' => $sipSettings['stun']['ip'] !== self::DISABLED_STUN_ADDRESS,
            'stunServer' => $sipSettings['stun']['ip'],
            'stunPort' => $sipSettings['stun']['port'],
        ];
    }
}
