<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class representing a Secret Mini intercom.
 */
class secretMini extends ufanet
{

    /**
     * URL used to automatically update the intercom firmware.
     */
    protected const AUTOUPDATE_ADDRESS = 'https://dom.ufanet.ru/icupd/OPENIPC';

    public function configureGate(array $links = []): void
    {
        // Empty implementation
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setAutoupdateEnabled(false);
    }

    public function setCmsModel(string $model = '')
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTickerText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = parent::transformDbConfig($dbConfig);

        $dbConfig['tickerText'] = '';

        return $dbConfig;
    }

    protected function getCmsModel(): string
    {
        return '';
    }

    protected function getTickerText(): string
    {
        return '';
    }

    /**
     * Enables or disables the autoupdate feature.
     *
     * @param bool $enabled (Optional) True to enable autoupdate, false otherwise. Defaults to true.
     * @return void
     */
    protected function setAutoupdateEnabled(bool $enabled = true): void
    {
        $this->apiCall('/cgi-bin/firmware.cgi', 'POST', [
            'action' => 'autoupdate',
            'autoupdate_enable' => $enabled ? 'true' : 'false',
            'autoupdate_address' => self::AUTOUPDATE_ADDRESS,
        ], 0, true);
    }
}
