<?php

namespace hw\ip\domophone\basip;

use hw\Interface\LanguageInterface;
use hw\ip\domophone\domophone;

/**
 * Abstract class representing an BASIP intercom.
 */
abstract class basip extends domophone implements LanguageInterface
{
    use \hw\ip\common\basip\basip;

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
        // TODO: Implement configureEncoding() method.
    }

    public function configureEventServer(string $url): void
    {
        // TODO: Implement configureEventServer() method.
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
        // TODO: Implement configureSip() method.
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

    public function setAdminPassword(string $password): void
    {
        // TODO: Implement setAdminPassword() method.
    }

    public function setAudioLevels(array $levels): void
    {
        // TODO: Implement setAudioLevels() method.
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

    public function syncData(): void
    {
        // TODO: Implement syncData() method.
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

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
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
}
