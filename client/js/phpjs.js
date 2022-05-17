function trim(str, charlist) {
    // Strips whitespace from the beginning and end of a string
    //
    // version: 1107.2516
    // discuss at: http://phpjs.org/functions/trim    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: mdsjack (http://www.mdsjack.bo.it)
    // +   improved by: Alexander Ermolaev (http://snippets.dzone.com/user/AlexanderErmolaev)
    // +      input by: Erkekjetter
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // +      input by: DxGx
    // +   improved by: Steven Levithan (http://blog.stevenlevithan.com)
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // *     example 1: trim('    Kevin van Zonneveld    ');    // *     returns 1: 'Kevin van Zonneveld'
    // *     example 2: trim('Hello World', 'Hdle');
    // *     returns 2: 'o Wor'
    // *     example 3: trim(16, 1);
    // *     returns 3: 6    var whitespace, l = 0,
    i = 0;
    str += '';

    if (!charlist) {        // default list
        whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
    } else {
        // preg_quote custom list
        charlist += '';whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
    }

    l = str.length;
    for (i = 0; i < l; i++) {if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(i);
            break;
        }
    }
    l = str.length;
    for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(0, i + 1);break;
        }
    }

    return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
}

function md5(str) {
    // Calculate the md5 hash of a string
    //
    // version: 1107.2516
    // discuss at: http://phpjs.org/functions/md5    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // + namespaced by: Michael White (http://getsprink.com)
    // +    tweaked by: Jack
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Brett Zamir (http://brett-zamir.me)    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: utf8_encode
    // *     example 1: md5('Kevin van Zonneveld');
    // *     returns 1: '6e658d4bfcb59cc13f96c14450ac40b9'
    var xl;
    var rotateLeft = function (lValue, iShiftBits) {
        return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
    };
     var addUnsigned = function (lX, lY) {
        var lX4, lY4, lX8, lY8, lResult;
        lX8 = (lX & 0x80000000);
        lY8 = (lY & 0x80000000);
        lX4 = (lX & 0x40000000);        lY4 = (lY & 0x40000000);
        lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
        if (lX4 & lY4) {
            return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
        }        if (lX4 | lY4) {
            if (lResult & 0x40000000) {
                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
            } else {
                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);            }
        } else {
            return (lResult ^ lX8 ^ lY8);
        }
    };
    var _F = function (x, y, z) {
        return (x & y) | ((~x) & z);
    };
    var _G = function (x, y, z) {        return (x & z) | (y & (~z));
    };
    var _H = function (x, y, z) {
        return (x ^ y ^ z);
    };    var _I = function (x, y, z) {
        return (y ^ (x | (~z)));
    };

    var _FF = function (a, b, c, d, x, s, ac) {        a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    };

    var _GG = function (a, b, c, d, x, s, ac) {        a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    };

    var _HH = function (a, b, c, d, x, s, ac) {        a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    };

    var _II = function (a, b, c, d, x, s, ac) {        a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    };

    var convertToWordArray = function (str) {        var lWordCount;
        var lMessageLength = str.length;
        var lNumberOfWords_temp1 = lMessageLength + 8;
        var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
        var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;        var lWordArray = new Array(lNumberOfWords - 1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
        lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
        lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
        return lWordArray;    };

    var wordToHex = function (lValue) {
        var wordToHexValue = "",
            wordToHexValue_temp = "",            lByte, lCount;
        for (lCount = 0; lCount <= 3; lCount++) {
            lByte = (lValue >>> (lCount * 8)) & 255;
            wordToHexValue_temp = "0" + lByte.toString(16);
            wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);        }
        return wordToHexValue;
    };

    var x = [],        k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
        S12 = 12,
        S13 = 17,
        S14 = 22,
        S21 = 5,        S22 = 9,
        S23 = 14,
        S24 = 20,
        S31 = 4,
        S32 = 11,        S33 = 16,
        S34 = 23,
        S41 = 6,
        S42 = 10,
        S43 = 15,        S44 = 21;

    str = this.utf8_encode(str);
    x = convertToWordArray(str);
    a = 0x67452301;    b = 0xEFCDAB89;
    c = 0x98BADCFE;
    d = 0x10325476;

    xl = x.length;    for (k = 0; k < xl; k += 16) {
        AA = a;
        BB = b;
        CC = c;
        DD = d;        a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
        d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
        c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
        b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
        a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);        d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
        c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
        b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
        a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
        d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);        c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
        b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
        a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
        d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
        c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);        b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
        a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
        d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
        c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
        b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);        a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
        d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
        c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
        b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
        a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);        d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
        c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
        b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
        a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
        d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);        c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
        b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
        a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
        d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
        c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);        b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
        a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
        d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
        c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
        b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);        a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
        d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
        c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
        b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
        a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);        d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
        c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
        b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
        a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
        d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);        c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
        b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
        a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
        d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
        c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);        b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
        a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
        d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
        c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
        b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);        a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
        d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
        c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
        b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
        a = addUnsigned(a, AA);        b = addUnsigned(b, BB);
        c = addUnsigned(c, CC);
        d = addUnsigned(d, DD);
    }
     var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

    return temp.toLowerCase();
}

function utf8_encode(argString) {
    // Encodes an ISO-8859-1 string to UTF-8
    //
    // version: 1107.2516
    // discuss at: http://phpjs.org/functions/utf8_encode    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: sowberry
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman    // +   improved by: Yves Sucaet
    // +   bugfixed by: Onno Marsman
    // +   bugfixed by: Ulrich
    // +   bugfixed by: Rafal Kukawski
    // *     example 1: utf8_encode('Kevin van Zonneveld');    // *     returns 1: 'Kevin van Zonneveld'
    if (argString === null || typeof argString === "undefined") {
        return "";
    }
     var string = (argString + ''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
    var utftext = "",
        start, end, stringl = 0;

    start = end = 0;    stringl = string.length;
    for (var n = 0; n < stringl; n++) {
        var c1 = string.charCodeAt(n);
        var enc = null;
         if (c1 < 128) {
            end++;
        } else if (c1 > 127 && c1 < 2048) {
            enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
        } else {            enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
        }
        if (enc !== null) {
            if (end > start) {
                utftext += string.slice(start, end);            }
            utftext += enc;
            start = end = n + 1;
        }
    }
    if (end > start) {
        utftext += string.slice(start, stringl);
    }
     return utftext;
}

function utf8_decode(str_data) {
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Norman "zEh" Fuchs
    // +   bugfixed by: hitwork
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: utf8_decode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'
    var tmp_arr = [],
        i = 0,
        ac = 0,
        c1 = 0,
        c2 = 0,
        c3 = 0;

    str_data += '';

    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if (c1 > 191 && c1 < 224) {
            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);
            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return tmp_arr.join('');
}

function stripos(f_haystack, f_needle, f_offset) {
    // http://kevin.vanzonneveld.net
    // +     original by: Martijn Wieringa
    // +      revised by: Onno Marsman
    // *         example 1: stripos('ABC', 'a');
    // *         returns 1: 0
    var haystack = (f_haystack + '').toLowerCase();
    var needle = (f_needle + '').toLowerCase();

    return haystack.indexOf(needle, f_offset);
}

function is_int(mixed_var) {
    // http://kevin.vanzonneveld.net
    // +   original by: Alex
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Matt Bradley
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: WebDevHobo (http://webdevhobo.blogspot.com/)
    // +   improved by: Rafał Kukawski (http://blog.kukawski.pl)
    // %        note 1: 1.0 is simplified to 1 before it can be accessed by the function, this makes
    // %        note 1: it different from the PHP implementation. We can't fix this unfortunately.
    // *     example 1: is_int(23)
    // *     returns 1: true
    // *     example 2: is_int('23')
    // *     returns 2: false
    // *     example 3: is_int(23.5)
    // *     returns 3: false
    // *     example 4: is_int(true)
    // *     returns 4: false
    return mixed_var === ~~mixed_var;
}

function str_replace(search, replace, subject, countObj) { // eslint-disable-line camelcase
  //  discuss at: http://locutus.io/php/str_replace/
  // original by: Kevin van Zonneveld (http://kvz.io)
  // improved by: Gabriel Paderni
  // improved by: Philip Peterson
  // improved by: Simon Willison (http://simonwillison.net)
  // improved by: Kevin van Zonneveld (http://kvz.io)
  // improved by: Onno Marsman (https://twitter.com/onnomarsman)
  // improved by: Brett Zamir (http://brett-zamir.me)
  //  revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // bugfixed by: Anton Ongson
  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
  // bugfixed by: Oleg Eremeev
  // bugfixed by: Glen Arason (http://CanadianDomainRegistry.ca)
  // bugfixed by: Glen Arason (http://CanadianDomainRegistry.ca)
  //    input by: Onno Marsman (https://twitter.com/onnomarsman)
  //    input by: Brett Zamir (http://brett-zamir.me)
  //    input by: Oleg Eremeev
  //      note 1: The countObj parameter (optional) if used must be passed in as a
  //      note 1: object. The count will then be written by reference into it's `value` property
  //   example 1: str_replace(' ', '.', 'Kevin van Zonneveld')
  //   returns 1: 'Kevin.van.Zonneveld'
  //   example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars')
  //   returns 2: 'hemmo, mars'
  //   example 3: str_replace(Array('S','F'),'x','ASDFASDF')
  //   returns 3: 'AxDxAxDx'
  //   example 4: var countObj = {}
  //   example 4: str_replace(['A','D'], ['x','y'] , 'ASDFASDF' , countObj)
  //   example 4: var $result = countObj.value
  //   returns 4: 4

  var i = 0
  var j = 0
  var temp = ''
  var repl = ''
  var sl = 0
  var fl = 0
  var f = [].concat(search)
  var r = [].concat(replace)
  var s = subject
  var ra = Object.prototype.toString.call(r) === '[object Array]'
  var sa = Object.prototype.toString.call(s) === '[object Array]'
  s = [].concat(s)

  var $global = (typeof window !== 'undefined' ? window : GLOBAL)
  $global.$locutus = $global.$locutus || {}
  var $locutus = $global.$locutus
  $locutus.php = $locutus.php || {}

  if (typeof (search) === 'object' && typeof (replace) === 'string') {
    temp = replace
    replace = []
    for (i = 0; i < search.length; i += 1) {
      replace[i] = temp
    }
    temp = ''
    r = [].concat(replace)
    ra = Object.prototype.toString.call(r) === '[object Array]'
  }

  if (typeof countObj !== 'undefined') {
    countObj.value = 0
  }

  for (i = 0, sl = s.length; i < sl; i++) {
    if (s[i] === '') {
      continue
    }
    for (j = 0, fl = f.length; j < fl; j++) {
      temp = s[i] + ''
      repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0]
      s[i] = (temp).split(f[j]).join(repl)
      if (typeof countObj !== 'undefined') {
        countObj.value += ((temp.split(f[j])).length - 1)
      }
    }
  }
  return sa ? s : s[0]
}

function date(format, timestamp) {
    //  discuss at: http://locutus.io/php/date/
    // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // original by: gettimeofday
    //    parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
    // improved by: Kevin van Zonneveld (http://kvz.io)
    // improved by: MeEtc (http://yass.meetcweb.com)
    // improved by: Brad Touesnard
    // improved by: Tim Wiel
    // improved by: Bryan Elliott
    // improved by: David Randall
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Brett Zamir (http://brett-zamir.me)
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Thomas Beaucourt (http://www.webapp.fr)
    // improved by: JT
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Rafał Kukawski (http://blog.kukawski.pl)
    // improved by: Theriault (https://github.com/Theriault)
    //    input by: Brett Zamir (http://brett-zamir.me)
    //    input by: majak
    //    input by: Alex
    //    input by: Martin
    //    input by: Alex Wilson
    //    input by: Haravikk
    // bugfixed by: Kevin van Zonneveld (http://kvz.io)
    // bugfixed by: majak
    // bugfixed by: Kevin van Zonneveld (http://kvz.io)
    // bugfixed by: Brett Zamir (http://brett-zamir.me)
    // bugfixed by: omid (http://locutus.io/php/380:380#comment_137122)
    // bugfixed by: Chris (http://www.devotis.nl/)
    //      note 1: Uses global: locutus to store the default timezone
    //      note 1: Although the function potentially allows timezone info
    //      note 1: (see notes), it currently does not set
    //      note 1: per a timezone specified by date_default_timezone_set(). Implementers might use
    //      note 1: $locutus.currentTimezoneOffset and
    //      note 1: $locutus.currentTimezoneDST set by that function
    //      note 1: in order to adjust the dates in this function
    //      note 1: (or our other date functions!) accordingly
    //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400)
    //   returns 1: '07:09:40 m is month'
    //   example 2: date('F j, Y, g:i a', 1062462400)
    //   returns 2: 'September 2, 2003, 12:26 am'
    //   example 3: date('Y W o', 1062462400)
    //   returns 3: '2003 36 2003'
    //   example 4: var $x = date('Y m d', (new Date()).getTime() / 1000)
    //   example 4: $x = $x + ''
    //   example 4: var $result = $x.length // 2009 01 09
    //   returns 4: 10
    //   example 5: date('W', 1104534000)
    //   returns 5: '52'
    //   example 6: date('B t', 1104534000)
    //   returns 6: '999 31'
    //   example 7: date('W U', 1293750000.82); // 2010-12-31
    //   returns 7: '52 1293750000'
    //   example 8: date('W', 1293836400); // 2011-01-01
    //   returns 8: '52'
    //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
    //   returns 9: '52 2011-01-02'
    //        test: skip-1 skip-2 skip-5
    var jsdate, f
    // Keep this here (works, but for code commented-out below for file size reasons)
    // var tal= [];
    var txtWords = [
        'Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur',
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ]
    // trailing backslash -> (dropped)
    // a backslash followed by any character (including backslash) -> the character
    // empty string -> empty string
    var formatChr = /\\?(.?)/gi
    var formatChrCb = function (t, s) {
        return f[t] ? f[t]() : s
    }
    var _pad = function (n, c) {
        n = String(n)
        while (n.length < c) {
            n = '0' + n
        }
        return n
    }
    f = {
        // Day
        d: function () {
            // Day of month w/leading 0; 01..31
            return _pad(f.j(), 2)
        },
        D: function () {
            // Shorthand day name; Mon...Sun
            return f.l()
                .slice(0, 3)
        },
        j: function () {
            // Day of month; 1..31
            return jsdate.getDate()
        },
        l: function () {
            // Full day name; Monday...Sunday
            return txtWords[f.w()] + 'day'
        },
        N: function () {
            // ISO-8601 day of week; 1[Mon]..7[Sun]
            return f.w() || 7
        },
        S: function () {
            // Ordinal suffix for day of month; st, nd, rd, th
            var j = f.j()
            var i = j % 10
            if (i <= 3 && parseInt((j % 100) / 10, 10) === 1) {
                i = 0
            }
            return ['st', 'nd', 'rd'][i - 1] || 'th'
        },
        w: function () {
            // Day of week; 0[Sun]..6[Sat]
            return jsdate.getDay()
        },
        z: function () {
            // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j())
            var b = new Date(f.Y(), 0, 1)
            return Math.round((a - b) / 864e5)
        },
        // Week
        W: function () {
            // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3)
            var b = new Date(a.getFullYear(), 0, 4)
            return _pad(1 + Math.round((a - b) / 864e5 / 7), 2)
        },
        // Month
        F: function () {
            // Full month name; January...December
            return txtWords[6 + f.n()]
        },
        m: function () {
            // Month w/leading 0; 01...12
            return _pad(f.n(), 2)
        },
        M: function () {
            // Shorthand month name; Jan...Dec
            return f.F()
                .slice(0, 3)
        },
        n: function () {
            // Month; 1...12
            return jsdate.getMonth() + 1
        },
        t: function () {
            // Days in month; 28...31
            return (new Date(f.Y(), f.n(), 0))
                .getDate()
        },
        // Year
        L: function () {
            // Is leap year?; 0 or 1
            var j = f.Y()
            return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0
        },
        o: function () {
            // ISO-8601 year
            var n = f.n()
            var W = f.W()
            var Y = f.Y()
            return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0)
        },
        Y: function () {
            // Full year; e.g. 1980...2010
            return jsdate.getFullYear()
        },
        y: function () {
            // Last two digits of year; 00...99
            return f.Y()
                .toString()
                .slice(-2)
        },
        // Time
        a: function () {
            // am or pm
            return jsdate.getHours() > 11 ? 'pm' : 'am'
        },
        A: function () {
            // AM or PM
            return f.a()
                .toUpperCase()
        },
        B: function () {
            // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2
            // Hours
            var i = jsdate.getUTCMinutes() * 60
            // Minutes
            // Seconds
            var s = jsdate.getUTCSeconds()
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3)
        },
        g: function () {
            // 12-Hours; 1..12
            return f.G() % 12 || 12
        },
        G: function () {
            // 24-Hours; 0..23
            return jsdate.getHours()
        },
        h: function () {
            // 12-Hours w/leading 0; 01..12
            return _pad(f.g(), 2)
        },
        H: function () {
            // 24-Hours w/leading 0; 00..23
            return _pad(f.G(), 2)
        },
        i: function () {
            // Minutes w/leading 0; 00..59
            return _pad(jsdate.getMinutes(), 2)
        },
        s: function () {
            // Seconds w/leading 0; 00..59
            return _pad(jsdate.getSeconds(), 2)
        },
        u: function () {
            // Microseconds; 000000-999000
            return _pad(jsdate.getMilliseconds() * 1000, 6)
        },
        // Timezone
        e: function () {
            // Timezone identifier; e.g. Atlantic/Azores, ...
            // The following works, but requires inclusion of the very large
            // timezone_abbreviations_list() function.
            /*              return that.date_default_timezone_get();
             */
            var msg = 'Not supported (see source code of date() for timezone on how to add support)'
            throw new Error(msg)
        },
        I: function () {
            // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0)
            // Jan 1
            var c = Date.UTC(f.Y(), 0)
            // Jan 1 UTC
            var b = new Date(f.Y(), 6)
            // Jul 1
            // Jul 1 UTC
            var d = Date.UTC(f.Y(), 6)
            return ((a - c) !== (b - d)) ? 1 : 0
        },
        O: function () {
            // Difference to GMT in hour format; e.g. +0200
            var tzo = jsdate.getTimezoneOffset()
            var a = Math.abs(tzo)
            return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4)
        },
        P: function () {
            // Difference to GMT w/colon; e.g. +02:00
            var O = f.O()
            return (O.substr(0, 3) + ':' + O.substr(3, 2))
        },
        T: function () {
            // The following works, but requires inclusion of the very
            // large timezone_abbreviations_list() function.
            /*              var abbr, i, os, _default;
            if (!tal.length) {
              tal = that.timezone_abbreviations_list();
            }
            if ($locutus && $locutus.default_timezone) {
              _default = $locutus.default_timezone;
              for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                  if (tal[abbr][i].timezone_id === _default) {
                    return abbr.toUpperCase();
                  }
                }
              }
            }
            for (abbr in tal) {
              for (i = 0; i < tal[abbr].length; i++) {
                os = -jsdate.getTimezoneOffset() * 60;
                if (tal[abbr][i].offset === os) {
                  return abbr.toUpperCase();
                }
              }
            }
            */
            return 'UTC'
        },
        Z: function () {
            // Timezone offset in seconds (-43200...50400)
            return -jsdate.getTimezoneOffset() * 60
        },
        // Full Date/Time
        c: function () {
            // ISO-8601 date.
            return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb)
        },
        r: function () {
            // RFC 2822
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb)
        },
        U: function () {
            // Seconds since UNIX epoch
            return jsdate / 1000 | 0
        }
    }
    var _date = function (format, timestamp) {
        jsdate = (timestamp === undefined ? new Date() // Not provided
                : (timestamp instanceof Date) ? new Date(timestamp) // JS Date()
                    : new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
        )
        return format.replace(formatChr, formatChrCb)
    }
    return _date(format, timestamp)
}

function date_ru(format, timestamp) {
    //  discuss at: http://locutus.io/php/date/
    // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // original by: gettimeofday
    //    parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
    // improved by: Kevin van Zonneveld (http://kvz.io)
    // improved by: MeEtc (http://yass.meetcweb.com)
    // improved by: Brad Touesnard
    // improved by: Tim Wiel
    // improved by: Bryan Elliott
    // improved by: David Randall
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Brett Zamir (http://brett-zamir.me)
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Thomas Beaucourt (http://www.webapp.fr)
    // improved by: JT
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Rafał Kukawski (http://blog.kukawski.pl)
    // improved by: Theriault (https://github.com/Theriault)
    //    input by: Brett Zamir (http://brett-zamir.me)
    //    input by: majak
    //    input by: Alex
    //    input by: Martin
    //    input by: Alex Wilson
    //    input by: Haravikk
    // bugfixed by: Kevin van Zonneveld (http://kvz.io)
    // bugfixed by: majak
    // bugfixed by: Kevin van Zonneveld (http://kvz.io)
    // bugfixed by: Brett Zamir (http://brett-zamir.me)
    // bugfixed by: omid (http://locutus.io/php/380:380#comment_137122)
    // bugfixed by: Chris (http://www.devotis.nl/)
    //      note 1: Uses global: locutus to store the default timezone
    //      note 1: Although the function potentially allows timezone info
    //      note 1: (see notes), it currently does not set
    //      note 1: per a timezone specified by date_default_timezone_set(). Implementers might use
    //      note 1: $locutus.currentTimezoneOffset and
    //      note 1: $locutus.currentTimezoneDST set by that function
    //      note 1: in order to adjust the dates in this function
    //      note 1: (or our other date functions!) accordingly
    //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400)
    //   returns 1: '07:09:40 m is month'
    //   example 2: date('F j, Y, g:i a', 1062462400)
    //   returns 2: 'September 2, 2003, 12:26 am'
    //   example 3: date('Y W o', 1062462400)
    //   returns 3: '2003 36 2003'
    //   example 4: var $x = date('Y m d', (new Date()).getTime() / 1000)
    //   example 4: $x = $x + ''
    //   example 4: var $result = $x.length // 2009 01 09
    //   returns 4: 10
    //   example 5: date('W', 1104534000)
    //   returns 5: '52'
    //   example 6: date('B t', 1104534000)
    //   returns 6: '999 31'
    //   example 7: date('W U', 1293750000.82); // 2010-12-31
    //   returns 7: '52 1293750000'
    //   example 8: date('W', 1293836400); // 2011-01-01
    //   returns 8: '52'
    //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
    //   returns 9: '52 2011-01-02'
    //        test: skip-1 skip-2 skip-5
    var jsdate, f
    // Keep this here (works, but for code commented-out below for file size reasons)
    // var tal= [];
    var txtWords = [
        'Вск', 'Пнд', 'Втр', 'Срд', 'Чтв', 'Птн', 'Суб',
        'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
        'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'
    ]
    // trailing backslash -> (dropped)
    // a backslash followed by any character (including backslash) -> the character
    // empty string -> empty string
    var formatChr = /\\?(.?)/gi
    var formatChrCb = function (t, s) {
        return f[t] ? f[t]() : s
    }
    var _pad = function (n, c) {
        n = String(n)
        while (n.length < c) {
            n = '0' + n
        }
        return n
    }
    f = {
        // Day
        d: function () {
            // Day of month w/leading 0; 01..31
            return _pad(f.j(), 2)
        },
        D: function () {
            // Shorthand day name; Mon...Sun
            return f.l()
                .slice(0, 3)
        },
        j: function () {
            // Day of month; 1..31
            return jsdate.getDate()
        },
        l: function () {
            // Full day name; Monday...Sunday
            return txtWords[f.w()]
        },
        N: function () {
            // ISO-8601 day of week; 1[Mon]..7[Sun]
            return f.w() || 7
        },
        S: function () {
            // Ordinal suffix for day of month; st, nd, rd, th
            var j = f.j()
            var i = j % 10
            if (i <= 3 && parseInt((j % 100) / 10, 10) === 1) {
                i = 0
            }
            return ['st', 'nd', 'rd'][i - 1] || 'th'
        },
        w: function () {
            // Day of week; 0[Sun]..6[Sat]
            return jsdate.getDay()
        },
        z: function () {
            // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j())
            var b = new Date(f.Y(), 0, 1)
            return Math.round((a - b) / 864e5)
        },
        // Week
        W: function () {
            // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3)
            var b = new Date(a.getFullYear(), 0, 4)
            return _pad(1 + Math.round((a - b) / 864e5 / 7), 2)
        },
        // Month
        F: function () {
            // Full month name; January...December
            return txtWords[6 + f.n()]
        },
        m: function () {
            // Month w/leading 0; 01...12
            return _pad(f.n(), 2)
        },
        M: function () {
            // Shorthand month name; Jan...Dec
            return f.F()
                .slice(0, 3)
        },
        n: function () {
            // Month; 1...12
            return jsdate.getMonth() + 1
        },
        t: function () {
            // Days in month; 28...31
            return (new Date(f.Y(), f.n(), 0))
                .getDate()
        },
        // Year
        L: function () {
            // Is leap year?; 0 or 1
            var j = f.Y()
            return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0
        },
        o: function () {
            // ISO-8601 year
            var n = f.n()
            var W = f.W()
            var Y = f.Y()
            return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0)
        },
        Y: function () {
            // Full year; e.g. 1980...2010
            return jsdate.getFullYear()
        },
        y: function () {
            // Last two digits of year; 00...99
            return f.Y()
                .toString()
                .slice(-2)
        },
        // Time
        a: function () {
            // am or pm
            return jsdate.getHours() > 11 ? 'pm' : 'am'
        },
        A: function () {
            // AM or PM
            return f.a()
                .toUpperCase()
        },
        B: function () {
            // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2
            // Hours
            var i = jsdate.getUTCMinutes() * 60
            // Minutes
            // Seconds
            var s = jsdate.getUTCSeconds()
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3)
        },
        g: function () {
            // 12-Hours; 1..12
            return f.G() % 12 || 12
        },
        G: function () {
            // 24-Hours; 0..23
            return jsdate.getHours()
        },
        h: function () {
            // 12-Hours w/leading 0; 01..12
            return _pad(f.g(), 2)
        },
        H: function () {
            // 24-Hours w/leading 0; 00..23
            return _pad(f.G(), 2)
        },
        i: function () {
            // Minutes w/leading 0; 00..59
            return _pad(jsdate.getMinutes(), 2)
        },
        s: function () {
            // Seconds w/leading 0; 00..59
            return _pad(jsdate.getSeconds(), 2)
        },
        u: function () {
            // Microseconds; 000000-999000
            return _pad(jsdate.getMilliseconds() * 1000, 6)
        },
        // Timezone
        e: function () {
            // Timezone identifier; e.g. Atlantic/Azores, ...
            // The following works, but requires inclusion of the very large
            // timezone_abbreviations_list() function.
            /*              return that.date_default_timezone_get();
             */
            var msg = 'Not supported (see source code of date() for timezone on how to add support)'
            throw new Error(msg)
        },
        I: function () {
            // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0)
            // Jan 1
            var c = Date.UTC(f.Y(), 0)
            // Jan 1 UTC
            var b = new Date(f.Y(), 6)
            // Jul 1
            // Jul 1 UTC
            var d = Date.UTC(f.Y(), 6)
            return ((a - c) !== (b - d)) ? 1 : 0
        },
        O: function () {
            // Difference to GMT in hour format; e.g. +0200
            var tzo = jsdate.getTimezoneOffset()
            var a = Math.abs(tzo)
            return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4)
        },
        P: function () {
            // Difference to GMT w/colon; e.g. +02:00
            var O = f.O()
            return (O.substr(0, 3) + ':' + O.substr(3, 2))
        },
        T: function () {
            // The following works, but requires inclusion of the very
            // large timezone_abbreviations_list() function.
            /*              var abbr, i, os, _default;
            if (!tal.length) {
              tal = that.timezone_abbreviations_list();
            }
            if ($locutus && $locutus.default_timezone) {
              _default = $locutus.default_timezone;
              for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                  if (tal[abbr][i].timezone_id === _default) {
                    return abbr.toUpperCase();
                  }
                }
              }
            }
            for (abbr in tal) {
              for (i = 0; i < tal[abbr].length; i++) {
                os = -jsdate.getTimezoneOffset() * 60;
                if (tal[abbr][i].offset === os) {
                  return abbr.toUpperCase();
                }
              }
            }
            */
            return 'UTC'
        },
        Z: function () {
            // Timezone offset in seconds (-43200...50400)
            return -jsdate.getTimezoneOffset() * 60
        },
        // Full Date/Time
        c: function () {
            // ISO-8601 date.
            return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb)
        },
        r: function () {
            // RFC 2822
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb)
        },
        U: function () {
            // Seconds since UNIX epoch
            return jsdate / 1000 | 0
        }
    }
    var _date = function (format, timestamp) {
        jsdate = (timestamp === undefined ? new Date() // Not provided
                : (timestamp instanceof Date) ? new Date(timestamp) // JS Date()
                    : new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
        )
        return format.replace(formatChr, formatChrCb)
    }
    return _date(format, timestamp)
}

function ip2long(argIP) {
  //  discuss at: http://locutus.io/php/ip2long/
  // original by: Waldo Malqui Silva (http://waldo.malqui.info)
  // improved by: Victor
  //  revised by: fearphage (http://http/my.opera.com/fearphage/)
  //  revised by: Theriault (https://github.com/Theriault)
  //    estarget: es2015
  //   example 1: ip2long('192.0.34.166')
  //   returns 1: 3221234342
  //   example 2: ip2long('0.0xABCDEF')
  //   returns 2: 11259375
  //   example 3: ip2long('255.255.255.256')
  //   returns 3: false
  let i = 0
  // PHP allows decimal, octal, and hexadecimal IP components.
  // PHP allows between 1 (e.g. 127) to 4 (e.g 127.0.0.1) components.
  const pattern = new RegExp([
    '^([1-9]\\d*|0[0-7]*|0x[\\da-f]+)',
    '(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?',
    '(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?',
    '(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?$'
  ].join(''), 'i')
  argIP = argIP.match(pattern) // Verify argIP format.
  if (!argIP) {
    // Invalid format.
    return false
  }
  // Reuse argIP variable for component counter.
  argIP[0] = 0
  for (i = 1; i < 5; i += 1) {
    argIP[0] += !!((argIP[i] || '').length)
    argIP[i] = parseInt(argIP[i]) || 0
  }
  // Continue to use argIP for overflow values.
  // PHP does not allow any component to overflow.
  argIP.push(256, 256, 256, 256)
  // Recalculate overflow of last component supplied to make up for missing components.
  argIP[4 + argIP[0]] *= Math.pow(256, 4 - argIP[0])
  if (argIP[1] >= argIP[5] ||
    argIP[2] >= argIP[6] ||
    argIP[3] >= argIP[7] ||
    argIP[4] >= argIP[8]) {
    return false
  }
  return argIP[1] * (argIP[0] === 1 || 16777216) +
    argIP[2] * (argIP[0] <= 2 || 65536) +
    argIP[3] * (argIP[0] <= 3 || 256) +
    argIP[4] * 1
}

function long2ip(ip) {
  //  discuss at: http://locutus.io/php/long2ip/
  // original by: Waldo Malqui Silva (http://waldo.malqui.info)
  //   example 1: long2ip( 3221234342 )
  //   returns 1: '192.0.34.166'
  if (!isFinite(ip)) {
    return false
  }
  return [ip >>> 24, ip >>> 16 & 0xFF, ip >>> 8 & 0xFF, ip & 0xFF].join('.')
}

function strtotime(text, now) {
  //  discuss at: http://locutus.io/php/strtotime/
  // original by: Caio Ariede (http://caioariede.com)
  // improved by: Kevin van Zonneveld (http://kvz.io)
  // improved by: Caio Ariede (http://caioariede.com)
  // improved by: A. Matías Quezada (http://amatiasq.com)
  // improved by: preuter
  // improved by: Brett Zamir (http://brett-zamir.me)
  // improved by: Mirko Faber
  //    input by: David
  // bugfixed by: Wagner B. Soares
  // bugfixed by: Artur Tchernychev
  // bugfixed by: Stephan Bösch-Plepelits (http://github.com/plepe)
  //      note 1: Examples all have a fixed timestamp to prevent
  //      note 1: tests to fail because of variable time(zones)
  //   example 1: strtotime('+1 day', 1129633200)
  //   returns 1: 1129719600
  //   example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200)
  //   returns 2: 1130425202
  //   example 3: strtotime('last month', 1129633200)
  //   returns 3: 1127041200
  //   example 4: strtotime('2009-05-04 08:30:00 GMT')
  //   returns 4: 1241425800
  //   example 5: strtotime('2009-05-04 08:30:00+00')
  //   returns 5: 1241425800
  //   example 6: strtotime('2009-05-04 08:30:00+02:00')
  //   returns 6: 1241418600
  //   example 7: strtotime('2009-05-04T08:30:00Z')
  //   returns 7: 1241425800

  var parsed
  var match
  var today
  var year
  var date
  var days
  var ranges
  var len
  var times
  var regex
  var i
  var fail = false

  if (!text) {
    return fail
  }

  // Unecessary spaces
  text = text.replace(/^\s+|\s+$/g, '')
    .replace(/\s{2,}/g, ' ')
    .replace(/[\t\r\n]/g, '')
    .toLowerCase()

  // in contrast to php, js Date.parse function interprets:
  // dates given as yyyy-mm-dd as in timezone: UTC,
  // dates with "." or "-" as MDY instead of DMY
  // dates with two-digit years differently
  // etc...etc...
  // ...therefore we manually parse lots of common date formats
  var pattern = new RegExp([
    '^(\\d{1,4})',
    '([\\-\\.\\/:])',
    '(\\d{1,2})',
    '([\\-\\.\\/:])',
    '(\\d{1,4})',
    '(?:\\s(\\d{1,2}):(\\d{2})?:?(\\d{2})?)?',
    '(?:\\s([A-Z]+)?)?$'
  ].join(''))
  match = text.match(pattern)

  if (match && match[2] === match[4]) {
    if (match[1] > 1901) {
      switch (match[2]) {
        case '-':
          // YYYY-M-D
          if (match[3] > 12 || match[5] > 31) {
            return fail
          }

          return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // YYYY.M.D is not parsed by strtotime()
          return fail
        case '/':
          // YYYY/M/D
          if (match[3] > 12 || match[5] > 31) {
            return fail
          }

          return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
      }
    } else if (match[5] > 1901) {
      switch (match[2]) {
        case '-':
          // D-M-YYYY
          if (match[3] > 12 || match[1] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // D.M.YYYY
          if (match[3] > 12 || match[1] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '/':
          // M/D/YYYY
          if (match[1] > 12 || match[3] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[1], 10) - 1, match[3],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
      }
    } else {
      switch (match[2]) {
        case '-':
          // YY-M-D
          if (match[3] > 12 || match[5] > 31 || (match[1] < 70 && match[1] > 38)) {
            return fail
          }

          year = match[1] >= 0 && match[1] <= 38 ? +match[1] + 2000 : match[1]
          return new Date(year, parseInt(match[3], 10) - 1, match[5],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // D.M.YY or H.MM.SS
          if (match[5] >= 70) {
            // D.M.YY
            if (match[3] > 12 || match[1] > 31) {
              return fail
            }

            return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
          }
          if (match[5] < 60 && !match[6]) {
            // H.MM.SS
            if (match[1] > 23 || match[3] > 59) {
              return fail
            }

            today = new Date()
            return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
            match[1] || 0, match[3] || 0, match[5] || 0, match[9] || 0) / 1000
          }

          // invalid format, cannot be parsed
          return fail
        case '/':
          // M/D/YY
          if (match[1] > 12 || match[3] > 31 || (match[5] < 70 && match[5] > 38)) {
            return fail
          }

          year = match[5] >= 0 && match[5] <= 38 ? +match[5] + 2000 : match[5]
          return new Date(year, parseInt(match[1], 10) - 1, match[3],
          match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case ':':
          // HH:MM:SS
          if (match[1] > 23 || match[3] > 59 || match[5] > 59) {
            return fail
          }

          today = new Date()
          return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
          match[1] || 0, match[3] || 0, match[5] || 0) / 1000
      }
    }
  }

  // other formats and "now" should be parsed by Date.parse()
  if (text === 'now') {
    return now === null || isNaN(now)
      ? new Date().getTime() / 1000 | 0
      : now | 0
  }
  if (!isNaN(parsed = Date.parse(text))) {
    return parsed / 1000 | 0
  }
  // Browsers !== Chrome have problems parsing ISO 8601 date strings, as they do
  // not accept lower case characters, space, or shortened time zones.
  // Therefore, fix these problems and try again.
  // Examples:
  //   2015-04-15 20:33:59+02
  //   2015-04-15 20:33:59z
  //   2015-04-15t20:33:59+02:00
  pattern = new RegExp([
    '^([0-9]{4}-[0-9]{2}-[0-9]{2})',
    '[ t]',
    '([0-9]{2}:[0-9]{2}:[0-9]{2}(\\.[0-9]+)?)',
    '([\\+-][0-9]{2}(:[0-9]{2})?|z)'
  ].join(''))
  match = text.match(pattern)
  if (match) {
    // @todo: time zone information
    if (match[4] === 'z') {
      match[4] = 'Z'
    } else if (match[4].match(/^([+-][0-9]{2})$/)) {
      match[4] = match[4] + ':00'
    }

    if (!isNaN(parsed = Date.parse(match[1] + 'T' + match[2] + match[4]))) {
      return parsed / 1000 | 0
    }
  }

  date = now ? new Date(now * 1000) : new Date()
  days = {
    'sun': 0,
    'mon': 1,
    'tue': 2,
    'wed': 3,
    'thu': 4,
    'fri': 5,
    'sat': 6
  }
  ranges = {
    'yea': 'FullYear',
    'mon': 'Month',
    'day': 'Date',
    'hou': 'Hours',
    'min': 'Minutes',
    'sec': 'Seconds'
  }

  function lastNext (type, range, modifier) {
    var diff
    var day = days[range]

    if (typeof day !== 'undefined') {
      diff = day - date.getDay()

      if (diff === 0) {
        diff = 7 * modifier
      } else if (diff > 0 && type === 'last') {
        diff -= 7
      } else if (diff < 0 && type === 'next') {
        diff += 7
      }

      date.setDate(date.getDate() + diff)
    }
  }

  function process (val) {
    // @todo: Reconcile this with regex using \s, taking into account
    // browser issues with split and regexes
    var splt = val.split(' ')
    var type = splt[0]
    var range = splt[1].substring(0, 3)
    var typeIsNumber = /\d+/.test(type)
    var ago = splt[2] === 'ago'
    var num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1)

    if (typeIsNumber) {
      num *= parseInt(type, 10)
    }

    if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
      return date['set' + ranges[range]](date['get' + ranges[range]]() + num)
    }

    if (range === 'wee') {
      return date.setDate(date.getDate() + (num * 7))
    }

    if (type === 'next' || type === 'last') {
      lastNext(type, range, num)
    } else if (!typeIsNumber) {
      return false
    }

    return true
  }

  times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
    '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
    '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)'
  regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?'

  match = text.match(new RegExp(regex, 'gi'))
  if (!match) {
    return fail
  }

  for (i = 0, len = match.length; i < len; i++) {
    if (!process(match[i])) {
      return fail
    }
  }

  return (date.getTime() / 1000)
}

function sprintf () {
    //  discuss at: https://locutus.io/php/sprintf/
    // original by: Ash Searle (https://hexmen.com/blog/)
    // improved by: Michael White (https://getsprink.com)
    // improved by: Jack
    // improved by: Kevin van Zonneveld (https://kvz.io)
    // improved by: Kevin van Zonneveld (https://kvz.io)
    // improved by: Kevin van Zonneveld (https://kvz.io)
    // improved by: Dj
    // improved by: Allidylls
    //    input by: Paulo Freitas
    //    input by: Brett Zamir (https://brett-zamir.me)
    // improved by: Rafał Kukawski (https://kukawski.pl)
    //   example 1: sprintf("%01.2f", 123.1)
    //   returns 1: '123.10'
    //   example 2: sprintf("[%10s]", 'monkey')
    //   returns 2: '[    monkey]'
    //   example 3: sprintf("[%'#10s]", 'monkey')
    //   returns 3: '[####monkey]'
    //   example 4: sprintf("%d", 123456789012345)
    //   returns 4: '123456789012345'
    //   example 5: sprintf('%-03s', 'E')
    //   returns 5: 'E00'
    //   example 6: sprintf('%+010d', 9)
    //   returns 6: '+000000009'
    //   example 7: sprintf('%+0\'@10d', 9)
    //   returns 7: '@@@@@@@@+9'
    //   example 8: sprintf('%.f', 3.14)
    //   returns 8: '3.140000'
    //   example 9: sprintf('%% %2$d', 1, 2)
    //   returns 9: '% 2'
    const regex = /%%|%(?:(\d+)\$)?((?:[-+#0 ]|'[\s\S])*)(\d+)?(?:\.(\d*))?([\s\S])/g
    const args = arguments
    let i = 0
    const format = args[i++]
    const _pad = function (str, len, chr, leftJustify) {
        if (!chr) {
            chr = ' '
        }
        const padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0).join(chr)
        return leftJustify ? str + padding : padding + str
    }
    const justify = function (value, prefix, leftJustify, minWidth, padChar) {
        const diff = minWidth - value.length
        if (diff > 0) {
            // when padding with zeros
            // on the left side
            // keep sign (+ or -) in front
            if (!leftJustify && padChar === '0') {
                value = [
                    value.slice(0, prefix.length),
                    _pad('', diff, '0', true),
                    value.slice(prefix.length)
                ].join('')
            } else {
                value = _pad(value, minWidth, padChar, leftJustify)
            }
        }
        return value
    }
    const _formatBaseX = function (value, base, leftJustify, minWidth, precision, padChar) {
        // Note: casts negative numbers to positive ones
        const number = value >>> 0
        value = _pad(number.toString(base), precision || 0, '0', false)
        return justify(value, '', leftJustify, minWidth, padChar)
    }
    // _formatString()
    const _formatString = function (value, leftJustify, minWidth, precision, customPadChar) {
        if (precision !== null && precision !== undefined) {
            value = value.slice(0, precision)
        }
        return justify(value, '', leftJustify, minWidth, customPadChar)
    }
    // doFormat()
    const doFormat = function (substring, argIndex, modifiers, minWidth, precision, specifier) {
        let number, prefix, method, textTransform, value
        if (substring === '%%') {
            return '%'
        }
        // parse modifiers
        let padChar = ' ' // pad with spaces by default
        let leftJustify = false
        let positiveNumberPrefix = ''
        let j, l
        for (j = 0, l = modifiers.length; j < l; j++) {
            switch (modifiers.charAt(j)) {
                case ' ':
                case '0':
                    padChar = modifiers.charAt(j)
                    break
                case '+':
                    positiveNumberPrefix = '+'
                    break
                case '-':
                    leftJustify = true
                    break
                case "'":
                    if (j + 1 < l) {
                        padChar = modifiers.charAt(j + 1)
                        j++
                    }
                    break
            }
        }
        if (!minWidth) {
            minWidth = 0
        } else {
            minWidth = +minWidth
        }
        if (!isFinite(minWidth)) {
            throw new Error('Width must be finite')
        }
        if (!precision) {
            precision = (specifier === 'd') ? 0 : 'fFeE'.indexOf(specifier) > -1 ? 6 : undefined
        } else {
            precision = +precision
        }
        if (argIndex && +argIndex === 0) {
            throw new Error('Argument number must be greater than zero')
        }
        if (argIndex && +argIndex >= args.length) {
            throw new Error('Too few arguments')
        }
        value = argIndex ? args[+argIndex] : args[i++]
        switch (specifier) {
            case '%':
                return '%'
            case 's':
                return _formatString(value + '', leftJustify, minWidth, precision, padChar)
            case 'c':
                return _formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, padChar)
            case 'b':
                return _formatBaseX(value, 2, leftJustify, minWidth, precision, padChar)
            case 'o':
                return _formatBaseX(value, 8, leftJustify, minWidth, precision, padChar)
            case 'x':
                return _formatBaseX(value, 16, leftJustify, minWidth, precision, padChar)
            case 'X':
                return _formatBaseX(value, 16, leftJustify, minWidth, precision, padChar)
                    .toUpperCase()
            case 'u':
                return _formatBaseX(value, 10, leftJustify, minWidth, precision, padChar)
            case 'i':
            case 'd':
                number = +value || 0
                // Plain Math.round doesn't just truncate
                number = Math.round(number - number % 1)
                prefix = number < 0 ? '-' : positiveNumberPrefix
                value = prefix + _pad(String(Math.abs(number)), precision, '0', false)
                if (leftJustify && padChar === '0') {
                    // can't right-pad 0s on integers
                    padChar = ' '
                }
                return justify(value, prefix, leftJustify, minWidth, padChar)
            case 'e':
            case 'E':
            case 'f': // @todo: Should handle locales (as per setlocale)
            case 'F':
            case 'g':
            case 'G':
                number = +value
                prefix = number < 0 ? '-' : positiveNumberPrefix
                method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(specifier.toLowerCase())]
                textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(specifier) % 2]
                value = prefix + Math.abs(number)[method](precision)
                return justify(value, prefix, leftJustify, minWidth, padChar)[textTransform]()
            default:
                // unknown specifier, consume that char and return empty
                return ''
        }
    }
    try {
        return format.replace(regex, doFormat)
    } catch (err) {
        return false
    }
}

function guid () {
    // copyright (c) Omer Cansizoglu
    // RFC4122: The version 4 UUID is meant for generating UUIDs from truly-random or
    // pseudo-random numbers.
    // The algorithm is as follows:
    //     Set the two most significant bits (bits 6 and 7) of the
    //        clock_seq_hi_and_reserved to zero and one, respectively.
    //     Set the four most significant bits (bits 12 through 15) of the
    //        time_hi_and_version field to the 4-bit version number from
    //        Section 4.1.3. Version4
    //     Set all the other bits to randomly (or pseudo-randomly) chosen
    //     values.
    // UUID                   = time-low "-" time-mid "-"time-high-and-version "-"clock-seq-reserved and low(2hexOctet)"-" node
    // time-low               = 4hexOctet
    // time-mid               = 2hexOctet
    // time-high-and-version  = 2hexOctet
    // clock-seq-and-reserved = hexOctet:
    // clock-seq-low          = hexOctet
    // node                   = 6hexOctet
    // Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    // y could be 1000, 1001, 1010, 1011 since most significant two bits needs to be 10
    // y values are 8, 9, A, B
    let guidHolder = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
    let hex = '0123456789abcdef';
    let r = 0;
    let guidResponse = "";
    for (let i = 0; i < 36; i++) {
        if (guidHolder[i] !== '-' && guidHolder[i] !== '4') {
            // each x and y needs to be random
            r = Math.random() * 16 | 0;
        }
        if (guidHolder[i] === 'x') {
            guidResponse += hex[r];
        } else if (guidHolder[i] === 'y') {
            // clock-seq-and-reserved first hex is filtered and remaining hex values are random
            r &= 0x3; // bit and with 0011 to set pos 2 to zero ?0??
            r |= 0x8; // set pos 3 to 1 as 1???
            guidResponse += hex[r];
        } else {
            guidResponse += guidHolder[i];
        }
    }

    return guidResponse;
}
