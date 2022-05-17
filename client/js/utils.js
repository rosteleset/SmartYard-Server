function setFavicon(icon, unreaded) {
    if (typeof unreaded == 'undefined') {
        unreaded = 0;
    }

    if ($.browser.chrome) {
        $('#favicon').attr('href', icon.src);
    } else {
        document.head || (document.head = document.getElementsByTagName('head')[0]);
        let link = document.createElement('link');
        let oldLink = document.getElementById('dynamic-favicon');
        link.id = 'dynamic-favicon';
        link.rel = 'shortcut icon';
        link.href = icon.src;
        if (oldLink){
            document.head.removeChild(oldLink);
        }
        document.head.appendChild(link);
    }

    window.badge = new Favico({ animation: 'none', bgColor: '#000000' });

    if (unreaded) {
        if (unreaded <= 9) {
            window.badge.badge(unreaded);
        } else {
            window.badge.badge('9+');
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

function mText(title, text, callback, singleline) {
    $('#inputTextLabel').html(title);
    if (singleline === true) {
        $('#inputTextBody').hide();
        $('#inputTextLine').val(text).show();
    } else {
        $('#inputTextBody').val(text).show();
        $('#inputTextLine').hide();
    }
    $('#inputTextButton').off('click').on('click', () => {
        $('#inputText').modal('hide');
        let r = $.trim((singleline === true)?$('#inputTextLine').val():$('#inputTextBody').val());
        if (typeof callback == 'function' && r) {
            callback(r);
        }
    });
    autoZ($('#inputText').modal('show'));
    if (singleline === true) {
        $('#inputTextLine').focus();
    } else {
        $('#inputTextBody').focus();
    }
    xblur();
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
    if (button.length == 2) {
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
    $('#alertModalLabel').html(title);
    $('#alertModalLabel').next().remove();
    if (title_button) {
        $('#alertModalLabel').parent().append($(title_button));
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
}

function xblur() {
    console.log("xblur");
    setTimeout(() => {
        $('a input button').blur();
    }, 100);
}

function autoZ(target) {
    let maxZ = Math.max.apply(null, $.map($('body > *:visible'), function(e) {
        if (e == target) {
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

function loadingDone(stay_hidden) {
    $('#loading').modal('hide');
    if (stay_hidden) {
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

function i18n(msg, ...args) {
    try {
        let t = msg.split(".");
        if (t.length > 2) {
            return msg;
        }
        let loc;
        if (t.length === 2) {
            loc = sprintf(window.lang[t[0]][t[1]], ...args);
        } else {
            loc = sprintf(window.lang[t[0]], ...args);
        }
        if (!loc) {
            return msg;
        }
        return loc;
    } catch (_) {
        return msg;
    }
}
