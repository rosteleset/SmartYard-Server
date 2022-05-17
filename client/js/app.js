var last_hash = false;
var modules = [];
var moduleLoadQueue = [];
var me = {};

function parseHash(hash, default_route) {
    let params = {};
    let route;

    default_route = default_route?default_route:'default';

    try {
        hash = hash.split('#')[1].split('&');
        route = hash[0]?hash[0]:default_route;
        for (let i = 1; i < hash.length; i++) {
            let sp = hash[i].split('=');
            params[sp[0]] = sp[1]?decodeURIComponent(sp[1]):true;
        }
    } catch (e) {
        route = default_route;
    }

    return [ route, params?params:[] ];
}

function implodeHash(route, params) {
    let p_ = '';

    for (let i in params) {
        p_ += '&' + i + '=' + encodeURIComponent(params[i]);
    }

    return '#' + route + p_;
}

function hashChange() {
    let hash = window.location.href.split('#')[1];
    hash = hash?('#' + hash):'';
    navigate(hash);
}

function navigate(hash, force) {
    $('.dropdownMenu').collapse('hide');
    $('.modal').modal('hide');

    let [ route, params ] = parseHash(hash);

    if (hash !== last_hash || force) {

        loadingStart();
        $('.mainform').hide();

        last_hash = hash;
        if (force) {
            window.location.href = hash;
        }

        setTimeout(() => {
            current_page = route;

            $(".sidebar .nav-item a").removeClass('active');
            $(".sidebar .nav-item a[href='#" + route.split('.')[0] + "']").addClass('active');

            $("#loginForm").hide();
            $("#forgotForm").hide();
            if (modules[route]) {
                $("#page404").hide();
                $("#topMenuLeftDynamic").html("");
                if (modules[route].search) {
                    $("#searchForm").show();
                } else {
                    $("#searchForm").hide();
                }
                modules[route].route(params);
            } else
            if (route == "default") {
                if (window.config.defaultRoute) {
                    window.location = "#" + window.config.defaultRoute;
                } else {
                    loadingDone();
                }
            } else {
                $("#page404").show();
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

function changeLanguage() {
    $.cookie("_lang", $("#loginBoxLang").val(), { expires: 3650 });
    location.reload();
}

function showLoginForm() {
    $("#page404").hide();
    $("#forgotForm").hide();
    $("#loginForm").show();

    $("#loginBoxLogin").val($.cookie("_login"));
    $("#loginBoxServer").val($.cookie("_server"));
    if (!$("#loginBoxServer").val()) {
        $("#loginBoxServer").val(window.config.defaultServer);
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
    $("#loginForm").hide();
    $("#forgotForm").show();

    $("#forgotBoxServer").val($.cookie("_server"));
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val($("#loginBoxServer").val());
    }
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val(window.config.defaultServer);
    }

    loadingDone(true);

    $("#forgotBoxEmail").focus();
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
                window.location.reload();
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
    let email = $.trim($("#forgotBoxEmail").val());

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
    module = moduleLoadQueue.shift();
    if (!module) {
        hashChange();
        window.onhashchange = hashChange;
        $("#app").show();
    } else {
        let lang = $.cookie("_lang");
        if (!lang) {
            lang = window.config.defaultLanguage;
        }
        if (!lang) {
            lang = "ru";
        }
        $.get("modules/" + module + "/i18n/" + lang + ".json", i18n => {
            window.lang[module] = i18n;
            $.getScript("modules/" + module + "/" + module + ".js");
        }).fail(() => {
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

function initAll() {
    if (!$.cookie("_cookie")) {
        warning(i18n("cookieWarning"), false, 3600);
        $.cookie("_cookie", "1", { expires: 36500 });
    }

    $.ajaxSetup({
        cache: window.config.ajaxCache,
    });

    $(window.document.body).css("background-color", '#e9ecef');

    if (!$.cookie("_ua")) {
        $.cookie("_ua", $.browser.ua, { expires: 36500 });
    }

    if (!$.cookie("_did")) {
        $.cookie("_did", guid(), { expires: 36500 });
    }

    loadingStart();

    $('.burger').on('click', () => {
        setTimeout(() => {
            $.cookie('_ls_collapse', $('body').hasClass('sidebar-collapse')?'1':'0', { expires: 36500 });
        }, 100);
    });

    if (parseInt($.cookie('_ls_collapse'))) {
        $('body').addClass('sidebar-collapse');
    }

    setFavicon({ src: "img/tech.png" });

    document.title = i18n("windowTitle");

    $("#loginBoxLogo").html("<img src='img/rosteleset.png'/>");
    $("#loginBoxTitle").text(i18n("loginFormTitle"));
    $("#loginBoxLogin").attr("placeholder", i18n("login"));
    $("#loginBoxPassword").attr("placeholder", i18n("password"));
    $("#loginBoxServer").attr("placeholder", i18n("server"));

    let l = "";
    for (let i in window.config.languages) {
        if ($.cookie("_lang") == i) {
            l += `<option value='${i}' selected>${window.config.languages[i]}</option>`;
        } else {
            l += `<option value='${i}'>${window.config.languages[i]}</option>`;
        }
    }
    $("#loginBoxLang").html(l);

    $("#loginBoxLoginButton").text(i18n("loginAction"));
    $("#loginBoxForgotPassword").text(i18n("passowrdForgot"));
    $("#loginBoxRememberLabel").text(i18n("rememberMe"));

    $("#forgotBoxLogo").html("<img src='img/rosteleset.png'/>");
    $("#forgotBoxTitle").text(i18n("forgotFormTitle"));
    $("#forgotBoxEmail").attr("placeholder", i18n("email"));
    $("#forgotBoxButton").text(i18n("forgotAction"));
    $("#forgotBoxLogin").text(i18n("forgotLogin"));
    $("#forgotBoxServer").attr("placeholder", i18n("server"));

    $("#brandTitle").text(i18n("windowTitle"));
    $("#searchInput").attr("placeholder", i18n("search"));
    $("#logout").text(i18n("logout"));

    $("#searchInput").off("keypress").on("keypress", e => {
        if (e.charCode === 13) {
            modules[current_page].search($("#searchInput").val());
        }
    });

    $("#searchButton").off("click").on("click", () => {
        modules[current_page].search($("#searchInput").val());
    });

    if ($.cookie("_server") && $.cookie("_token")) {
        POST("authentication", "ping", false, {
            ua: $.cookie("_ua"),
        }).done((a, b) => {
            if (b === "nocontent") {
                GET("authorization", "available").done((a, b) => {
                    if (a.available && b === "success") {
                        GET("accounts", "whoAmI").done(_me => {
                            if (_me && _me.user) {
                                let userCard = _me.user.login;
                                if (_me.user.realName) {
                                    userCard += "<br />" + _me.user.realName;
                                }
                                if (_me.user.eMail) {
                                    userCard += "<br />" + _me.user.eMail;
                                }
                                $("#userCard").html(userCard);
                            }
                        });
                        window.available = a.available;
                        console.log(a.available);
                        if (window.config && window.config.modules) {
                            for (let i in window.config.modules) {
                                moduleLoadQueue.push(window.config.modules[i]);
                            }
                            loadModule();
                        } else {
                            $("#app").show();
                            if (window.config.defaultRoute) {
                                window.onhashchange = hashChange;
                                window.location = "#" + window.config.defaultRoute;
                            } else {
                                hashChange();
                                window.onhashchange = hashChange;
                            }
                        }
                    }
                }).fail(response => {
                    if (response && response.responseJSON && response.responseJSON.error) {
                        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
                    } else {
                        error(i18n("errors.unknown"), i18n("error"), 30);
                    }
                    showLoginForm();
                });
            }
        }).fail(response => {
            if (response && response.responseJSON && response.responseJSON.error) {
                error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
            } else {
                error(i18n("errors.unknown"), i18n("error"), 30);
            }
            showLoginForm();
        });
    } else {
        showLoginForm();
    }
}

