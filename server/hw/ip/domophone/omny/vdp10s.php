<?php

namespace hw\ip\domophone\omny;

use hw\Interface\LanguageInterface;
use hw\ip\domophone\akuvox\e12;

/**
 * Represents an Omny VDP-10S intercom.
 */
class vdp10s extends e12 implements LanguageInterface
{
    protected static function getMaxUsers(): int
    {
        return 5000; // From manual
    }

    public function configureEventServer(string $url): void
    {
        $this->setConfigParams([
            // VDP-10S emits MakeCall messages required by event and plog parsers only at level 6
            'Config.Settings.LOGLEVEL.Level' => '6',
        ]);

        parent::configureEventServer($url);
    }

    public function setLanguage(string $language): void
    {
        $this->setConfigParams([
            'Config.Settings.LANGUAGE.WebLang' => $language === 'ru' ? '3' : '0',
        ]);
    }
}
