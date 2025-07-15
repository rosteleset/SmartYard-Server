<?php

namespace hw\ip\domophone\beward;

use hw\Interfaces\{CmsLevelsInterface, DisplayTextInterface, LanguageInterface};

/**
 * Class representing a Beward DKS series domophone.
 */
class dks extends beward implements CmsLevelsInterface, DisplayTextInterface, LanguageInterface
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

    public function setLanguage(string $language): void
    {
        $webLang = match ($language) {
            'ru' => 1,
            default => 0,
        };

        // TODO: PAL setting here??? Find out
        $this->apiCall('webs/sysInfoCfgEx', ['sys_pal' => 0, 'sys_language' => $webLang]);
    }
}
