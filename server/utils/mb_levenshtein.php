<?php
/**
 * Levenshtein function with UTF-8 support.
 *
 * @ref: https://www.php.net/manual/ja/function.levenshtein.php#113702 by luciole75w's comment @ PHP Manual
 * @ref: https://qiita.com/mpyw/items/2b636827730e06c71e3d by mpyw's article @ Qiita
 * @url: https://github.com/KEINOS/mb_levenshtein for latest information @ GitHub
 */

namespace {

/**
 * mb_levenshtein_ratio.
 *
 * Returns levenshtein distance in ratio between 0 to 1.
 *
 * @param  string  $s1        One of the strings being evaluated for Levenshtein ratio.
 * @param  string  $s2        One of the strings being evaluated for Levenshtein ratio.
 * @param  integer $cost_ins  Defines the cost of insertion.
 * @param  integer $cost_rep  Defines the cost of replacement.
 * @param  integer $cost_del  Defines the cost of deletion.
 *
 * @return float
 */
function mb_levenshtein_ratio($s1, $s2, $cost_ins = 1, $cost_rep = 1, $cost_del = 1)
{
    $l1   = mb_strlen($s1, 'UTF-8');
    $l2   = mb_strlen($s2, 'UTF-8');
    $size = max($l1, $l2);

    if (! $size) {
        return 0;
    }
    if (! $s1) {
        return $l2 / $size;
    }
    if (! $s2) {
        return $l1 / $size;
    }

    return 1.0 - mb_levenshtein($s1, $s2, $cost_ins, $cost_rep, $cost_del) / $size;
}

/**
 * mb_levenshtein.
 *
 * Didactic example showing the usage of the previous conversion function.
 * But for better performance, in a real application with a single input string
 * matched against many strings from a database, you will probably want to pre-
 * encode the input only once.
 *
 * @param  string  $s1        One of the strings being evaluated for Levenshtein distance.
 * @param  string  $s2        One of the strings being evaluated for Levenshtein distance.
 * @param  integer $cost_ins  Defines the cost of insertion.
 * @param  integer $cost_rep  Defines the cost of replacement.
 * @param  integer $cost_del  Defines the cost of deletion.
 *
 * @return integer
 */
function mb_levenshtein($s1, $s2, $cost_ins = 1, $cost_rep = 1, $cost_del = 1)
{
    $charMap = array();
    convert_mb_ascii($s1, $charMap);
    convert_mb_ascii($s2, $charMap);

    return levenshtein($s1, $s2, $cost_ins, $cost_rep, $cost_del);
}

/**
 * convert_mb_ascii.
 *
 * Convert an UTF-8 encoded string to a single-byte string suitable for
 * functions such as levenshtein.
 *
 * The function simply uses (and updates) a tailored dynamic encoding
 * (in/out map parameter) where non-ascii characters are remapped to
 * the range [128-255] in order of appearance.
 *
 * Thus it supports up to 128 different multibyte code points max over
 * the whole set of strings sharing this encoding.
 *
 * @param  string $str  UTF-8 string to be converted to extended ASCII.
 * @param  array  $map  Reference of the map.
 *
 * @return void
 */
function convert_mb_ascii(&$str, &$map)
{
    // find all utf-8 characters
    $matches = array();
    if (! preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
        return; // plain ascii string
    }

    // update the encoding map with the characters not already met
    $mapCount = count($map);
    foreach ($matches[0] as $mbc) {
        if (! isset($map[$mbc])) {
            $map[$mbc] = chr(128 + $mapCount);
            $mapCount++;
        }
    }

    // finally remap non-ascii characters
    $str = strtr($str, $map);
}

}
