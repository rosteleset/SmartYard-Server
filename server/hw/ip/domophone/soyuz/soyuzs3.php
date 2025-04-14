<?php

namespace hw\ip\domophone\soyuz;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class soyuzs3 extends soyuz
{

    public function prepare(): void
    {
        parent::prepare();
        $this->setServiceCode();
    }

    public function setCmsLevels(array $levels): void
    {
    }

    public function setTickerText(string $text = ''): void
    {
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
    }
}
