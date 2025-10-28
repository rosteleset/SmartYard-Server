var backToTopButton = false;
var backToTopTicking = false;
var windowResizeTicking = false;

function message(message, caption, timeout) {
    timeout = timeout?timeout:15;
    toastr.info(message, caption?caption:i18n("message"), {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-bottom-right",
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout ? (timeout * 1000) : "0",
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
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout ? (timeout * 1000) : "0",
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
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": timeout ? (timeout * 1000) : "0",
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

let mYesNoTimeout = 0;

function mYesNo(body, title, callbackYes, callbackNo, yes, no, timeout) {
    if (mYesNoTimeout) {
        clearTimeout(mYesNoTimeout);
    }

    if (!title) {
        title = i18n("confirm");
    }

    $('#yesnoModalLabel').html(title);
    $('#yesnoModalBody').html(body);
    let t = yes?yes:i18n("yes");
    t = t.charAt(0).toUpperCase() + t.substring(1);
    $('#yesnoModalButtonYes').html(t).off('click').on('click', () => {
        $('#yesnoModal').modal('hide');
        if (typeof callbackYes == 'function') callbackYes();
    });
    t = no?no:i18n("no");
    t = t.charAt(0).toUpperCase() + t.substring(1);
    $('#yesnoModalButtonNo').html(t).off('click').on('click', () => {
        $('#yesnoModal').modal('hide');
        if (typeof callbackNo == 'function') callbackNo();
    });

    autoZ($('#yesnoModal')).modal('show');
    xblur();

    if (timeout) {
        mYesNoTimeout = setTimeout(() => {
            mYesNoTimeout = 0;
            $('#yesnoModal').modal('hide');
        }, timeout);
    }
}

function mAlert(body, title, callback, titleButton, mainButton) {
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
    if (titleButton) {
        l.next().remove();
        l.parent().append($(titleButton));
    }
    $('#alertModalBody').html(body);
    if (mainButton) {
        $('#alertModalButton').html(mainButton);
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

function mPrompt(prompt, title, value, callback, titleButton, mainButton) {
    if (!title) {
        title = i18n("message");
    }
    let l = $('#promptModalLabel').html(title);
    if (titleButton) {
        l.next().remove();
        l.parent().append($(titleButton));
    }
    $('#promptModalBody').html(prompt);
    $('#promptModalInput').val(value);
    if (mainButton) {
        $('#promptModalButton').html(mainButton);
    } else {
        $('#ptomptModalButton').html(i18n("ok"));
    }
    $('#promptModalButton').off('click').on('click', (e) => {
        $('#promptModal').modal('hide');
        if (typeof callback == 'function') callback($('#promptModalInput').val());
        e.stopPropagation();
    });
    autoZ($('#promptModal')).modal('show');
    xblur();
    setTimeout(() => {
        $('#promptModalInput').focus();
    }, 100);
}

function modal(body) {
    $("#modalBody").html(body);
    xblur();
    return autoZ($('#modal')).modal({
        backdrop: 'static',
        keyboard: true,
        show: true,
    });
}

function FAIL(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        if (response.getResponseHeader("x-last-error")) {
            error(i18n("errors." + response.responseJSON.error, i18n("errors." + response.getResponseHeader("x-last-error"))), i18n("error"), 30);
        } else {
            error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
        }
        if (response.responseJSON.error == "tokenNotFound") {
            lStore("_token", null);
            setTimeout(() => {
                window.location.reload();
            }, 5000);
            return $.Deferred().reject(response.responseJSON.error);
        }
    } else {
        if (response && response.getResponseHeader("x-last-error")) {
            error(i18n("errors.unknown" + " [" + i18n("errors." + response.getResponseHeader("x-last-error")) + "]"), i18n("errorCode", response.status), 30);
        } else {
            error(i18n("errors.unknown"), i18n("errorCode", response ? response.status : i18n("unknown")), 30);
        }
    }
}

function FAILPAGE(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        if (response.getResponseHeader("x-last-error")) {
            error(i18n("errors." + response.responseJSON.error, i18n("errors." + response.getResponseHeader("x-last-error"))), i18n("error"), 30);
            pageError(i18n("errors." + response.responseJSON.error, i18n("errors." + response.getResponseHeader("x-last-error"))));
        } else {
            error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
            pageError(i18n("errors." + response.responseJSON.error));
        }
    } else {
        if (response && response.getResponseHeader("x-last-error")) {
            error(i18n("errors.unknown" + " [" + i18n("errors." + response.getResponseHeader("x-last-error")) + "]"), i18n("errorCode", response.status), 30);
            pageError(i18n("errors." + response.getResponseHeader("x-last-error")));
        } else {
            error(i18n("errors.unknown"), i18n("errorCode", response ? response.status : i18n("unknown")), 30);
            pageError();
        }
    }
    loadingDone();
}

function loadingStart() {
    requestAnimationFrame(() => {
        document.body.style.cursor = 'wait';

        autoZ($('#loading').modal({
            backdrop: 'static',
            keyboard: false,
        }));
    });
}

function loadingDone(stayHidden) {
    requestAnimationFrame(() => {
        document.body.style.cursor = 'default';

        xblur();

        $('#loading').modal('hide');

        if (stayHidden === true) {
            $('#app').addClass("invisible");
        } else {
            $('#app').removeClass("invisible");
        }

        $(window).resize();
    });
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

function page404() {
    $("#mainForm").html("");
    $("#altForm").hide();
    loadingDone();
    document.title = `${i18n("windowTitle")} :: 404`;
    $("#page404").html(`
        <section class="content">
            <div class="error-page">
                <img src="img/404.png" style="border: none; width: 200px; height: 200px;">
                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger"></i>${i18n("errors.404caption")}</h3>
                    <p>${i18n("errors.404message")}</p>
                </div>
            </div>
        </section>
    `).show();
}

function pageError(error) {
    $("#mainForm").html("");
    $("#altForm").hide();
    subTop();
    loadingDone();
    document.title = `${i18n("windowTitle")} :: ${i18n("error")}`;
    $("#pageError").html(`
        <section class="content">
            <div class="error-page">
                <h2 class="headline text-danger mr-4"> Error</h2>
                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger"></i>${i18n("error")}</h3>
                    <p>${error?error:i18n("errors.unknown")}</p>
                </div>
            </div>
        </section>
    `).show();
}

function subTop(html) {
    if (html) {
        $("#subTop").html(`<div class="info-box mt-2 mb-1" style="min-height: 0px;"><div class="info-box-content"><span class="info-box-text">${html}</span></div></div>`).show();
    } else {
        $("#subTop").hide();
    }
}

function leftSide(button, title, target, group, wisibleOnlyWhenActive) {
    if (group != mainSidebarGroup && !mainSidebarFirst) {
        $("#leftside-menu").append(`
            <li class="nav-item"><hr class="border-top" style="opacity: 15%"></li>
        `);
    }

    let route = hashParse("route");

    let id = "id-" + md5(guid());

    let style;

    if (mainSidebarFirst) {
        style = "margin-top: 11px;"
    } else {
        style = "margin-top: 3px;";
    }

    if (wisibleOnlyWhenActive && target !== "#" + route.split('.')[0]) {
        style += "display: none;";
    }

    $("#leftside-menu").append(`
        <li id="${id}" class="nav-item${wisibleOnlyWhenActive?" wisibleOnlyWhenActive":""}" data-target="${target}" title="${escapeHTML(title)}" style="${style}">
            <a href="${target}" data-href="${target}" class="nav-link ${(target === "#" + (route ? route.split('.')[0] : "")) ? "active" : ""}">
                <i class="${button} nav-icon"></i>
                <p class="text-nowrap">${title}</p>
            </a>
        </li>
    `);

    mainSidebarGroup = group;
    mainSidebarFirst = false;

    return id;
}

function leftSideClick(button, title, group, click) {
    if (group != mainSidebarGroup && !mainSidebarFirst) {
        $("#leftside-menu").append(`
            <li class="nav-item"><hr class="border-top" style="opacity: 15%"></li>
        `);
    }

    let id = "id-" + md5(guid());

    let style;

    if (mainSidebarFirst) {
        style = "margin-top: 11px;"
    } else {
        style = "margin-top: 3px;";
    }

    $("#leftside-menu").append(`
        <li id="${id}" class="nav-item" title="${escapeHTML(title)}" style="${style}">
            <a class="nav-link" href="#" onclick="xblur(); return false;">
                <i class="${button} nav-icon"></i>
                <p class="text-nowrap">${title}</p>
            </a>
        </li>
    `);

    $("#" + id).off("click").on("click", click);

    mainSidebarGroup = group;
    mainSidebarFirst = false;

    return id;
}

$(document).on('select2:open', '.select2', function () {
    setTimeout(() => {
        try {
            document.querySelector(`[aria-controls="select2-${$(this).attr("id")}-results"]`).focus();
        } catch (_) {
            //
        }
    }, 5);
});

$(document).off("scroll").on("scroll", () => {
    if (!backToTopTicking) {
        window.requestAnimationFrame(() => {
            if ($('html').scrollTop() < 16) {
                if (backToTopButton) {
                    backToTopButton = false;
                    $(".back-to-top").hide();
                }
            } else {
                if (!backToTopButton) {
                    backToTopButton = true;
                    $(".back-to-top").show();
                }
            }
            backToTopTicking = false;
        });
        backToTopTicking = true;
    }
});

$(window).off("resize").on("resize", () => {
    if (!windowResizeTicking) {
        window.requestAnimationFrame(() => {
            if ($("#editorContainer:visible").length) {
                if (!$("#editorContainer").attr("data-fh")) {
                    let height = $(window).height() - mainFormTop;
                    if ($('#subTop:visible').length) {
                        height -= $('#subTop').height();
                    }
                    $("#editorContainer").css("height", height + "px");
                }
            }
            if ($(".resizable:visible").length) {
                $(".resizable:visible").trigger("windowResized");
            }
            windowResizeTicking = false;
        });
        windowResizeTicking = true;
    }
});

$(document).on("paste", e => {
    if ($(".paste-target:visible").length) {
        e.preventDefault();
        if (!e.originalEvent.clipboardData.files.length) {
            return;
        }
        let files = [];
        Array.from(e.originalEvent.clipboardData.files).forEach(file => {
            files.push(file);
        });
        $(".paste-target:visible").trigger("proxy-paste", [files]);
    }
});

setInterval(() => {
    if (hasUnsavedChanges || ($("#editorContainer").length && currentAceEditor && currentAceEditorOriginalValue !== false && currentAceEditor.getValue() != currentAceEditorOriginalValue)) {
        if (typeof window.onbeforeunload != "function") {
            window.onbeforeunload = () => false;
            $(".saveButton").addClass("text-primary");
        }
    } else {
        if (!$("#editorContainer").length) {
            currentAceEditor = false;
            currentAceEditorOriginalValue = false;
        }
        if (typeof window.onbeforeunload == "function") {
            window.onbeforeunload = null;
            $(".saveButton").removeClass("text-primary");
        }
    }

    $("form").off("submit").on("submit", ev => {
        ev.preventDefault();
        return false;
    });

    $(".blink-icon.blinking").toggleClass("text-warning");
    $(".blink-icon:not(.blinking)").removeClass("text-warning");
    $("body").addClass("sidebar-collapse");
}, 1000);