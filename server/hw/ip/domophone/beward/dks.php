<?php

namespace hw\ip\domophone\beward;

use hw\Interfaces\DisplayTextInterface;

/**
 * Class representing a Beward DKS series domophone.
 */
class dks extends beward implements DisplayTextInterface
{
    public function getDisplayText(): array
    {
        return [$this->getParams('display_cgi')['TickerText']];
    }

    public function getDisplayTextLinesCount(): int
    {
        /*
         * In fact, DKS has the ability to display either one long line or five short ones with a given rotation
         * interval, but they only hold 8 characters, so I don't see any point in using them.
         */
        return 1;
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
}
