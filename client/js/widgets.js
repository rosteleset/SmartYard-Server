var backToTopButton = false;
var backToTopTicking = false;
var windowResizeTicking = false;

const systemColors = [
    // empty
    "",
    // theme colors
    "primary",
    "secondary",
    "info",
    "success",
    "warning",
    "danger",
    // black/white nuances
    "black",
    "gray-dark",
    "gray",
    "light",
    // colors
    "indigo",
    "lightblue",
    "navy",
    "purple",
    "fuchsia",
    "pink",
    "maroon",
    "orange",
    "lime",
    "teal",
    "olive",
];

function message(message, caption, timeout) {
    timeout = timeout ? timeout : 15;
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
    timeout = timeout ? timeout : 15;
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
    timeout = timeout ? timeout : 15;
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
    let t = yes ? yes : i18n("yes");
    t = t.charAt(0).toUpperCase() + t.substring(1);
    $('#yesnoModalButtonYes').html(t).off('click').on('click', () => {
        $('#yesnoModal').modal('hide');
        if (typeof callbackYes == 'function') callbackYes();
    });
    t = no ? no : i18n("no");
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
    $('#alertModalButton').off('click').on('click', e => {
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
        $('#promptModalButton').html(i18n("ok"));
    }
    $("#promptModalInput").off("keypress").on("keypress", e => {
        if (e.keyCode == 13) {
            $('#promptModalButton').click();
        }
    });
    $('#promptModalButton').off('click').on('click', e => {
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

function mPassword(prompt, title, callback, titleButton, mainButton) {
    $('#passwordModalInput').val("");
    if (!title) {
        title = i18n("message");
    }
    let l = $('#passwordModalLabel').html(title);
    if (titleButton) {
        l.next().remove();
        l.parent().append($(titleButton));
    }
    $('#passwordModalBody').html(prompt);
    if (mainButton) {
        $('#passwordModalButton').html(mainButton);
    } else {
        $('#passwordModalButton').html(i18n("ok"));
    }
    $("#passwordModalInput").off("keypress").on("keypress", e => {
        if (e.keyCode == 13) {
            $('#passwordModalButton').click();
        }
    });
    $('#passwordModalButton').off('click').on('click', e => {
        $('#passwordModal').modal('hide');
        let v = $('#passwordModalInput').val();
        $('#passwordModalInput').val("");
        if (typeof callback == 'function') callback(v);
        e.stopPropagation();
    });
    autoZ($('#passwordModal')).modal('show');
    xblur();
    setTimeout(() => {
        $('#passwordModalInput').focus();
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
    if (response.getResponseHeader("x-maintenance") == "yes") {
        error(i18n("maintenanceMessage"), i18n("maintenanceCaption"), 30);
        pageMaintenance();
        loadingDone();
        return $.Deferred().reject(i18n("maintenanceCaption"));
    } else {
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
}

function FAILPAGE(response) {
    if (response.getResponseHeader("x-maintenance") == "yes") {
        error(i18n("maintenanceMessage"), i18n("maintenanceCaption"), 30);
        pageMaintenance();
        loadingDone();
        return $.Deferred().reject(i18n("maintenanceCaption"));
    } else {
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
    }
    loadingDone();
}

function loadingStart(callback) {
    document.body.style.cursor = 'wait';

    autoZ($('#loading').modal({
        backdrop: 'static',
        keyboard: false,
    }));

    if (callback) {
        callback();
    }
}

function loadingDone(stayHidden) {
    document.body.style.cursor = 'default';

    xblur();

    $('#loading').modal('hide');

    if (stayHidden === true) {
        $('#app').addClass("invisible");
    } else {
        $('#app').removeClass("invisible");
    }

    $(window).resize();
}

function page404() {
    $("#mainForm").html("");
    $("#altForm").hide();
    subTop();
    loadingDone();
    document.title = `${i18n("windowTitle")} :: 404`;
    $("#pageError").html(`
        <section class="content">
            <div class="error-page">
                <img src="img/404.jpg" style="border: none; width: 200px; height: 200px; border-radius: 10px;">
                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger mr-3"></i><b>${i18n("errors.404caption")}</b></h3>
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
                <img src="img/error.jpg" style="border: none; width: 200px; height: 200px; border-radius: 10px;">
                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger mr-3"></i><b>${i18n("error")}</b></h3>
                    <p>${error ? error : i18n("errors.unknownMessage")}</p>
                </div>
            </div>
        </section>
    `).show();
}

function pageMaintenance() {
    $("#mainForm").html("");
    $("#altForm").hide();
    subTop();
    loadingDone();
    document.title = `${i18n("windowTitle")} :: ${i18n("maintenanceCaption")}`;
    $("#pageError").html(`
        <section class="content">
            <div class="error-page">
                <img src="img/${i18n("images.maintenance")}" style="border: none; width: 200px; height: 200px; border-radius: 10px;">
                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger mr-3"></i><b>${i18n("maintenanceCaption")}</b></h3>
                    <p>${i18n("maintenanceMessage")}</p>
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

    if (wisibleOnlyWhenActive && target !== "#" + route.split('.')[0]) {
        style += "display: none;";
    }

    $("#leftside-menu").append(`
        <li id="${id}" class="leftsidebar-button nav-item${wisibleOnlyWhenActive?" wisibleOnlyWhenActive":""}" data-target="${target}" title="${escapeHTML(title)}" style="${style}">
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
        <li id="${id}" class="leftsidebar-button nav-item" title="${escapeHTML(title)}" style="${style}">
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

$(document).on("scroll", () => {
    if (backToTopTicking) {
        clearTimeout(backToTopTicking);
    }
    backToTopTicking = setTimeout(() => {
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
    }, 150);
});

$(window).on("resize", () => {
    if (windowResizeTicking) {
        clearTimeout(windowResizeTicking);
    }
    windowResizeTicking = setTimeout(() => {
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
    }, 150);
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
}, 1000);