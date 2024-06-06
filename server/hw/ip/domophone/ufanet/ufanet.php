<?php

namespace hw\ip\domophone\ufanet;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Ufanet intercom.
 */
abstract class ufanet extends domophone
{

    use \hw\ip\common\ufanet\ufanet;

    public function addRfid(string $code, int $apartment = 0)
    {
        // TODO: Implement addRfid() method.
    }

    public function addRfids(array $rfids)
    {
        // TODO: Implement addRfids() method.
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        // TODO: Implement configureApartment() method.
    }

    public function configureEncoding()
    {
        // TODO: Implement configureEncoding() method.
    }

    public function configureGate(array $links = [])
    {
        // TODO: Implement configureGate() method.
    }

    public function configureMatrix(array $matrix)
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
        int    $stunPort = 3478
    )
    {
        // TODO: Implement configureSip() method.
    }

    public function configureUserAccount(string $password)
    {
        // TODO: Implement configureUserAccount() method.
    }

    public function deleteApartment(int $apartment = 0)
    {
        // TODO: Implement deleteApartment() method.
    }

    public function deleteRfid(string $code = '')
    {
        // TODO: Implement deleteRfid() method.
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // TODO: Implement getLineDiagnostics() method.
        return 0;
    }

    public function openLock(int $lockNumber = 0)
    {
        $lockNumber++;
        $this->apiCall("/api/v1/doors/$lockNumber/open", 'POST');
    }

    public function prepare()
    {
        $this->setNetwork();
        $this->setDisplayLocalization();
    }

    public function setAudioLevels(array $levels)
    {
        // TODO: Implement setAudioLevels() method.
    }

    public function setCallTimeout(int $timeout)
    {
        // TODO: Implement setCallTimeout() method.
    }

    public function setCmsLevels(array $levels)
    {
        // TODO: Implement setCmsLevels() method.
    }

    public function setCmsModel(string $model = '')
    {
        // TODO: Implement setCmsModel() method.
    }

    public function setConciergeNumber(int $sipNumber)
    {
        // TODO: Implement setConciergeNumber() method.
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1'
    )
    {
        // TODO: Implement setDtmfCodes() method.
    }

    public function setLanguage(string $language = 'ru')
    {
        // TODO: Implement setLanguage() method.
    }

    public function setPublicCode(int $code = 0)
    {
        // TODO: Implement setPublicCode() method.
    }

    public function setSosNumber(int $sipNumber)
    {
        // TODO: Implement setSosNumber() method.
    }

    public function setTalkTimeout(int $timeout)
    {
        // TODO: Implement setTalkTimeout() method.
    }

    public function setTickerText(string $text = '')
    {
        $this->apiCall('/api/v1/configuration', 'PUT', ['display' => ['labels' => [$text, '', '']]]);
    }

    public function setUnlockTime(int $time = 3)
    {
        // TODO: Implement setUnlockTime() method.
    }

    public function setUnlocked(bool $unlocked = true)
    {
        // TODO: Implement setUnlocked() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        // TODO: Implement transformDbConfig() method.
        return $dbConfig;
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getAudioLevels(): array
    {
        // TODO: Implement getAudioLevels() method.
        return [];
    }

    protected function getCmsLevels(): array
    {
        // TODO: Implement getCmsLevels() method.
        return [];
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

    protected function getGateConfig(): array
    {
        // TODO: Implement getGateConfig() method.
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
        // TODO: Implement getSipConfig() method.
        return [];
    }

    protected function getTickerText(): string
    {
        // TODO: Implement getTickerText() method.
        return '';
    }

    protected function getUnlocked(): bool
    {
        // TODO: Implement getUnlocked() method.
        return false;
    }

    protected function setDisplayLocalization()
    {
        $this->apiCall('/api/v1/configuration', 'PUT', [
            'display' => [
                'localization' => [
                    'ENTER_APARTMENT' => 'НАБЕРИТЕ НОМЕР КВАРТИРЫ',
                    'ENTER_PREFIX' => 'НАБЕРИТЕ ПРЕФИКС',
                    'CALL' => 'ИДЁТ ВЫЗОВ',
                    'CALL_GATE' => 'ЗАНЯТО',
                    'CONNECT' => 'ГОВОРИТЕ',
                    'OPEN' => 'ОТКРЫТО',
                    'FAIL_NO_CLIENT' => 'НЕВЕРНЫЙ НОМЕР КВАРТИРЫ',
                    'FAIL_NO_APP_AND_FLAT' => 'АБОНЕНТ НЕДОСТУПЕН',
                    'FAIL_LONG_SPEAK' => 'ВРЕМЯ ВЫШЛО',
                    'FAIL_NO_ANSWER' => 'НЕ ОТВЕЧАЕТ',
                    'FAIL_UNKNOWN' => 'ОШИБКА',
                    'FAIL_BLACK_LIST' => 'АБОНЕНТ ЗАБЛОКИРОВАН',
                    'FAIL_LINE_BUSY' => 'ЛИНИЯ ЗАНЯТА',
                    'KEY_DUPLICATE_ERROR' => 'ДУБЛИКАТ КЛЮЧА ЗАБЛОКИРОВАН',
                    'KEY_READ_ERROR' => 'ОШИБКА ЧТЕНИЯ КЛЮЧА',
                    'KEY_BROKEN_ERROR' => 'КЛЮЧ ВЫШЕЛ ИЗ СТРОЯ',
                    'KEY_UNSUPPORTED_ERROR' => 'КЛЮЧ НЕ ПОДДЕРЖИВАЕТСЯ'
                ],
            ],
        ]);
    }

    protected function setNetwork()
    {
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'RTSP.Block' => 'false',
            'Agent.Enable' => 'false',
        ]);
    }
}
