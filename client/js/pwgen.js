/*
 * pwgen.js
 *
 * Copyright (C) 2003-2006 KATO Kazuyoshi <kzys@8-p.info>
 *
 * This program is a JavaScript port of pwgen.
 * The original C source code written by Theodore Ts'o.
 * <http://sourceforge.net/projects/pwgen/>
 *
 * This file may be distributed under the terms of the GNU General
 * Public License.
 */

PWGen = {
    initialize: function() {
        this.maxLength = 8;
        this.includeCapitalLetter = true;
        this.includeNumber = true;
    },

    generate0: function() {
        var result = "";
        var prev = 0;
        var isFirst = true;

        var requested = 0;
        if (this.includeCapitalLetter) {
            requested |= this.INCLUDE_CAPITAL_LETTER;
        }

        if (this.includeNumber) {
            requested |= this.INCLUDE_NUMBER;
        }

        var shouldBe = (Math.random() < 0.5) ? this.VOWEL : this.CONSONANT;

        while (result.length < this.maxLength) {
            i = Math.floor((this.ELEMENTS.length - 1) * Math.random());
            str = this.ELEMENTS[i][0];
            flags = this.ELEMENTS[i][1];

            /* Filter on the basic type of the next element */
            if ((flags & shouldBe) == 0)
                continue;
            /* Handle the NOT_FIRST flag */
            if (isFirst && (flags & this.NOT_FIRST))
                continue;
            /* Don't allow VOWEL followed a Vowel/Dipthong pair */
            if ((prev & this.VOWEL) && (flags & this.VOWEL) && (flags & this.DIPTHONG))
                continue;
            /* Don't allow us to overflow the buffer */
            if (result.length + str.length > this.maxLength)
                continue;


            if (requested & this.INCLUDE_CAPITAL_LETTER) {
                if ((isFirst || (flags & this.CONSONANT)) &&
                    (Math.random() > 0.3)) {
                    str = str.slice(0, 1).toUpperCase() + str.slice(1, str.length);
                    requested &= ~this.INCLUDE_CAPITAL_LETTER;
                }
            }

            /*
             * OK, we found an element which matches our criteria,
             * let's do it!
             */
            result += str;


            if (requested & this.INCLUDE_NUMBER) {
                if (!isFirst && (Math.random() < 0.3)) {
                    result += Math.floor(10 * Math.random()).toString();
                    requested &= ~this.INCLUDE_NUMBER;

                    isFirst = true;
                    prev = 0;
                    shouldBe = (Math.random() < 0.5) ? this.VOWEL : this.CONSONANT;
                    continue;
                }
            }

            /*
             * OK, figure out what the next element should be
             */
            if (shouldBe == this.CONSONANT) {
                shouldBe = this.VOWEL;
            } else { /* should_be == VOWEL */
                if ((prev & this.VOWEL) ||
                    (flags & this.DIPTHONG) || (Math.random() > 0.3)) {
                    shouldBe = this.CONSONANT;
                } else {
                    shouldBe = this.VOWEL;
                }
            }
            prev = flags;
            isFirst = false;
        }

        if (requested & (this.INCLUDE_NUMBER | this.INCLUDE_CAPITAL_LETTER))
            return null;

        return result;
    },

    generate: function() {
        var result = null;

        while (! result)
            result = this.generate0();

        return result;
    },

    INCLUDE_NUMBER: 1,
    INCLUDE_CAPITAL_LETTER: 1 << 1,

    CONSONANT: 1,
    VOWEL:     1 << 1,
    DIPTHONG:  1 << 2,
    NOT_FIRST: 1 << 3
};

PWGen.ELEMENTS = [
    [ "a",  PWGen.VOWEL ],
    [ "ae", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "ah", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "ai", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "b",  PWGen.CONSONANT ],
    [ "c",  PWGen.CONSONANT ],
    [ "ch", PWGen.CONSONANT | PWGen.DIPTHONG ],
    [ "d",  PWGen.CONSONANT ],
    [ "e",  PWGen.VOWEL ],
    [ "ee", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "ei", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "f",  PWGen.CONSONANT ],
    [ "g",  PWGen.CONSONANT ],
    [ "gh", PWGen.CONSONANT | PWGen.DIPTHONG | PWGen.NOT_FIRST ],
    [ "h",  PWGen.CONSONANT ],
    [ "i",  PWGen.VOWEL ],
    [ "ie", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "j",  PWGen.CONSONANT ],
    [ "k",  PWGen.CONSONANT ],
    [ "l",  PWGen.CONSONANT ],
    [ "m",  PWGen.CONSONANT ],
    [ "n",  PWGen.CONSONANT ],
    [ "ng", PWGen.CONSONANT | PWGen.DIPTHONG | PWGen.NOT_FIRST ],
    [ "o",  PWGen.VOWEL ],
    [ "oh", PWGen.VOWEL | PWGen.DIPTHONG ],
    [ "oo", PWGen.VOWEL | PWGen.DIPTHONG],
    [ "p",  PWGen.CONSONANT ],
    [ "ph", PWGen.CONSONANT | PWGen.DIPTHONG ],
    [ "qu", PWGen.CONSONANT | PWGen.DIPTHONG],
    [ "r",  PWGen.CONSONANT ],
    [ "s",  PWGen.CONSONANT ],
    [ "sh", PWGen.CONSONANT | PWGen.DIPTHONG],
    [ "t",  PWGen.CONSONANT ],
    [ "th", PWGen.CONSONANT | PWGen.DIPTHONG],
    [ "u",  PWGen.VOWEL ],
    [ "v",  PWGen.CONSONANT ],
    [ "w",  PWGen.CONSONANT ],
    [ "x",  PWGen.CONSONANT ],
    [ "y",  PWGen.CONSONANT ],
    [ "z",  PWGen.CONSONANT ],
];
