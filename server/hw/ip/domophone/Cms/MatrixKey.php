<?php

namespace hw\ip\domophone\Cms;

/**
 * Builds an unambiguous key for a CMS matrix cell.
 */
final class MatrixKey
{
    /**
     * @return string Matrix cell key in "hundreds:tens:units" format.
     */
    public static function build(int $hundreds, int $tens, int $units): string
    {
        return "$hundreds:$tens:$units";
    }
}
