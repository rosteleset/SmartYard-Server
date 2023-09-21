const modules = {};
const moduleLoadQueue = [];
const loadingProgress = new ldBar("#loadingProgress");
// TODO f..ck!
const mainFormTop = 75;

var lastHash = false;
var currentPage = false;
var mainSidebarFirst = true;
var mainSidebarGroup = false;
var config = false;
var lang = false;
var myself = false;
var available = false;
var badge = false;
var currentModule = false;
var lStoreEngine = false;
var hasUnsavedChanges = false;

function hashChange() {
    let [ route, params, hash ] = hashParse();

    if (hash !== lastHash) {
        lastHash = hash;

        loadingStart();

        setTimeout(() => {
            currentPage = route;

            let r = route.split(".");

            if ($(".sidebar .withibleOnlyWhenActive[data-target!='?#" + route + "']").length) {
                $(".sidebar .withibleOnlyWhenActive[data-target!='?#" + route + "']").hide();
            } else {
                $(".sidebar .withibleOnlyWhenActive[data-target!='?#" + r[0] + "']").hide();
            }

            if ($(".sidebar .withibleOnlyWhenActive[data-target='?#" + route + "']").length) {
                $(".sidebar .withibleOnlyWhenActive[data-target='?#" + route + "']").show();
            } else {
                $(".sidebar .withibleOnlyWhenActive[data-target='?#" + r[0] + "']").show();
            }

            if ($(".sidebar .nav-item a[data-href!='?#" + route + "']").length) {
                $(".sidebar .nav-item a[data-href!='?#" + route + "']").removeClass('active');
            } else {
                $(".sidebar .nav-item a[data-href!='?#" + r[0] + "']").removeClass('active');
            }

            if ($(".sidebar .nav-item a[data-href='?#" + route + "']").length) {
                $(".sidebar .nav-item a[data-href='?#" + route + "']").addClass('active');
            } else {
                $(".sidebar .nav-item a[data-href='?#" + r[0] + "']").addClass('active');
            }

            $("#loginForm").hide();
            $("#forgotForm").hide();

            let module = modules;

            for (let i = 0; i < r.length; i++) {
                if (module[r[i]]) {
                    module = module[r[i]];
                } else {
                    module = false;
                    break;
                }
            }

            if (module) {
                $("#page404").hide();
                $("#pageError").hide();
                $("#topMenuLeft").html(`<li class="ml-3 mr-3 nav-item d-none d-sm-inline-block text-bold text-lg">${i18n(route.split('.')[0] + "." + route.split('.')[0])}</li>`);
                if (currentModule != module) {
                    $("#leftTopDynamic").html("");
                    $("#rightTopDynamic").html("");
                    currentModule = module;
                }
                if (typeof module.search === "function") {
                    $("#searchForm").show();
                } else {
                    $("#searchForm").hide();
                }
                if (typeof module.route === "function") {
                    module.route(params);
                } else {
                    page404();
                }
            } else
            if (route === "default") {
                if (config.defaultRoute && config.defaultRoute != "#" && config.defaultRoute != "?#") {
                    location.href = (config.defaultRoute.charAt(0) == "?")?config.defaultRoute:("?" + config.defaultRoute);
                } else {
                    loadingDone();
                }
            } else {
                page404();
            }
        }, 50);
    }
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
    $("#subTop").html("");
    $("#altForm").hide();
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

function changeLanguage() {
    lStore("_lang", $("#loginBoxLang").val());
    location.reload();
}

function showLoginForm() {
    $("#mainForm").html("");
    $("#altForm").hide();
    $("#page404").hide();
    $("#pageError").hide();
    $("#forgotForm").hide();
    $("#loginForm").show();

    $("#loginBoxLogin").val(lStore("_login"));
    $("#loginBoxServer").val(lStore("_server"));
    if (!$("#loginBoxServer").val()) {
        $("#loginBoxServer").val(config.defaultServer);
    }

    let server = $("#loginBoxServer").val();

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    $.get(server + "/accounts/forgot?available=ask").done(() => {
        $("#loginBoxForgot").show();
    });

    loadingDone(true);

    if ($("#loginBoxLogin").val()) {
        $("#loginBoxPassword").focus();
    } else {
        $("#loginBoxLogin").focus();
    }
}

function showForgotPasswordForm() {
    $("#mainForm").html("");
    $("#altForm").hide();
    $("#page404").hide();
    $("#pageError").hide();
    $("#loginForm").hide();
    $("#forgotForm").show();

    $("#forgotBoxServer").val(lStore("_server"));
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val($("#loginBoxServer").val());
    }
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val(config.defaultServer);
    }

    loadingDone(true);

    $("#forgotBoxEMail").focus();
}

function ping(server) {
    return jQuery.ajax({
        url: server + "/server/ping",
        type: "POST",
        contentType: "json",
        success: response => {
            if (response != "pong") {
                loadingDone(true);
                error(i18n("errors.serverUnavailable"), i18n("error"), 30);
            }
        },
        error: () => {
            loadingDone(true);
            error(i18n("errors.serverUnavailable"), i18n("error"), 30);
        }
    });
}

function login() {
    loadingStart();

    let login = $.trim($("#loginBoxLogin").val());
    let password = $.trim($("#loginBoxPassword").val());
    let server = $.trim($("#loginBoxServer").val());

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    lStore("_login", login);
    lStore("_server", server);

    ping(server).then(() => {
        return jQuery.ajax({
            url: server + "/authentication/login",
            type: "POST",
            contentType: "json",
            data: JSON.stringify({
                login: login,
                password: password,
                rememberMe: true,
                did: lStore("_did"),
            }),
            success: response => {
                if (response && response.token) {
                    lStore("_token", response.token);
                    location.reload();
                } else {
                    error(i18n("errors.unknown"), i18n("error"), 30);
                }
            },
            error: response => {
                loadingDone(true);
                $("#loginBoxLogin").focus();
                if (response && response.responseJSON && response.responseJSON.error) {
                    error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
                } else {
                    error(i18n("errors.unknown"), i18n("error"), 30);
                }
            }
        });
    });
}

function logout() {
    POST("authentication", "logout", false, {
        mode: "all",
    }).always(() => {
        lStore("_token", null);
        location.reload();
    });
}

function forgot() {
    let email = $.trim($("#forgotBoxEMail").val());

    let server = $("#forgotBoxServer").val();

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    if (email) {
        lStore("_server", $("#loginBoxServer").val());
        $.get(server + "/accounts/forgot?eMail=" + email);
        message(i18n("forgotMessage"));
        showLoginForm();
    }
}

function whoAmI(force) {
    return GET("authentication", "whoAmI", false, force).done(_me => {
        if (_me && _me.user) {
            $(".myNameIs").attr("title", _me.user.realName?_me.user.realName:_me.user.login);
            myself.uid = _me.user.uid;
            myself.realName = _me.user.realName;
            myself.eMail = _me.user.eMail;
            myself.phone = _me.user.phone;
            myself.webRtcExtension = _me.user.webRtcExtension;
            myself.webRtcPassword = _me.user.webRtcPassword;
            if (_me.user.groups) {
                for (let i in _me.user.groups) {
                    if (_me.user.groups[i].acronym == _me.user.primaryGroupAcronym) {
                        myself.primaryGroupName = _me.user.groups[i].name;
                    }
                }
            }
            if (_me.user.defaultRoute) {
                config.defaultRoute = _me.user.defaultRoute;
            }
            if (myself.eMail) {
                let gravUrl = "https://www.gravatar.com/avatar/" + md5($.trim(myself.eMail).toLowerCase()) + "?s=64&d=404";
                $(".userAvatar").off("click").on("error", function () {
                    $(this).attr("src", "avatars/noavatar.png");
                    error(i18n("errors.noGravatar"));
                }).attr("src", gravUrl);
            } else {
                if (parseInt(myself.uid) === 0) {
                    $(".userAvatar").attr("src", "avatars/admin.png");
                }
            }
            $("#selfSettings").off("click").on("click", () => {
                modules["users"].modifyUser(myself.uid, true);
            });
            let userCard = _me.user.login;
            if (_me.user.realName) {
                userCard += "<br />" + _me.user.realName;
            }
            if (myself.primaryGroupName) {
                userCard += "<br />" + myself.primaryGroupName;
            }
            if (_me.user.eMail) {
                userCard += "<br />" + _me.user.eMail;
            }
            $("#userCard").html(userCard);
        }
    })
}

function initAll() {
    if (config.logo) {
        setFavicon("img/" + config.logo + "Icon.png");
        $("#leftSideToggler").attr("src", "img/" + config.logo + ".png");
        $("#loginBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
        $("#forgotBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
    }

    $(document.body).css("background-color", '#e9ecef');

    loadingStart();

    document.title = i18n("windowTitle");

    $("#loginBoxTitle").text(i18n("loginFormTitle"));
    $("#loginBoxLogin").attr("placeholder", i18n("login"));
    $("#loginBoxPassword").attr("placeholder", i18n("password"));
    $("#loginBoxServer").attr("placeholder", i18n("server"));

    let l = "";
    for (let i in config.languages) {
        if (lStore("_lang") == i) {
            l += `<option value='${i}' selected>${config.languages[i]}</option>`;
        } else {
            l += `<option value='${i}'>${config.languages[i]}</option>`;
        }
    }
    $("#loginBoxLang").html(l);

    $("#loginBoxLoginButton").text(i18n("loginAction"));
    $("#loginBoxForgotPassword").text(i18n("passowrdForgot"));

    $("#forgotBoxTitle").text(i18n("forgotFormTitle"));
    $("#forgotBoxEMail").attr("placeholder", i18n("eMail"));
    $("#forgotBoxButton").text(i18n("forgotAction"));
    $("#forgotBoxLogin").text(i18n("forgotLogin"));
    $("#forgotBoxServer").attr("placeholder", i18n("server"));

    $("#brandTitle").text(i18n("windowTitle"));
    $("#logout").text(i18n("logout"));

    if (config.z2Enabled) {
        $(".rs232-scanner-button").show();
    }
    $('.rs232-scanner').attr('title', i18n("connectScanner"));

    $("#searchInput").attr("placeholder", i18n("search")).off("keypress").on("keypress", e => {
        if (e.charCode === 13) {
            modules[currentPage].search($("#searchInput").val());
        }
    });

    $("#inputTextLine").off("keypress").on("keypress", event => {
        if (event.keyCode === 13) $('#inputTextButton').click();
    });

    $("#searchButton").off("click").on("click", () => {
        modules[currentPage].search($("#searchInput").val());
    });

    /*
        $("#confirmModal").draggable({
            handle: "#confirmModalHeader",
        });

        $("#yesnoModal").draggable({
            handle: "#yesnoModalHeader",
        });

        $("#alertModal").draggable({
            handle: "#alertModalHeader",
        });

        $("#uploadModalBody").draggable({
            handle: "#uploadModalHeader",
        });
    */

    if (lStoreEngine && lStoreEngine !== "cookie") {
        lStore("_cookie", "1");
    }

    if (window.location.hostname !== "127.0.0.1" || window.location.hostname !== "localhost") {
        lStore("_https", "1");
    }

    if (!lStore("_cookie")) {
        warning(i18n("cookieWarning"), false, 3600);
        lStore("_cookie", "1");
    }

    if (!lStore("_https") && window.location.protocol === 'http:') {
        warning(i18n("httpsWarning"), false, 3600);
        lStore("_https", "1");
    }

    if (!lStore("_did")) {
        lStore("_did", guid());
    }

    if (lStore("_server") && lStore("_token")) {
        POST("authentication", "ping", false).done((a, b) => {
            if (b === "nocontent") {
                GET("authorization", "available").done(a => {
                    if (a && a.available) {
                        myself = {
                            uid: -1,
                        };
                        whoAmI().done(() => {
                            available = a.available;
                            if (config && config.modules) {
                                for (let i in config.modules) {
                                    moduleLoadQueue.push(config.modules[i]);
                                }
                                loadModule();
                            } else {
                                $("#app").show();
                                if (config.defaultRoute) {
                                    onhashchange = hashChange;
                                    location.href = (config.defaultRoute.charAt(0) == "?")?config.defaultRoute:("?" + config.defaultRoute);
                                } else {
                                    hashChange();
                                    onhashchange = hashChange;
                                }
                            }
                            setInterval(() => {
                                $(".blink-icon.blinking").toggleClass("text-warning");
                                $(".blink-icon:not(.blinking)").removeClass("text-warning");
                            }, 1000);
                        }).fail(response => {
                            FAIL(response);
                            showLoginForm();
                        });
                    } else {
                        FAIL();
                        showLoginForm();
                    }
                }).fail(response => {
                    FAIL(response);
                    showLoginForm();
                });
            } else {
                FAIL();
                showLoginForm();
            }
        }).fail(response => {
            FAIL(response);
            showLoginForm();
        });
    } else {
        showLoginForm();
    }

    if (!lStore()) {
        loadingDone();
        return;
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
        "preventDuplicates": true,
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
        "preventDuplicates": true,
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
//    autoZ($('#loading')).show();
}

function loadingDone(stayHidden) {
    xblur();
    
    $('#loading').modal('hide');
//    $('#loading').hide();

    if (stayHidden === true) {
        $('#app').addClass("invisible");
    } else {
        $('#app').removeClass("invisible");
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

    let id = md5(guid());

    $("#leftside-menu").append(`
        <li id="${id}" class="nav-item ${mainSidebarFirst?"mt-1":""} ${withibleOnlyWhenActive?" withibleOnlyWhenActive":""}" data-target="${target}" title="${escapeHTML(title)}"${(withibleOnlyWhenActive && target !== "#" + route.split('.')[0])?" style='display: none;'":""}>
            <a href="${target}" data-href="${target}" class="nav-link${(target === "#" + route.split('.')[0])?" active":""}">
                <i class="${button} nav-icon"></i>
                <p class="text-nowrap">${title}</p>
            </a>
        </li>
    `);

    mainSidebarGroup = group;
    mainSidebarFirst = false;

    return id;
}

function loadModule() {
    let module = moduleLoadQueue.shift();
    if (!module) {
        for (let i in modules) {
            if (typeof modules[i].allLoaded == "function") {
                modules[i].allLoaded();
            }
        }
        hashChange();
        onhashchange = hashChange;
        $("#app").show();
    } else {
        let l = lStore("_lang");
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

function trimStr(str, len, abbr) {
    if (!len) {
        len = 33;
    }
    let sub = Math.floor((len - 3) / 2);
    if (str.length > len) {
        if (abbr) {
            return "<abbr title='" + escapeHTML(str) + "'>" + str.substring(0, sub) + "..." + str.substring(str.length - sub) + "</abbr>";
        } else {
            return str.substring(0, sub) + "..." + str.substring(str.length - sub);
        }
    } else {
        return str;
    }
}

function lStore(key, val) {
    if (!lStoreEngine) {
        let wdb;
        
        let t = guid();

        try {
            wdb = new IdbKvStore("rbt");

            window.lStoreData = {};

            wdb.on("add", function (change) {
                lStoreData[change.key] = change.value;
            });

            wdb.on("set", function (change) {
                lStoreData[change.key] = change.value;
            });

            wdb.on("remove", function (change) {
                delete lStoreData[change.key];
            });

            wdb.set("test", t);

            if (!IdbKvStore.BROADCAST_SUPPORT) {
                throw true;
            }

            wdb.remove("test");
        } catch (e) {
            wdb = false;
        }

        if (wdb) {
            lStoreEngine = wdb;
        } else {
            $.cookie("test", t, { insecure: config.insecureCookie });

            if ($.cookie("test") != t) {
                error(i18n("errors.cantStoreCookie"), i18n("error"), 30);
                return false;
            }
        
            $.cookie("test", null);

            lStoreEngine = "cookie";
        }
    }

    if (key && typeof key !== "function") {
        if (typeof(val) != "undefined") {
            if (lStoreEngine === "cookie") {
                if (val === null) {
                    $.cookie(key, val);
                } else {
                    $.cookie(key, JSON.stringify(val), { expires: 3650, insecure: config.insecureCookie });
                }
            } else {
                if (val === null) {
                    delete lStoreData[key];
                    lStoreEngine.remove(key);
                } else {
                    lStoreData[key] = val;
                    lStoreEngine.set(key, val);
                }
            }
            return true;
        } else {
            if (lStoreEngine === "cookie") {
                try {
                    return JSON.parse($.cookie(key));
                } catch (e) {
                    $.cookie(key, null);
                    return null;
                }
            } else {
                return lStoreData[key];
            }
        }
    } else {
        if (lStoreEngine === "cookie") {
            if (typeof key === "function") {
                key();
            }
        } else {
            lStoreEngine.json((err, kv) => {
                if (!err && kv) {
                    lStoreData = kv;
                }
                if (typeof key === "function") {
                    key();
                }
            });
        }
        return true;
    }
}

function textRTrim(text) {
    text = text.split("\n");

    for (let i in text) {
        text[i] = text[i].trimRight()
    }

    return text.join("\n");
}

function QUERY(api, method, query, fresh) {
    return $.ajax({
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + (query?("?" + $.param(query)):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function GET(api, method, id, fresh) {
    return $.ajax({
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + ((typeof id !== "undefined" && id !== false)?("/" + encodeURIComponent(id)):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function AJAX(type, api, method, id, query) {
    return $.ajax({
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + ((typeof id !== "undefined" && id !== false)?("/" + encodeURIComponent(id)):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
        },
        type: type,
        contentType: "json",
        data: query?JSON.stringify(query):null,
    });
}

function POST(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function PUT(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function DELETE(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function FAIL(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
        if (response.responseJSON.error == "tokenNotFound") {
            lStore("_token", null);
            setTimeout(() => {
                location.reload();
            }, 5000);
        }
    } else {
        error(i18n("errors.unknown"), i18n("error"), 30);
    }
}

function FAILPAGE(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
        pageError(i18n("errors." + response.responseJSON.error));
    } else {
        error(i18n("errors.unknown"), i18n("error"), 30);
        pageError();
    }
    loadingDone();
}

function AVAIL(api, method, request_method) {
    if (request_method) {
        return available && available[api] && available[api][method] && available[api][method][request_method];
    }
    if (method) {
        return available && available[api] && available[api][method];
    }
    if (api) {
        return available && available[api];
    }
}

/*
$(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
});
*/

$(document).on('select2:open', '.select2', function () {
    setTimeout(() => {
        document.querySelector(`[aria-controls="select2-${$(this).attr("id")}-results"]`).focus();
    }, 5);
});

$(window).off("resize").on("resize", () => {
    if ($("#editorContainer").length) {
        let height = $(window).height() - mainFormTop;
        $("#editorContainer").css("height", height + "px");
    }
    if ($("#mapContainer").length) {
        let height = $(window).height() - mainFormTop;
        $("#mapContainer").css("height", height + "px");
    }
});

setInterval(() => {
    if (hasUnsavedChanges || $("#editorContainer").length) {
        if (typeof window.onbeforeunload != "function") {
            window.onbeforeunload = () => false;
        }
    } else {
        if (typeof window.onbeforeunload == "function") {
            window.onbeforeunload = null;
        }
    } 
}, 1000);