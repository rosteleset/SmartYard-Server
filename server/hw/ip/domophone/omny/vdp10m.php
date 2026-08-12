<?php

namespace hw\ip\domophone\omny;

use hw\ip\domophone\akuvox\akuvox;

class vdp10m extends akuvox
{
    protected static function getMaxUsers(): int
    {
        return 5000; // Found by Codex
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        // TODO
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // TODO
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setConfigParams([
            'Config.Programable.SOFTKEY01.LocalParam1' => $sipNumber . str_repeat(';', 7),
        ]);
    }

    protected function getApartments(): array
    {
        // TODO
        return [];
    }
}
