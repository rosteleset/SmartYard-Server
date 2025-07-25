<?php

namespace hw\ip\domophone\is;

use hw\Interface\DisplayTextInterface;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is implements DisplayTextInterface
{
    public function getDisplayText(): array
    {
        $text = $this->apiCall('/panelDisplay/settings')['imgStr'] ?? '';
        return $text === '' ? [] : [$text];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setServiceCode();
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) === 2) {
            $this->apiCall('/levels', 'PUT', [
                'resistances' => [
                    'quiescent' => $levels[0],
                    'answer' => $levels[1],
                ],
            ]);
        }
    }

    public function setDisplayText(array $textLines): void
    {
        $this->apiCall('/panelDisplay/settings', 'PUT', [
            'strDisplay' => isset($textLines[0]) && $textLines[0] !== '',
            'speed' => 300, // ms
            'imgStr' => $textLines[0] ?? '',
        ]);
    }

    protected function getApartmentCmsParams(?int $answer, ?int $quiescent): array
    {
        return [$quiescent, $answer];
    }

    protected function getApartmentResistanceParams(array $cmsLevels): ?array
    {
        if (count($cmsLevels) === 2) {
            return [
                'quiescent' => (float)$cmsLevels[0],
                'answer' => (float)$cmsLevels[1],
            ];
        }

        return null;
    }

    /**
     * Set service code.
     * This code is used to access the service menu from the front panel of the device.
     *
     * @param int $code (Optional) The service code to be set. If set to 0, the service code will be disabled.
     * Otherwise, it will be enabled with the provided code. 0 by default.
     *
     * @return void
     */
    protected function setServiceCode(int $code = 0): void
    {
        $enabled = $code !== 0;
        $pass = $enabled ? $code : 123456;

        $this->apiCall('/serviceCode/settings', 'PUT', [
            'enabled' => $enabled,
            'pass' => $pass,
        ]);
    }
}
