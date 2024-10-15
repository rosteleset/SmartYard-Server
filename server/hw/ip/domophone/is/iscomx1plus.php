<?php

namespace hw\ip\domophone\is;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is
{

    /**
     * Mapping of CMS models to their corresponding ID.
     *
     * @var array<string, int>
     */
    protected const CMS_MODEL_ID = [
        'BK-4' => 50,
        'BK-10' => 51,
        'BK-100' => 52,
        'COM-80U' => 61,
        'COM-100U' => 3, // other
        'COM-160U' => 63,
        'COM-220U' => 65,
        'FACTORIAL 8x8' => 0, // other
        'KKM-100S2' => 13,
        'KKM-105' => 11,
        'KKM-108' => 12,
        'KM100-7.2' => 30,
        'KMG-100' => 20,
    ];

    public function prepare()
    {
        parent::prepare();
        $this->setServiceCode();
    }

    public function setCmsLevels(array $levels)
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

    public function setTickerText(string $text = '')
    {
        $this->apiCall('/panelDisplay/settings', 'PUT', [
            'strDisplay' => $text !== '',
            'speed' => 300, // ms
            'imgStr' => $text,
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

    protected function getCmsModel(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->getCmsModelLegacy();
        }

        $typeId = $this->apiCall('/v1/switch/1')['type'] ?? null; // TODO: caching?
        $cmsModel = array_search($typeId, self::CMS_MODEL_ID);

        return $typeId !== null && $cmsModel !== false ? $cmsModel : '';
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
    protected function setServiceCode(int $code = 0)
    {
        $enabled = $code !== 0;
        $pass = $enabled ? $code : 123456;

        $this->apiCall('/serviceCode/settings', 'PUT', [
            'enabled' => $enabled,
            'pass' => $pass,
        ]);
    }
}
