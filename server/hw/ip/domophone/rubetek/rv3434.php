<?php

namespace hw\ip\domophone\rubetek;

use hw\Interface\BleInterface;

/**
 * Represents a Rubetek RV-3434 intercom.
 */
class rv3434 extends rubetek implements BleInterface
{
    public function getDisplayTextLinesCount(): int
    {
        return 3;
    }

    public function isBleEnabled(): bool
    {
        return $this->apiCall('/settings/ble')['enable'];
    }

    public function setBleEnabled(bool $enabled): void
    {
        $settings = $this->apiCall('/settings/ble');

        $settings['enable'] = $enabled;
        $settings['mark']['button']['enable'] = false;
        $settings['mark']['distance']['enable'] = false;
        $settings['app']['button']['enable'] = true;
        $settings['app']['distance']['enable'] = true;
        $settings['triggered_rssi']['distance'] = -58;
        $settings['triggered_rssi']['button'] = -116;

        $this->apiCall('/settings/ble', 'PATCH', $settings);
    }
}
