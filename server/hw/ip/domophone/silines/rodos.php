<?php

namespace hw\ip\domophone\silines;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a RODOS IP relay.
 */
class rodos extends domophone
{
    public function addRfid(string $code, int $apartment = 0): void
    {
        // Empty implementation
    }

    public function addRfids(array $rfids): void
    {
        // Empty implementation
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        // Empty implementation
    }

    public function configureEncoding(): void
    {
        // Empty implementation
    }

    public function configureEventServer(string $url): void
    {
        // Empty implementation
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
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
        // Empty implementation
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // Empty implementation
    }

    public function deleteRfid(string $code = ''): void
    {
        // Empty implementation
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        return 0;
    }

    public function getSysinfo(): array
    {
        $r = explode("\n", $this->apiCall('protect/config.htm')['content']);

        $mac = '';
        $softwareVersion = '';

        foreach ($r as $l) {
            if (str_contains($l, 'MAC Address:')) {
                $mac = explode('"', explode('value=', $l)[1])[1];
            }
            if (str_contains($l, 'RODOS-') && str_contains($l, 'footer')) {
                $softwareVersion = explode(' ', explode('RODOS-', $l)[1])[1];
            }
        }

        return ['DeviceID' => $mac, 'SoftwareVersion' => $softwareVersion];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $address = parse_url($this->url, PHP_URL_HOST);
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $msg = "admin $this->password k1=2";
        socket_sendto($socket, $msg, strlen($msg), 0, $address, 8283);
    }

    public function reboot(): void
    {
        // Empty implementation
    }

    public function reset(): void
    {
        // Empty implementation
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall('protect/config.htm', "dhcp=1&logn=admin&pass=$password&hpr=80&upr=8283&b0=Save+%26+reboot");
    }

    public function setAudioLevels(array $levels): void
    {
        // Empty implementation
    }

    public function setCallTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1'): void
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3): void
    {
        // Empty implementation
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['dtmf'] = [
            'code1' => '1',
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];

        $dbConfig['sip'] = [
            'server' => '',
            'port' => 5060,
            'login' => '',
            'password' => '',
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];

        $dbConfig['ntp'] = [
            'server' => '',
            'port' => 123,
            'timezone' => '',
        ];

        return $dbConfig;
    }

    protected function apiCall(string $uri, string $data = '', string $cookiesIn = '', bool $follow = false)
    {
        $url = $this->url . '/' . $uri;

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_ENCODING => '',
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIE => $cookiesIn,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "$this->login:$this->password",
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded']);
        }

        $rough_content = curl_exec($ch);
        $header['errno'] = curl_errno($ch);
        $header['errmsg'] = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header_content = substr($rough_content, 0, $header['header_size']);
        $header['content'] = trim(str_replace($header_content, '', $rough_content));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header_content, $matches);
        $header['cookies'] = implode('; ', $matches['cookie']);

        $header['headers'] = $header_content;

        return $header;
    }

    protected function getApartments(): array
    {
        return [];
    }

    protected function getAudioLevels(): array
    {
        return [];
    }

    protected function getCmsModel(): string
    {
        return '';
    }

    protected function getDtmfConfig(): array
    {
        return [
            'code1' => '1',
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getEventServer(): string
    {
        return '';
    }

    protected function getMatrix(): array
    {
        return [];
    }

    protected function getNtpConfig(): array
    {
        return [
            'server' => '',
            'port' => 123,
            'timezone' => '',
        ];
    }

    protected function getRfids(): array
    {
        return [];
    }

    protected function getSipConfig(): array
    {
        return [
            'server' => '',
            'port' => 5060,
            'login' => '',
            'password' => '',
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
    }
}
