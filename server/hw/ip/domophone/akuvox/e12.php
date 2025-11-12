<?php

namespace hw\ip\domophone\akuvox;

/**
 * Represents an Akuvox E12S/E12W intercom.
 */
class e12 extends akuvox
{
    protected static function getMaxUsers(): int
    {
        return 1000;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->bindInputsToRelays(inputB: 1); // Map all inputs to Relay A (only one relay available)
        $this->configureAudio();
        $this->configureLed(false);
        $this->setExternalReader(openRelayA: true); // Link external reader to Relay A (only one relay available)
    }

    /**
     * Configure general audio settings.
     *
     * @return void
     */
    protected function configureAudio(): void
    {
        $this->setConfigParams([
            'Config.Settings.HANDFREE.VolumeLevel' => '2', // Increase volume level
        ]);
    }

    /**
     * Configure LED fill light.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @param int $minThreshold (Optional) Minimum illumination threshold. Default is 1500.
     * @param int $maxThreshold (Optional) Maximum illumination threshold. Default is 1600.
     * @return void
     */
    protected function configureLed(bool $enabled = true, int $minThreshold = 1500, int $maxThreshold = 1600): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.LedType' => $enabled ? '0' : '2',
            'Config.DoorSetting.GENERAL.MinPhotoresistors' => "$minThreshold",
            'Config.DoorSetting.GENERAL.MaxPhotoresistors' => "$maxThreshold",
        ]);
    }
}
