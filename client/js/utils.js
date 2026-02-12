const phoneUtil = libphonenumber.PhoneNumberUtil.getInstance();

const additionalLinks = [
    "fb",
    "vless",
    "vmess",
    "ss",
    "tg",
];

const rbtMd = new remarkable.Remarkable({
    html: true,
    quotes: '“”‘’',

    highlight: function (str, language) {
        if (language && hljs.getLanguage(language)) {
            try {
                let h = hljs.highlight(str, { language }).value;
                return h;
            } catch (err) {
                console.log(err);
            }
        }

        try {
            return hljs.highlightAuto(str).value;
        } catch (err) {
            console.log(err);
        }

        return ''; // use external default escaping
    }
});

rbtMd.core.ruler.enable([
    'abbr'
]);

rbtMd.block.ruler.enable([
    'footnote',
    'deflist'
]);

rbtMd.inline.ruler.enable([
    'footnote_inline',
    'ins',
    'mark',
    'sub',
    'sup'
]);

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
            return "<span title='" + escapeHTML(str) + "'>" + str.substring(0, sub) + "..." + str.substring(str.length - sub) + "</span>";
//            return str.substring(0, sub) + "..." + str.substring(str.length - sub);
        }
    } else {
        return str;
    }
}

function setFavicon(icon, unreaded, bgColor) {
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

    badge = new Favico({ animation: 'none', bgColor: bgColor ? bgColor : '#000000' });

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
    if (target) {
//        target.css('z-index', 1);
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

function telify(input) {
    let l = lStore("_lang");
    if (!l) {
        l = config.defaultLanguage;
    }
    if (!l) {
        l = "ru";
    }

    return input.replaceAll(new RegExp(config.regExp.phone, 'g'), function(match) {
        try {
            let pre = '';
            let post = '';
            if (match[0] < '0' || match[0] > '9') {
                pre = match[0];
                match = match.substring(1);
            }
            if (match[match.length - 1] < '0' || match[match.length - 1] > '9') {
                post = match[match.length - 1];
                match = match.substring(0, match.length - 1);
            }
            let phoneObject = phoneUtil.parse(trim(match), l);
            return pre + '<a href="tel:' + phoneUtil.format(phoneObject, libphonenumber.PhoneNumberFormat.E164) + '" target="_blank">' + phoneUtil.format(phoneObject, libphonenumber.PhoneNumberFormat.NATIONAL) + '</a>' + post;
        } catch (e) {
            return match;
        }
    });
}

function convertLinks(input) {
    // https://linkify.js.org/

    let options = {
        defaultProtocol: "https",
        target: "_blank",
    };

    if (modules.tt && modules.tt.issueRegExp) {
        return linkifyHtml(telify(input), options).replaceAll(modules.tt.issueRegExp, m => {
            return `<a target='_blank' href='${navigateUrl('tt.issue', { 'issue': m })}'>${m}</a>`;
        });
    } else {
        return linkifyHtml(telify(input), options);
    }
}

function getMonthDifference(startDate, endDate) {
    return endDate.getMonth() - startDate.getMonth() + 12 * (endDate.getFullYear() - startDate.getFullYear());
}

function findBootstrapEnvironment() {
    let envs = [ 'xs', 'sm', 'md', 'lg', 'xl' ];

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

    function setValue(root, path, value) {
        if (path.length > 1) {
            let  dir = path.shift();
            if (typeof root[dir] == 'undefined') {
                root[dir] = path[0] == '' ? [] : {};
            }
            setValue(root[dir], path, value);
        } else {
            if (root instanceof Array) {
                root.push(value);
            } else {
                root[path] = value;
            }
        }
    }

    if (query) {
        if (query[0] == "?") {
            query = query.substring(1);
        }

        if (query[0] == "&") {
            query = query.substring(1);
        }

        let nvp = query.split('&');
        let data = {};

        for (let i = 0; i < nvp.length; i++) {
            let pair = nvp[i].split('=');
            let name = decodeURIComponent(pair[0]);
            let value = decodeURIComponent(pair[1]);

            try {
                let path = name.match(/(^[^\[]+)(\[.*\]$)?/);
                let first = path[1];
                if (path[2]) {
                    path = path[2].match(/(?=\[(.*)\]$)/)[1].split('][')
                } else {
                    path = [];
                }
                path.unshift(first);
                setValue(data, path, value);
            } catch (e) {
                //
            }
        }

        return data;
    } else {
        return {};
    }
}

function refreshUrl(options) {
    let [ route, params ] = hashParse();

    params["_"] = Math.random();

    if (options && options.exclude) {
        for (let i in options.exclude) {
            delete params[options.exclude[i]];
        }
    }

    if (options && options.set) {
        for (let i in options.set) {
            params[i] = options.set[i];
        }
    }

    return "?#" + route + "&" + $.param(params);
}

function navigateUrl(route, params, options) {
    if (!params) {
        params = {};
    }

    if (!params["_"]) {
        params["_"] = Math.random();
    }

    if (options && options.exclude) {
        for (let i in options.exclude) {
            delete params[options.exclude[i]];
        }
    }

    if (!options || !options.noClean) {
        for (let i in params) {
            if (params[i] === false || params[i] === undefined) {
                delete params[i];
            }
        }
    }

    if (options && options.noRefresh) {
        delete params["_"];
    }

    route = $.trim(route);
    while (route && (route.charAt(0) == "?" || route.charAt(0) == "#")) {
        route = route.substring(1);
    }

    if (options && options.run) {
        loadingStart();
        window.location.href = "?#" + route + "&" + $.param(params);
    } else {
        return "?#" + route + ((params && Object.keys(params).length) ? ("&" + $.param(params)) : "");
    }
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

function pathToObject(parent, path) {
    let result = parent;

    path = path.split(".");

    while (path.length) {
        let k = path.shift();
        result = result[k];
    }

    return result;
}

function ildc() {
    let mw = 0;

    $(".ildc").each(function () {
        let w = parseInt($(this).css("width"), 10);
        if (w > mw) {
            mw = w;
        }
    });

    mw += "px"

    $(".ildc").css("width", mw);
}

function buildTreeFromPaths(data, delimiter = '.', ) {
    let root = [];

    data.forEach(item => {
        let levels = item.tree ? item.tree.split(delimiter) : [];
        let currentLevel = root;

        levels.forEach(folderPath => {
            let folder = currentLevel.find(node => node.path === folderPath);

            if (!folder) {
                folder = {
                    path: folderPath,
                    tree: item.tree,
                    name: item.name,
                    text: item.text,
                    id: item.id,
                    state: item.state,
                    children: []
                };
                currentLevel.push(folder);
            }

            currentLevel = folder.children;
        });
    });

    return root;
}

function sqlLike(text, pattern) {
    return (new RegExp(`^${pattern.replace(/%/g, '.*').replace(/_/g, '.').replace(/\./g, '\\.')}$`)).test(text);
}

function systemColor(text) {
    let m = md5(text);
    let s = 0;

    for (let i = 0; i < m.length; i++) {
        s += parseInt(m[i], 16);
    }

    s = s % (systemColors.length - 1);

    return systemColors[s + 1];
}

function rbtMdRender(str) {
    return rbtMd.render(str);
}

function isObject(item) {
    return (item && typeof item === 'object' && !Array.isArray(item));
}

function mergeDeep(target, ...sources) {
    if (!sources.length) return target;

    const source = sources.shift();

    if (!target) {
        target = {}
    }

    if (isObject(target) && isObject(source)) {
        for (const key in source) {
            if (isObject(source[key])) {
                if (!target[key]) Object.assign(target, { [key]: {} });
                mergeDeep(target[key], source[key]);
            } else {
                Object.assign(target, { [key]: source[key] });
            }
        }
    }

    return mergeDeep(target, ...sources);
}

function getSafeWeekInfo(localeCode = navigator.language) {
    try {
        let locale = new Intl.Locale(localeCode);

        if (typeof locale.getWeekInfo === 'function') {
            return locale.getWeekInfo();
        }

        if (locale.weekInfo) {
            return locale.weekInfo;
        }
    } catch (e) {
        //
    }

    console.warn(`getWeekInfo is not supported for ${localeCode}`);

    return {
        firstDay: 1,
        weekend: [6, 7],
        minimalDays: 1
    };
}

function isWeekend(date, localeCode = navigator.language) {
    let { weekend } = getSafeWeekInfo(localeCode);

    return weekend.includes(date.getDay() === 0 ? 7 : date.getDay());
}

function date(format, timestamp) {
    let l = lang._code;
    let d = {
        "ru": date_ru,
        "en": date_en,
    };

    if (!d[l]) {
        d[l] = date_en;
    }

    return d[l](format, timestamp);
}

function forceReload() {
    window.onhashchange = hashChange;
    window.location.reload();
}

/*
async function encryptAsync(msg, pk) {
    await sodium.ready;

    return sodium.to_base64(sodium.crypto_box_seal(msg, sodium.from_base64(pk)));
}
*/

async function encryptAsync(msg, pk) {
    let binaryDerString = window.atob(pk);
    const binaryDer = new Uint8Array(binaryDerString.length);

    for (let i = 0; i < binaryDerString.length; i++) {
        binaryDer[i] = binaryDerString.charCodeAt(i);
    }

    let publicKey = await window.crypto.subtle.importKey(
        "spki",
        binaryDer,
        { name: "RSA-OAEP", hash: "SHA-1" },
        false,
        ["encrypt"]
    );

    const encrypted = await window.crypto.subtle.encrypt(
        { name: "RSA-OAEP" },
        publicKey,
        new TextEncoder().encode(msg)
    );

    return btoa(String.fromCharCode(...new Uint8Array(encrypted)));
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

jQuery.fn.click2 = function(single_click_callback, double_click_callback, timeout) {
    return this.each(function() {
        let clicks = 0, self = this;
        jQuery(this).click(event => {
            clicks++;
            if (clicks == 1) {
                setTimeout(() => {
                    if (clicks == 1) {
                        single_click_callback.call(self, event);
                    } else {
                        double_click_callback.call(self, event);
                    }
                    clicks = 0;
                }, timeout || 300);
            }
        });
    });
}

String.prototype.escapedSplit = function (delimeter) {
    let str = this;

    str = str.replaceAll(delimeter + delimeter, String.fromCharCode(27));
    let arr = str.split(delimeter);

    for (let i in arr) {
        arr[i] = arr[i].replaceAll(String.fromCharCode(27), delimeter);
    }

    return arr;
}

for (let i in additionalLinks) {
    linkify.registerCustomProtocol(additionalLinks[i]);
}

linkify.init();

window.addEventListener("drop", (e) => {
    if ([...e.dataTransfer.items].some((item) => item.kind === "file")) {
        e.preventDefault();
    }
});

window.addEventListener("dragover", (e) => {
    let fileItems = [...e.dataTransfer.items].filter(
        (item) => item.kind === "file",
    );

    if (fileItems.length > 0) {
        e.preventDefault();
    }
});
