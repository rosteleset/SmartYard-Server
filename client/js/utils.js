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

// button - "class(danger, warning, success....):текст"
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
    autoZ($('#confirmModal').modal('show'));
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
    autoZ($('#yesnoModal').modal('show'));
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
    autoZ($('#alertModal').modal('show'));
    xblur();
}

function modal(body) {
    $("#modalBody").html(body);
    autoZ($('#modal').modal('show'));
    xblur();

    return $('#modal');
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
            return parseInt($(e).css('z-index')) || 1;
        }
    }));

    target.css('z-index', maxZ + 1);

    return maxZ + 1;
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
    return str.split("\n").join("<br />");
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

function leftSide(button, title, target, separator, withibleOnlyWhenActive) {
    if (separator && !mainSidebarFirst) {
        $("#leftside-menu").append(`
            <li class="nav-item"><hr class="border-top" style="opacity: 15%"></li>
        `);
    }

    let [ route ] = hashParse();

    $("#leftside-menu").append(`
        <li class="nav-item ${mainSidebarFirst?"mt-1":""} ${withibleOnlyWhenActive?" withibleOnlyWhenActive":""}" target="${target}" title="${title}"${(withibleOnlyWhenActive && target !== "#" + route.split('.')[0])?" style='display: none;'":""}>
            <a href="${target}" class="nav-link${(target === "#" + route.split('.')[0])?" active":""}">
                <i class="${button} nav-icon"></i>
                <p class="text-nowrap">${title}</p>
            </a>
        </li>
    `);

    mainSidebarFirst = false;
}

function loadSubModules(parent, modules, done) {
    let module = modules.shift();
    if (!module) {
        done();
    } else{
        $.getScript("modules/" + parent + "/" + module + ".js").
        done(() => {
            loadSubModules(parent, modules, done);
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