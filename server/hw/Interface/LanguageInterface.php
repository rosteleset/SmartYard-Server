<?php

namespace hw\Interface;

/**
 * Interface for managing the language settings on a device.
 */
interface LanguageInterface
{
    /**
     * Sets the language used on the device.
     *
     * The language must be provided in the ISO 639-1 format (e.g., "ru", "en", "de").
     * This setting affects all localized outputs, including the web interface and sound files.
     *
     * @param string $language The language code to apply (ISO 639-1).
     * @return void
     */
    public function setLanguage(string $language): void;
}
