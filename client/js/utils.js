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

    for (let key in escapeChars) {
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

$.deparam = function (query) {
    let setValue = function (root, path, value) {
        if (path.length > 1) {
            let  dir = path.shift();
            if (typeof root[dir] == 'undefined') {
                root[dir] = path[0] == '' ? [] : {};
            }
            arguments.callee(root[dir], path, value);
        } else {
            if (root instanceof Array) {
                root.push(value);
            } else {
                root[path] = value;
            }
        }
    };

    let nvp = query.split('&');
    let data = {};

    for (let i = 0 ; i < nvp.length ; i++) {
        let pair = nvp[i].split('=');
        let name = decodeURIComponent(pair[0]);
        let value = decodeURIComponent(pair[1]);

        let path = name.match(/(^[^\[]+)(\[.*\]$)?/);
        let first = path[1];
        if (path[2]) {
            path = path[2].match(/(?=\[(.*)\]$)/)[1].split('][')
        } else {
            path = [];
        }
        path.unshift(first);
        setValue(data, path, value);
    }

    return data;
}

function refreshUrl() {
    let [ route, params ] = hashParse();

    params["_"] = Math.random();

    return "?#" + route + "&" + $.param(params);
}

function navigateUrl(route, params) {
    if (!params) {
        params = {};
    }
    params["_"] = Math.random();

    return "?#" + route + "&" + $.param(params);
}

function object2array(obj) {
    if (typeof obj !== "object") {
        return obj;
    }

    if (Array.isArray(obj)) {
        return obj;
    }

    let possible = true;

    let keys = Object.keys(obj);

    for (let i in keys) {
        if (i != keys[i]) {
            possible = false;
            break;
        }
    }

    if (!possible) {
        return obj;
    }

    let arr = [];

    for (let i in keys) {
        arr.push(obj[keys[i]]);
    }

    return arr;
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
