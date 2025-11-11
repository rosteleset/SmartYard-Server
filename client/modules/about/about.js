({
    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "?#about", "about");
        moduleLoaded("about", this);
    },

    route: function () {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");

        $.get("modules/custom/version?_=" + Math.random()).
        always((x, y) => {
            let custom = "-";
            if (y == "success" && $.trim(x)) {
                custom = $.trim(x);
            }
            $.get("version.cli?_=" + Math.random()).
            done(cli => {
                $.get("version.app?_=" + Math.random()).
                done(app => {
                    let versionActual = md5(app + "/" + cli + "/" + custom);
                    GET("server", "version", false, true).
                    done(v => {
                        let h = '';
                        h += i18n("about.text", cli ? cli : 'unknown', v.appVersion ? v.appVersion : 'unknown', v.dbVersion ? v.dbVersion : 'unknown', (custom != "-" ) ? custom : i18n('no'));
                        if (version != versionActual) {
                            h += `<br /><button type="button" class="mt-3 btn btn-outline-secondary" onclick="window.location.reload(true);"><i class="fas fa-sync-alt mr-2"></i>${i18n("about.refresh")}</button>`;
                        }
                        $("#mainForm").html(h);
                    }).
                    fail(FAIL).
                    always(loadingDone);
                }).
                fail(FAIL).
                always(loadingDone);
            }).
            fail(FAIL).
            always(loadingDone);
        });
    },
}).init();