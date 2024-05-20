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
var currentAceEditor = false;
var currentAceEditorOriginalValue = false;

function hashChange() {
    $('.dropdownMenu').collapse('hide');
    $('.modal').modal('hide');

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
                $("#topMenuLeft").html(`<li id="topMenuLeftCaption" class="ml-3 mr-3 nav-item d-none d-sm-inline-block text-bold text-lg">${i18n(route.split('.')[0] + "." + route.split('.')[0])}</li>`);
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

function changeLanguage() {
    lStore("_lang", $("#loginBoxLang").val());
    location.reload();
}

function ping(server) {
    return $.ajax({
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
        return $.ajax({
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

function doLogout(all) {
    if (all != "all") {
        all = "no";
    }
    POST("authentication", "logout", false, {
        mode: "all",
    }).always(() => {
        lStore("_token", null);
        location.reload();
    });
}

function logout() {
    mYesNo(
        i18n("logoutQuestion"),
        i18n("logout"),
        () => {
            doLogout("single");
        },
        () => {
            doLogout("all");
        },
        i18n("logoutSingle"),
        i18n("logoutAll")
    );
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
            myself.login = _me.user.login;
            myself.realName = _me.user.realName;
            myself.eMail = _me.user.eMail;
            myself.phone = _me.user.phone;
            myself.webRtcExtension = _me.user.webRtcExtension;
            myself.webRtcPassword = _me.user.webRtcPassword;
            myself.groups = {};
            if (_me.user.groups) {
                for (let i in _me.user.groups) {
                    if (_me.user.groups[i].acronym == _me.user.primaryGroupAcronym) {
                        myself.primaryGroupAcronym = _me.user.groups[i].acronym;
                        myself.primaryGroupName = _me.user.groups[i].name;
                    }
                    myself.groups[_me.user.groups[i].acronym] = {
                        "adminLogin": _me.user.groups[i].adminLogin,
                        "name": _me.user.groups[i].name,
                    };
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
    });
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
    $("#loginBoxForgotPassword").text(i18n("passwordForgot"));

    $("#forgotBoxTitle").text(i18n("forgotFormTitle"));
    $("#forgotBoxEMail").attr("placeholder", i18n("eMail"));
    $("#forgotBoxButton").text(i18n("forgotAction"));
    $("#forgotBoxLogin").text(i18n("forgotLogin"));
    $("#forgotBoxServer").attr("placeholder", i18n("server"));

    $("#brandTitle").text(i18n("windowTitle"));
    $("#logout").text(i18n("logout"));

    $("#myNotifications").attr("title", i18n("noNotifications"));

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

function loadModule() {
    let module = moduleLoadQueue.shift();
    if (!module) {
        for (let i in modules) {
            if (typeof modules[i].allLoaded == "function") {
                modules[i].allLoaded();
            }
            if (config && config.customSubModules && config.customSubModules[i]) {
                for (let j in config.customSubModules[i]) {
                    if (typeof modules[i][config.customSubModules[i][j]].allLoaded == "function") {
                        modules[i][config.customSubModules[i][j]].allLoaded();
                    }
                }
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
        })
        .fail(FAIL)
        .always(() => {
            if (config && config.customSubModules && config.customSubModules[module]) {
                $.get("modules/" + module + "/custom/i18n/" + l + ".json", i18n => {
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
                    lang[module] = {...lang[module], ...i18n};
                }).always(() => {
                    $.getScript("modules/" + module + "/" + module + ".js")
                    .fail(() => {
                        pageError(i18n("errorLoadingModule", module));
                    });
                });
            } else {
                $.getScript("modules/" + module + "/" + module + ".js")
                .fail(() => {
                    pageError(i18n("errorLoadingModule", module));
                });
            }
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
        if (config && config.customSubModules && config.customSubModules[m[0]]) {
            loadCustomSubModules(m[0], JSON.parse(JSON.stringify(config.customSubModules[m[0]])));
        } else {
            loadModule();
        }
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

function loadCustomSubModules(parent, subModules) {
    let module = subModules.shift();
    if (!module) {
        loadModule();
    } else{
        $.getScript("modules/" + parent + "/custom/" + module + ".js").
        done(() => {
            loadCustomSubModules(parent, subModules);
        }).
        fail(FAIL);
    }
}

function hashParse(part) {
    let u = new URL(location.href);

    let hash = u.hash;

    if (hash && hash[0] == "#") {
        hash = hash.substring(1);
    }

    let params = {};
    let route = "default";

    try {
        let t = $.deparam(hash);
        let k = Object.keys(t);
        route = k[0];
        k.shift();
        for (let i in k) {
            params[k[i]] = t[k[i]];
        }
    } catch (e) {
        route = "default";
    }

    if (part == "route") {
        return route;
    }

    if (part == "params") {
        return params;
    }

    if (part == "hash") {
        return hash;
    }

    return [ route, params, hash ];
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

$("#loginBoxPassword").off("keypress").on("keypress", e => {
    if (e.keyCode == 13) {
        login();
    }
});