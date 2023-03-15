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

function message(message, caption, timeout) {
    timeout = timeout?timeout:15;
    toastr.info(message, caption?caption:i18n("message"), {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-bottom-right",
        "preventDuplicates": false,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout?(timeout * 1000):"0",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    });
}

function warning(message, caption, timeout) {
    timeout = timeout?timeout:15;
    toastr.warning(message, caption?caption:i18n("warning"), {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-bottom-right",
        "preventDuplicates": false,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout?(timeout * 1000):"0",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    });
}

function error(message, caption, timeout) {
    timeout = timeout?timeout:15;
    toastr.error(message, caption?caption:i18n("error"), {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-bottom-right",
        "preventDuplicates": false,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout?(timeout * 1000):"0",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    });
}

function mConfirm(body, title, button, callback) {
    if (!title) {
        title = i18n("confirm");
    }
    $('#confirmModalLabel').html(title);
    $('#confirmModalBody').html(body);
    let bc = 'btn-primary';
    button = button.split(':');
    if (button.length === 2) {
        bc = 'btn-' + button[0];
        button = button[1];
    } else {
        button = button[0];
    }
    $('#confirmModalButton').removeClass('btn-primary btn-secondary btn-success btn-danger btn-warning btn-info btn-light btn-dark btn-link').addClass(bc).html(button).off('click').on('click', () => {
        $('#confirmModal').modal('hide');
        if (typeof callback == 'function') callback();
    });
    autoZ($('#confirmModal')).modal('show');
    xblur();
}

function mYesNo(body, title, callbackYes, callbackNo, yes, no) {
    if (!title) {
        title = i18n("confirm");
    }
    $('#yesnoModalLabel').html(title);
    $('#yesnoModalBody').html(body);
    $('#yesnoModalButtonYes').html(yes?yes:i18n("yes")).off('click').on('click', () => {
        $('#yesnoModal').modal('hide');
        if (typeof callbackYes == 'function') callbackYes();
    });
    $('#yesnoModalButtonNo').html(no?no:i18n("no")).off('click').on('click', () => {
        $('#yesnoModal').modal('hide');
        if (typeof callbackNo == 'function') callbackNo();
    });
    autoZ($('#yesnoModal')).modal('show');
    xblur();
}

function mAlert(body, title, callback, title_button, main_button) {
    if (!title) {
        title = i18n("message");
    }
    if (title.toLowerCase().indexOf(i18n("error").toLowerCase()) >= 0) {
        title = '<span class="text-danger">' + title + '</span>';
    }
    if (title.toLowerCase().indexOf(i18n("warning").toLowerCase()) >= 0) {
        title = '<span class="text-warning">' + title + '</span>';
    }
    if (title.toLowerCase().indexOf(i18n("message").toLowerCase()) >= 0) {
        title = '<span class="text-success">' + title + '</span>';
    }
    let l = $('#alertModalLabel').html(title);
    if (title_button) {
        l.next().remove();
        l.parent().append($(title_button));
    }
    $('#alertModalBody').html(body);
    if (main_button) {
        $('#alertModalButton').html(main_button);
    } else {
        $('#alertModalButton').html(i18n("ok"));
    }
    $('#alertModalButton').off('click').on('click', (e) => {
        $('#alertModal').modal('hide');
        if (typeof callback == 'function') callback();
        e.stopPropagation();
    });
    autoZ($('#alertModal')).modal('show');
    xblur();
}

function modal(body) {
    $("#modalBody").html(body);
    xblur();
    return autoZ($('#modal')).modal('show');
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

function loadingStart() {
    autoZ($('#loading').modal({
        backdrop: 'static',
        keyboard: false,
    }));
}

function loadingDone(stayHidden) {
    xblur();

    $('#loading').modal('hide');

    if (stayHidden === true) {
        $('#app').addClass("invisible");
    } else {
        $('#app').removeClass("invisible");
    }

    if (parseInt($.cookie('_ls_collapse'))) {
        $(document.body).addClass('sidebar-collapse');
    } else {
        $(document.body).removeClass('sidebar-collapse');
    }

    $(window).resize();
}

function timeoutStart() {
    autoZ($('#timeout').modal({
        backdrop: 'static',
        keyboard: false,
    }));
    $('.timeout-animate').each(function () {
        this.beginElement();
    });
}

function timeoutDone() {
    $('#timeout').modal('hide');
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

function nl2br(str) {
    if (str && typeof str == "string") {
        return str.split("\n").join("<br />");
    } else {
        return "";
    }
}

function i18n(msg, ...args) {
    try {
        let t = msg.split(".");
        if (t.length > 2) {
            let t_ = [];
            t_[0] = t.shift();
            t_[1] = t.join(".");
            t = t_;
        }
        let loc;
        if (t.length === 2) {
            loc = lang[t[0]][t[1]];
        } else {
            loc = lang[t[0]];
        }
        if (loc) {
            if (typeof loc === "object" && Array.isArray(loc)) {
                loc = nl2br(loc.join("\n"));
            }
            loc = sprintf(loc, ...args);
        }
        if (!loc) {
            if (t[0] === "errors") {
                return t[1];
            } else {
                return msg;
            }
        }
        return loc;
    } catch (_) {
        return msg;
    }
}

function leftSide(button, title, target, group, withibleOnlyWhenActive) {
    if (group != mainSidebarGroup && !mainSidebarFirst) {
        $("#leftside-menu").append(`
            <li class="nav-item"><hr class="border-top" style="opacity: 15%"></li>
        `);
    }

    let [ route ] = hashParse();

    $("#leftside-menu").append(`
        <li class="nav-item ${mainSidebarFirst?"mt-1":""} ${withibleOnlyWhenActive?" withibleOnlyWhenActive":""}" target="${target}" title="${escapeHTML(title)}"${(withibleOnlyWhenActive && target !== "#" + route.split('.')[0])?" style='display: none;'":""}>
            <a href="${target}" class="nav-link${(target === "#" + route.split('.')[0])?" active":""}">
                <i class="${button} nav-icon"></i>
                <p class="text-nowrap">${title}</p>
            </a>
        </li>
    `);

    mainSidebarGroup = group;
    mainSidebarFirst = false;
}

function loadModule() {
    let module = moduleLoadQueue.shift();
    if (!module) {
        hashChange();
        onhashchange = hashChange;
        $("#app").show();
    } else {
        let l = $.cookie("_lang");
        if (!l) {
            l = config.defaultLanguage;
        }
        if (!l) {
            l = "ru";
        }
        $.get("modules/" + module + "/i18n/" + l + ".json", i18n => {
            if (i18n.errors) {
                if (!lang.errors) {
                    lang.errors = {};
                }
                lang.errors = {...lang.errors, ...i18n.errors};
                delete i18n.errors;
            }
            if (i18n.methods) {
                if (!lang.methods) {
                    lang.methods = {};
                }
                lang.methods = {...lang.methods, ...i18n.methods};
                delete i18n.methods;
            }
            lang[module] = i18n;
        }).always(() => {
            $.getScript("modules/" + module + "/" + module + ".js");
        });
    }
}

function moduleLoaded(module, object) {
    let m = module.split(".");

    if (!modules[module] && m.length === 1 && object) {
        modules[module] = object;
    }

    if (m.length === 2 && modules[m[0]] && object) {
        modules[m[0]][m[1]] = object;
    }

    if (m.length === 1) {
        loadModule();
    }
}

function loadSubModules(parent, subModules, doneOrParentObject) {
    if (!modules[parent] && typeof doneOrParentObject === "object") {
        modules[parent] = doneOrParentObject;
    }
    let module = subModules.shift();
    if (!module) {
        if (typeof doneOrParentObject === "function") {
            doneOrParentObject();
        }
        if (typeof doneOrParentObject === "object") {
            moduleLoaded(parent, doneOrParentObject);
        }
    } else{
        $.getScript("modules/" + parent + "/" + module + ".js").
        done(() => {
            loadSubModules(parent, subModules, doneOrParentObject);
        }).
        fail(FAIL);
    }
}

function formatBytes(bytes) {
    let u = 0;
    for (; bytes > 1024; u++) bytes /= 1024;
    return Math.round(bytes) + ' ' + [ 'B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y' ][u];
}

function subTop(html) {
    $("#subTop").html(`<div class="info-box mt-2 mb-1" style="min-height: 0px;"><div class="info-box-content"><span class="info-box-text">${html}</span></div></div>`);
}

function hashParse() {
    let hash = location.href.split('#')[1];
    hash = hash?('#' + hash):'';

    $('.dropdownMenu').collapse('hide');
    $('.modal').modal('hide');

    let params = {};
    let route;

    try {
        hash = hash.split('#')[1].split('&');
        route = hash[0]?hash[0]:"default";
        for (let i = 1; i < hash.length; i++) {
            let sp = hash[i].split('=');
            params[sp[0]] = sp[1]?decodeURIComponent(sp[1]):true;
        }
    } catch (e) {
        route = "default";
    }

    return [ route, params, hash ];
}

function escapeHTML(str) {
    if (str && typeof str == "string") {
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

        return str.replace(regex, function(m) {
            return '&' + escapeChars[m] + ';';
        });
    } else {
        return str;
    }
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

function ttDate(date, dateOnly) {
    if (date) {
        date = new Date(date * 1000);
        if (dateOnly) {
            return date.toLocaleDateString();
        } else {
            return date.toLocaleDateString() + " " + pad2(date.getHours()) + ":" + pad2(date.getMinutes());
        }
    } else {
        return "&nbsp;"
    }
}

function utf8_to_b64(str) {
    return window.btoa(unescape(encodeURIComponent(str)));
}

function b64_to_utf8(str) {
    return decodeURIComponent(escape(window.atob(str)));
}

function trimStr(str, len) {
    if (!len) {
        len = 19;
    }
    let sub = Math.floor((len - 3) / 2);
    if (str.length > len) {
        return str.substring(0, sub) + "..." + str.substring(str.length - sub);
    } else {
        return str;
    }
}