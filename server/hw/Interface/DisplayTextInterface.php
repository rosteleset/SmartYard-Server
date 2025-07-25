<?php

namespace hw\Interface;

/**
 * Interface for managing display text on a device.
 */
interface DisplayTextInterface
{
    /**
     * Returns the current text lines displayed on the device.
     *
     * @return string[] List of text lines currently configured on the device.
     */
    public function getDisplayText(): array;

    /**
     * Returns the maximum number of display lines supported by the device.
     *
     * @return int Maximum number of lines the device can display.
     */
    public function getDisplayTextLinesCount(): int;

    /**
     * Sets the lines of text to be shown on the device's display. Each element in the array
     * represents one line of text. Existing display content will be replaced.
     *
     * @param string[] $textLines Array of strings, each representing a line to display.
     * @return void
     */
    public function setDisplayText(array $textLines): void;
}
