<?php

namespace hw\ip\domophone\basip\Models;

use hw\ip\common\basip\HttpClient\BearerHttpClient;
use hw\ip\domophone\basip\Basip;

/**
 * Represents a BasIP CR-02BD network reader.
 */
class CR02BD extends Basip
{
    use \hw\ip\common\basip\Models\AA07BD {
        transformDbConfig as protected aa07bdTransformDbConfig;
    }

    protected const HTTP_CLIENT_CLASS = BearerHttpClient::class;

    protected static function getIdentifierValidPayload(): array
    {
        return [
            'passes' => [
                'is_permanent' => true,
                'max_passes' => 0,
            ],
            'time' => [
                'from' => null,
                'is_permanent' => true,
                'to' => null,
            ],
        ];
    }

    public function prepare(): void
    {
        $this->setUnlockTime(5);
        $this->setPublicCode();
        $this->setLanguage('ru');
        $this->configureInternalReader();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->aa07bdTransformDbConfig(parent::transformDbConfig($dbConfig));

        $dbConfig['dtmf'] = $this->getDtmfConfig();
        $dbConfig['sip'] = $this->getSipConfig();
        $dbConfig['apartments'] = [];

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        return [];
    }

    protected function getDtmfConfig(): array
    {
        // Empty implementation
        return [
            'code1' => '1',
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getSipConfig(): array
    {
        // Empty implementation
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

    protected function setRtspPassword(string $password): void
    {
        // Empty implementation
    }
}
