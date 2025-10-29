var version = 'unknown';
var serviceWorker;

function ver(c) {
    let needRestart = false;

    $.get("version.app?_=" + Math.random()).
    done(v1 => {
        $.get("version.cli?_=" + Math.random()).
        done(v2 => {
            $.get("modules/custom/version?_=" + Math.random()).
            always((x, y) => {
                let v3 = "-";
                if (y == "success" && $.trim(x)) {
                    v3 = $.trim(x);
                }
                version = md5(v1 + "/" + v2 + "/" + v3);
                lStore("_version", version);
                if ("serviceWorker" in navigator) {
                    navigator.serviceWorker.getRegistrations().
                    then(r => {
                        r.forEach(w => {
                            if (w && w.active && w.active.scriptURL) {
                                let url = new URL(w.active.scriptURL);
                                let pathname = url.pathname.split("/");
                                let search = url.search;
                                if (search && search[0] == "?") {
                                    search = search.substring(1);
                                }
                                search = $.deparam(search);
                                if (pathname[pathname.length - 1] == "sw.js" && (!search.ver || search.ver != version)) {
                                    w.unregister();
                                    needRestart = true;
                                }
                            }
                        });
                    }).
                    then(() => {
                        if (needRestart) {
                            setTimeout(() => {
                                lStore("_reload", Math.random());
                                window.location.reload();
                            }, 500);
                        } else {
                            navigator.serviceWorker.register('sw.js?ver=' + version).then(sw => {
                                serviceWorker = sw;
                                cfg(c);
                            });
                        }
                    });
                } else {
                    cfg(c);
                }
            });
        }).
        fail(() => {
            cfg(c);
        });
    }).
    fail(() => {
        cfg(c);
    });
}

function cfg(c) {
    config = c;

    lStore(() => {
        let l = lStore("_lang");
        if (!l) {
            l = config.defaultLanguage;
        }
        if (!l) {
            l = "ru";
        }
        $.get("i18n/" + l + ".json?ver=" + version).
        done(t => {
            lang = t;
            initAll();
        }).
        fail(function() {
            lang = {};
            initAll();
        });
    });
}

function md(c) {
    if (c.mdCheck && c.mdRedirect) {
        $.get(c.mdCheck + "/#?_=" + Math.random()).
        done(r => {
            if (r) {
                let href = new URL(window.location.href);
                let hash = "";
                if (href.hash) {
                    hash = href.hash.substring(1);
                }
                window.location.href = c.mdRedirect + (hash ? ("/#?_from=" + encodeURIComponent(hash)) : "");
            } else {
                ver(c);
            }
        }).
        fail(() => {
            ver(c);
        });
    } else {
        ver(c);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    $.ajaxSetup({
        cache: true
    });
    $.ajaxSettings.converters["text json5"] = JSON5.parse;

    lStore(() => {
        $.ajax({
            url: "config/config.json?_=" + Math.random(),
            dataType: "json5",
            cache: false,
            success: c => {
                md(c);
            },
            error: e => {
                error("Woops can't get config");
            },
            timeout: 5000,
            converters: { "json5": JSON5.parse }
        });
    });
});
