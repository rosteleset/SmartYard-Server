const modules = {};
const moduleLoadQueue = [];
const loadingProgress = new ldBar("#loadingProgress");

var lastHash = false;
var currentPage = false;
var mainSidebarFirst = true;
var config = false;
var lang = false;
var myself = false;
var available = false;
var badge = false;

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

function hashChange() {
    let [ route, params, hash ] = hashParse();

    if (hash !== lastHash) {
        lastHash = hash;

        loadingStart();

        setTimeout(() => {
            currentPage = route;

            $(".sidebar .withibleOnlyWhenActive[target!='#" + route.split('.')[0] + "']").hide();
            $(".sidebar .withibleOnlyWhenActive[target='#" + route.split('.')[0] + "']").show();
            $(".sidebar .nav-item a[href!='#" + route.split('.')[0] + "']").removeClass('active');
            $(".sidebar .nav-item a[href='#" + route.split('.')[0] + "']").addClass('active');

            $("#loginForm").hide();
            $("#forgotForm").hide();

            if (modules[route]) {
                $("#page404").hide();
                $("#pageError").hide();
                $("#topMenuLeft").html(`<li class="ml-3 mr-3 nav-item d-none d-sm-inline-block text-bold text-lg">${i18n(route.split('.')[0] + "." + route.split('.')[0])}</li>`);
                $("#subTop").html("");
                $("#leftTopDynamic").html("");
                $("#rightTopDynamic").html("");
                if (modules[route].search) {
                    $("#searchForm").show();
                } else {
                    $("#searchForm").hide();
                }
                if (typeof modules[route].route === "function") {
                    modules[route].route(params);
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
    loadingDone(true);
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
    loadingDone(true);
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
    $.cookie("_lang", $("#loginBoxLang").val(), { expires: 3650 });
    location.reload();
}

function showLoginForm() {
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

function login() {
    loadingStart();

    let login = $.trim($("#loginBoxLogin").val());
    let password = $.trim($("#loginBoxPassword").val());
    let server = $.trim($("#loginBoxServer").val());
    let rememberMe = $("#loginBoxRemember").val();

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    $.cookie("_rememberMe", rememberMe, { expires: 3650 });

    if (rememberMe === "on") {
        $.cookie("_login", login, { expires: 3650 });
        $.cookie("_server", server, { expires: 3650 });
    } else {
        $.cookie("_login", login);
        $.cookie("_server", server);
    }

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
                    $.cookie("_token", response.token, { expires: 3650 });
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
}

function logout() {
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
            lang[module] = i18n;
        }).always(() => {
            $.getScript("modules/" + module + "/" + module + ".js");
        });
    }
}

function moduleLoaded(module, object) {
    let m = module.split(".");

    modules[module] = object;

    if (m.length === 1) {
        loadModule();
    }
}

function whoAmI(force) {
    return GET("accounts", "whoAmI", false, force).done(_me => {
        if (_me && _me.user) {
            myself.uid = _me.user.uid;
            myself.realName = _me.user.realName;
            myself.eMail = _me.user.eMail;
            myself.phone = _me.user.phone;
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
        $.cookie("_cookie", "1", { expires: 36500 });
    }

    if (config.logo) {
        setFavicon("img/" + config.logo + "Icon.png");
        $("#leftSideToggler").attr("src", "img/" + config.logo + ".png");
        $("#loginBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
        $("#forgotBoxLogo").html("<img class='mb-2' src='img/" + config.logo + "Text.png' width='285px'/>");
    }

    $(document.body).css("background-color", '#e9ecef');

    if (!$.cookie("_ua")) {
        $.cookie("_ua", $.browser.ua, { expires: 36500 });
    }

    if (!$.cookie("_did")) {
        $.cookie("_did", guid(), { expires: 36500 });
    }

    loadingStart();

    $("#leftSideToggler").on("click", () => {
        setTimeout(() => {
            $.cookie("_ls_collapse", $("body").hasClass("sidebar-collapse")?"1":"0", { expires: 36500 });
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

