<?php

namespace hw\ip\domophone\basip;

use hw\Interface\{
    FreePassInterface,
    LanguageInterface,
};
use hw\ip\domophone\domophone;

/**
 * Abstract class representing an BASIP intercom.
 */
abstract class basip extends domophone implements FreePassInterface, LanguageInterface
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
        // Empty implementation
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
        $this->setConciergeNumber(9999); // Need to set a new concierge URL, the SIP server address may have changed
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
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
        // Empty implementation
        return 0;
    }

    public function isFreePassEnabled(): bool
    {
        return $this->apiCall('/v1/access/freeaccess')['enable'] ?? true;
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
        $this->apiCall('/v1/device/call/dial/timeout', 'POST', [
            'dial_timeout' => $timeout,
            'forwarding_timeout' => 25,
        ]);
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        ['server' => $sipServer, 'port' => $sipPort] = $this->getSipConfig();

        $this->apiCall('/v1/device/call/concierge', 'POST', [
            'number_enable' => true,
            'number_url' => "sip:$sipNumber@$sipServer:$sipPort",
        ]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/v1/access/general/lock/dtmf/1', 'POST', ['dtmf_code' => $code1]);
        $this->apiCall('/v1/access/general/lock/dtmf/2', 'POST', ['dtmf_code' => $code2]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $days = array_map(fn($day) => [
            'lock' => 'all',
            'enable' => true,
            'time_from' => 0,
            'time_to' => 86340,
            'day' => $day,
        ], ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']);

        $this->apiCall('/v1/access/freeaccess', 'POST', [
            'enable' => $enabled,
            'days' => $days,
        ]);
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
        $this->apiCall('/v1/access/general/unlock/input/code', 'POST', [
            'input_code_enable' => $code !== 0,
            'input_code_number' => $code,
        ]);
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->apiCall('/v1/device/call/talk/timeout', 'POST', ['talk_timeout' => $timeout]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('/v1/access/general/lock/timeout/1', 'POST', ['lock_timeout' => $time]);
        $this->apiCall('/v1/access/general/lock/timeout/2', 'POST', ['lock_timeout' => $time]);
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
        // Empty implementation
        return '';
    }

    protected function getDtmfConfig(): array
    {
        $code1 = $this->apiCall('/v1/access/general/lock/dtmf/1')['dtmf_code'];
        $code2 = $this->apiCall('/v1/access/general/lock/dtmf/2')['dtmf_code'];

        return [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getMatrix(): array
    {
        // Empty implementation
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
