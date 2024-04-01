<?php

namespace hw\ip\domophone\is;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is
{

    use \hw\ip\common\is\iscomx1plus;

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        parent::configureApartment($apartment, $code, $sipNumbers, $cmsEnabled, $cmsLevels);

        // This crutch is here because at the moment SIP numbers aren't set when creating a new apartment
        $this->apiCall("/panelCode/$apartment", 'PUT', [
            'sipAccounts' => array_map('strval', $sipNumbers)
        ]);
    }

    public function configureEncoding()
    {
        $this->apiCall('/camera/audio', 'PUT', [
            'aac_enable' => false,
            'format' => 'PCMA',
        ]);

        $this->apiCall('/camera/codec', 'PUT', [
            'Channels' => [
                [
                    'Channel' => 0,
                    'Type' => 'H264',
                    'Profile' => 0,
                    'ByFrame' => true,
                    'Width' => 1280,
                    'Height' => 720,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'VBR',
                    'IFrameInterval' => 15,
                    'MaxBitrate' => 1024,
                ],
                [
                    'Channel' => 2,
                    'Type' => 'H264',
                    'Profile' => 0,
                    'ByFrame' => true,
                    'Width' => 640,
                    'Height' => 480,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'VBR',
                    'IFrameInterval' => 30,
                    'MaxBitrate' => 348,
                ],
            ],
        ]);
    }

    public function prepare()
    {
        parent::prepare();
        $this->configureEncoding();
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
