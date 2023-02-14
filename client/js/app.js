const modules = {};
const moduleLoadQueue = [];
const loadingProgress = new ldBar("#loadingProgress");

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

function hashChange() {
    let [ route, params, hash ] = hashParse();

    if (hash !== lastHash) {
        lastHash = hash;

        loadingStart();

        setTimeout(() => {
            currentPage = route;

            let r = route.split(".");

            if ($(".sidebar .withibleOnlyWhenActive[target!='#" + route + "']").length) {
                $(".sidebar .withibleOnlyWhenActive[target!='#" + route + "']").hide();
            } else {
                $(".sidebar .withibleOnlyWhenActive[target!='#" + r[0] + "']").hide();
            }

            if ($(".sidebar .withibleOnlyWhenActive[target='#" + route + "']").length) {
                $(".sidebar .withibleOnlyWhenActive[target='#" + route + "']").show();
            } else {
                $(".sidebar .withibleOnlyWhenActive[target='#" + r[0] + "']").show();
            }

            if ($(".sidebar .nav-item a[href!='#" + route + "']").length) {
                $(".sidebar .nav-item a[href!='#" + route + "']").removeClass('active');
            } else {
                $(".sidebar .nav-item a[href!='#" + r[0] + "']").removeClass('active');
            }

            if ($(".sidebar .nav-item a[href='#" + route + "']").length) {
                $(".sidebar .nav-item a[href='#" + route + "']").addClass('active');
            } else {
                $(".sidebar .nav-item a[href='#" + r[0] + "']").addClass('active');
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
                if (config.defaultRoute) {
                    location = config.defaultRoute;
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
                <h2 class="headline text-danger"> 404</h2>
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
    $.cookie("_lang", $("#loginBoxLang").val(), { expires: 3650, insecure: config.insecureCookie });
    location.reload();
}

function showLoginForm() {
    $("#mainForm").html("");
    $("#altForm").hide();
    $("#page404").hide();
    $("#pageError").hide();
    $("#forgotForm").hide();
    $("#loginForm").show();

    $("#loginBoxLogin").val($.cookie("_login"));
    $("#loginBoxServer").val($.cookie("_server"));
    if (!$("#loginBoxServer").val()) {
        $("#loginBoxServer").val(config.defaultServer);
    }
    $("#loginBoxRemember").attr("checked", $.cookie("_rememberMe") === "on");

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

    $("#forgotBoxServer").val($.cookie("_server"));
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
    let test = md5(new Date() + Math.random());

    $.cookie("_test", test, { insecure: config.insecureCookie });

    if ($.cookie("_test") != test) {
        error(i18n("errors.cantStoreCookie"), i18n("error"), 30);
        return;
    }

    loadingStart();

    let login = $.trim($("#loginBoxLogin").val());
    let password = $.trim($("#loginBoxPassword").val());
    let server = $.trim($("#loginBoxServer").val());
    let rememberMe = $("#loginBoxRemember").val();

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    $.cookie("_rememberMe", rememberMe, { expires: 3650, insecure: config.insecureCookie });

    if (rememberMe === "on") {
        $.cookie("_login", login, { expires: 3650, insecure: config.insecureCookie });
        $.cookie("_server", server, { expires: 3650, insecure: config.insecureCookie });
    } else {
        $.cookie("_login", login);
        $.cookie("_server", server);
    }

    ping(server).then(() => {
        return jQuery.ajax({
            url: server + "/authentication/login",
            type: "POST",
            contentType: "json",
            data: JSON.stringify({
                login: login,
                password: password,
                rememberMe: rememberMe === "on",
                ua: $.cookie("_ua"),
                did: $.cookie("_did"),
            }),
            success: response => {
                if (response && response.token) {
                    if (rememberMe === "on") {
                        $.cookie("_token", response.token, { expires: 3650, insecure: config.insecureCookie });
                    } else {
                        $.cookie("_token", response.token);
                    }
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
    window.onbeforeunload = null;

    POST("authentication", "logout", false, {
        mode: "all",
    }).always(() => {
        $.cookie("_token", "");
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
        $.cookie("_server", $("#loginBoxServer").val());
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
            if (_me.user.eMail) {
                userCard += "<br />" + _me.user.eMail;
            }
            $("#userCard").html(userCard);
        }
    })
}

function initAll() {
    if (!$.cookie("_cookie")) {
        warning(i18n("cookieWarning"), false, 3600);
        $.cookie("_cookie", "1", { expires: 3650, insecure: config.insecureCookie });
    }

    if (!$.cookie("_https") && window.location.protocol === 'http:') {
        warning(i18n("httpsWarning"), false, 3600);
        $.cookie("_https", "1", { expires: 3650, insecure: config.insecureCookie });
    }

    if (config.logo) {
        setFavicon("img/" + config.logo + "Icon.png");
        $("#leftSideToggler").attr("src", "img/" + config.logo + ".png");
        $("#loginBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
        $("#forgotBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
    }

    $(document.body).css("background-color", '#e9ecef');

    if (!$.cookie("_ua")) {
        $.cookie("_ua", $.browser.ua, { expires: 3650, insecure: config.insecureCookie });
    }

    if (!$.cookie("_did")) {
        $.cookie("_did", guid(), { expires: 3650, insecure: config.insecureCookie });
    }

    loadingStart();

    $("#leftSideToggler").parent().parent().on("click", () => {
        setTimeout(() => {
            $.cookie("_ls_collapse", $("body").hasClass("sidebar-collapse")?"1":"0", { expires: 3650, insecure: config.insecureCookie });
        }, 100);
    });

    if (parseInt($.cookie("_ls_collapse"))) {
        $("body").addClass("sidebar-collapse");
    }

    document.title = i18n("windowTitle");

    $("#loginBoxTitle").text(i18n("loginFormTitle"));
    $("#loginBoxLogin").attr("placeholder", i18n("login"));
    $("#loginBoxPassword").attr("placeholder", i18n("password"));
    $("#loginBoxServer").attr("placeholder", i18n("server"));

    let l = "";
    for (let i in config.languages) {
        if ($.cookie("_lang") == i) {
            l += `<option value='${i}' selected>${config.languages[i]}</option>`;
        } else {
            l += `<option value='${i}'>${config.languages[i]}</option>`;
        }
    }
    $("#loginBoxLang").html(l);

    $("#loginBoxLoginButton").text(i18n("loginAction"));
    $("#loginBoxForgotPassword").text(i18n("passowrdForgot"));
    $("#loginBoxRememberLabel").text(i18n("rememberMe"));

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

    $("#confirmModal").draggable({
        handle: "#confirmModalHeader",
    });

    $("#yesnoModal").draggable({
        handle: "#yesnoModalHeader",
    });

    $("#alertModal").draggable({
        handle: "#alertModalHeader",
    });

    if ($.cookie("_server") && $.cookie("_token")) {
        POST("authentication", "ping", false, {
            ua: $.cookie("_ua"),
        }).done((a, b) => {
            if (b === "nocontent") {
                GET("authorization", "available").done(a => {
                    if (a && a.available) {
                        myself = {
                            uid: -1,
                        };
                        whoAmI().done(() => {
                            window.onbeforeunload = () => false;
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
                                    location = config.defaultRoute;
                                } else {
                                    hashChange();
                                    onhashchange = hashChange;
                                }
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
}

