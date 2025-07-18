<?php

namespace hw\ip\domophone\beward;

use hw\Interfaces\{
    CmsLevelsInterface,
    DisplayTextInterface,
    HousePrefixInterface,
    LanguageInterface,
};
use hw\ValueObjects\HousePrefix;

/**
 * Class representing a Beward DKS series domophone.
 */
class dks extends beward implements CmsLevelsInterface, DisplayTextInterface, HousePrefixInterface, LanguageInterface
{
    public function getCmsLevels(): array
    {
        $params = $this->parseParamValue($this->apiCall('cgi-bin/intercom_cgi', ['action' => 'get']));
        return [
            (int)$params['HandsetUpLevel'],
            (int)$params['DoorOpenLevel'],
        ];
    }

    public function getDisplayText(): array
    {
        $text = $this->getParams('display_cgi')['TickerText'] ?? '';
        return $text === '' ? [] : [$text];
    }

    public function getDisplayTextLinesCount(): int
    {
        /*
         * In fact, DKS has the ability to display either one long line or five short ones with a given rotation
         * interval, but they only hold 8 characters, so I don't see any point in using them.
         */
        return 1;
    }

    public function getHousePrefixes(): array
    {
        $gateSettings = $this->getParams('gate_cgi');
        $prefixes = [];

        if ($gateSettings['Enable'] === 'off') {
            return $prefixes;
        }

        for ($i = 1; $i <= $gateSettings['EntranceCount']; $i++) {
            $prefixes[] = new HousePrefix(
                $gateSettings["Prefix$i"],
                $gateSettings["Address$i"],
                $gateSettings["BegNumber$i"],
                $gateSettings["EndNumber$i"],
            );
        }

        return $prefixes;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->enableUpnp(false);
        $this->enableServiceCodes(false);
        $this->setAlarm('SOSCallActive', 'on');
        $this->setIntercom('AlertNoUSBDisk', 'off');
        $this->setIntercom('ExtReaderNotify', 'off');
        $this->setIntercom('IndividualLevels', 'on');
        $this->setIntercom('SosDelay', 0);
        $this->setHousePrefixes([]); // Set "Mode 2" for incoming calls to work correctly
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->setIntercom('CallTimeout', $timeout);
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) == 2) {
            $this->setIntercom('HandsetUpLevel', $levels[0]);
            $this->setIntercom('DoorOpenLevel', $levels[1]);
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'levels',
                'HandsetUpLevel' => $levels[0],
                'DoorOpenLevel' => $levels[1],
            ]);
        }
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setIntercom('ConciergeApartment', $sipNumber);
        $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->apiCall('cgi-bin/display_cgi', [
            'action' => 'set',
            'TickerEnable' => isset($textLines[0]) ? 'on' : 'off',
            'TickerText' => $textLines[0] ?? '',
            'TickerTimeout' => 125,
            'LineEnable1' => 'off',
            'LineEnable2' => 'off',
            'LineEnable3' => 'off',
            'LineEnable4' => 'off',
            'LineEnable5' => 'off',
        ]);
    }

    public function setHousePrefixes(array $prefixes): void
    {
        $params = [
            'action' => 'set',
            'Mode' => 1,
            'Enable' => empty($prefixes) ? 'off' : 'on',
            'MainDoor' => 'on',
            'AltDoor' => 'on',
            'PowerRely' => 'on',
        ];

        if (!empty($prefixes)) {
            $params['EntranceCount'] = count($prefixes);

            foreach ($prefixes as $i => $prefix) {
                $index = $i + 1;
                $params['Address' . $index] = $prefix->address;
                $params['Prefix' . $index] = $prefix->prefix;
                $params['BegNumber' . $index] = $prefix->firstFlat;
                $params['EndNumber' . $index] = $prefix->lastFlat;
            }
        }

        $this->apiCall('cgi-bin/gate_cgi', $params);
    }

    public function setLanguage(string $language): void
    {
        $webLang = match ($language) {
            'ru' => 1,
            default => 0,
        };

        // TODO: PAL setting here??? Find out
        $this->apiCall('webs/sysInfoCfgEx', ['sys_pal' => 0, 'sys_language' => $webLang]);
    }

    public function setPublicCode(int $code = 0): void
    {
        if ($code) {
            $this->setIntercom('DoorCode', $code);
            $this->setIntercom('DoorCodeActive', 'on');
        } else {
            $this->setIntercom('DoorCode', self::DEFAULT_PUBLIC_ACCESS_CODE);
            $this->setIntercom('DoorCodeActive', 'off');
        }
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->setAlarm('SOSCallNumber', $sipNumber);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->setIntercom('TalkTimeout', $timeout);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->setIntercom('DoorOpenTime', $time);
    }

    public function setUnlocked(bool $unlocked = true): void
    {
        $this->apiCall('webs/btnSettingEx', [
            'flag' => '4600',
            'paramchannel' => '0',
            'paramcmd' => '0',
            'paramctrl' => (int)$unlocked,
            'paramstep' => '0',
            'paramreserved' => '0',
        ]);

        $this->setIntercom('DoorOpenMode', $unlocked ? 'on' : 'off');
        $this->setIntercom('MainDoorOpenMode', $unlocked ? 'on' : 'off');
        $this->setIntercom('AltDoorOpenMode', $unlocked ? 'on' : 'off');
    }

    /**
     * Enable service codes.
     * These codes are used to perform service operations from the front panel of the device.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @return void
     */
    protected function enableServiceCodes(bool $enabled = true): void
    {
        $state = $enabled ? 'open' : 'close';

        $this->apiCall('cgi-bin/srvcodes_cgi', [
            'action' => 'set',
            'RfidScanActive' => $state,
            'NetInfoActive' => $state,
            'StaticIpActive' => $state,
            'NetResetActive' => $state,
            'AdminResetActive' => $state,
            'FullResetActive' => $state,
            'SaveNetCfgActive' => $state,
            'SaveAptCfgActive' => $state,
            'DoorCodeAddActive' => $state,
        ]);
    }

    /**
     * Enable UPNP.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @return void
     */
    protected function enableUpnp(bool $enabled = true): void
    {
        $this->apiCall('webs/netUPNPCfgEx', ['cksearch' => $enabled ? 1 : 0]);
    }

    /**
     * Set parameter in the "alarm" section.
     *
     * @param string $name Parameter name.
     * @param string $value Parameter value.
     * @return void
     */
    protected function setAlarm(string $name, string $value): void
    {
        $this->apiCall('cgi-bin/intercom_alarm_cgi', ['action' => 'set', $name => $value]);
    }

    /**
     * Set parameter in the "intercom" section.
     *
     * @param string $name Parameter name.
     * @param string $value Parameter value.
     * @return void
     */
    protected function setIntercom(string $name, string $value): void
    {
        $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'set', $name => $value]);
    }
}
