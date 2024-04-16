function isEmpty(v) {
    let f = !!v;

    if (Array.isArray(v)) {
        f = f && v.length;
    }

    if (typeof v == "object" && !Array.isArray(v)) {
        f = f && Object.keys(v).length;
    }

    return !f;
}

function pad2(n) {
    return (n < 10 ? '0' : '') + n;
}

function ttDate(date, dateOnly, skip) {
    if (date) {
        date = new Date(date * 1000);
        if (dateOnly) {
            return date.toLocaleDateString();
        } else {
            return date.toLocaleDateString() + " " + pad2(date.getHours()) + ":" + pad2(date.getMinutes());
        }
    } else {
        if (skip) {
            return false;
        } else {
            return "&nbsp;"
        }
    }
}

function utf8_to_b64(str) {
    return window.btoa(unescape(encodeURIComponent(str)));
}

function b64_to_utf8(str) {
    return decodeURIComponent(escape(window.atob(str)));
}

function trimStr(str, len, abbr) {
    if (len < 0) {
        return str;
    }
    if (!len) {
        len = 33;
    }
    let sub = Math.floor((len - 3) / 2);
    if (str && str.length > len) {
        if (abbr) {
            return "<abbr title='" + escapeHTML(str) + "'>" + str.substring(0, sub) + "..." + str.substring(str.length - sub) + "</abbr>";
        } else {
            return str.substring(0, sub) + "..." + str.substring(str.length - sub);
        }
    } else {
        return str;
    }
}

function setFavicon(icon, unreaded) {
    if (typeof unreaded == 'undefined') {
        unreaded = 0;
    }

    if ($.browser.chrome) {
        $('#favicon').attr('href', icon);
    } else {
        document.head || (document.head = document.getElementsByTagName('head')[0]);
        let link = document.createElement('link');
        let oldLink = document.getElementById('dynamic-favicon');
        link.id = 'dynamic-favicon';
        link.rel = 'shortcut icon';
        link.href = icon;
        if (oldLink){
            document.head.removeChild(oldLink);
        }
        document.head.appendChild(link);
    }

    badge = new Favico({ animation: 'none', bgColor: '#000000' });

    if (unreaded) {
        if (unreaded <= 9 || !parseInt(unreaded)) {
            badge.badge(unreaded);
        } else {
            badge.badge('9+');
        }
    }
}

function xblur() {
    setTimeout(() => {
        $('a, input, button, .nav-item').blur();
    }, 100);
}

function autoZ(target) {
    let maxZ = Math.max.apply(null, $.map($('body > *:visible'), function(e) {
        if (e === target) {
            return 1;
        } else {
            // no great than 9999999
            let z = parseInt($(e).css('z-index'));
            if (z < 9999999) {
                return parseInt($(e).css('z-index')) || 1;
            } else {
                return 1;
            }
        }
    }));

    maxZ = Math.max(maxZ, 100500);

    if (target) {
        target.css('z-index', maxZ + 1);
    }

    return target;
}

function nl2br(str) {
    if (str && typeof str == "string") {
        return str.split("\n").join("<br />");
    } else {
        return "";
    }
}

function formatBytes(bytes) {
    let u = 0;
    for (; bytes > 1024; u++) bytes /= 1024;
    return Math.round(bytes) + [ 'B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y' ][u];
}

function escapeHTML(str) {
    if (typeof str == "undefined" || !str) {
        return "";
    }
    
    str = str.toString();

    let escapeChars = {
        '¢': 'cent',
        '£': 'pound',
        '¥': 'yen',
        '€': 'euro',
        '©':'copy',
        '®': 'reg',
        '<': 'lt',
        '>': 'gt',
        '"': 'quot',
        '&': 'amp',
        '\'': '#39'
    };

    let regexString = '[';

    for(let key in escapeChars) {
        regexString += key;
    }

    regexString += ']';

    let regex = new RegExp(regexString, 'g');

    let result = str.replace(regex, function(m) {
        return '&' + escapeChars[m] + ';';
    });

    return result;
}

function textRTrim(text) {
    text = text.split("\n");

    for (let i in text) {
        text[i] = text[i].trimRight()
    }

    return text.join("\n");
}

function parseIntEx(i) {
    i = parseInt(i);

    if (isNaN(i)) {
        return 0;
    } else {
        return i;
    }
}

function parseFloatEx(f, r) {
    f = parseFloat(`${f}`.toString().replaceAll(",", "."));

    if (r !== false && typeof r !== "undefined") {
        f = Math.round(f * (10 ** r)) / (10 ** r);
    }

    if (isNaN(f)) {
        return 0;
    } else {
        return f;
    }
}

/*
function convertLinks(input) {
    let text = input;
    const aLink = [];
    const linksFound = text.match(/(?:www|https?)[^(\s|\)|\])]+/g);

    if (linksFound != null) {
        for (let i = 0; i < linksFound.length; i++) {
            let replace = linksFound[i];

            if (!(linksFound[i].match(/(http(s?)):\/\//))) {
                replace = 'http://' + linksFound[i];
            }

            let linkText = replace.split('/')[2];

            if (linkText.substring(0, 3) == 'www') {
                linkText = linkText.replace('www.', '');
            }

            if (linkText.match(/youtu/)) {
                const youtubeID = replace.split('/').slice(-1)[0].split('=')[1];

                if (youtubeID === undefined || youtubeID === '') {
                    aLink.push('<a onclick="xblur()" href="' + replace + '" target="_blank">' + linkText + '</a>');
                } else {
                    aLink.push('<span class="video-wrapper"><iframe src="https://www.youtube.com/embed/' + youtubeID + '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></span>');
                }
            } else {
                aLink.push('<a onclick="xblur()" href="' + replace + '" target="_blank">' + linkText + '</a>');
            }
            text = text.split(linksFound[i]).map(item => {
                return aLink[i].includes('iframe') ? item.trim() : item;
            }).join(aLink[i]);
        }

        return text;
    } else {
        return input;
    }
}
*/

function convertLinks(input) {
    // https://linkify.js.org/
    let options = {
        defaultProtocol: "https",
        target: "_blank",
    };
    return linkifyHtml(input, options);
}

function getMonthDifference(startDate, endDate) {
    return endDate.getMonth() - startDate.getMonth() + 12 * (endDate.getFullYear() - startDate.getFullYear());
}

function findBootstrapEnvironment() {
    let envs = ['xs', 'sm', 'md', 'lg', 'xl'];

    let el = document.createElement('div');
    document.body.appendChild(el);

    let curEnv = envs.shift();

    for (let env of envs.reverse()) {
        el.classList.add(`d-${env}-none`);

        if (window.getComputedStyle(el).display === 'none') {
            curEnv = env;
            break;
        }
    }

    document.body.removeChild(el);
    return curEnv;
}

Object.defineProperty(Array.prototype, "assoc", {
    value: function (key, target, val) {
        let arr = this;

        for (let i in arr) {
            if (arr[i][key] == target) {
                if (val) {
                    return arr[i][val];
                } else {
                    return arr[i];
                }
            }
        }
    }
});

