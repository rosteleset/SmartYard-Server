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
