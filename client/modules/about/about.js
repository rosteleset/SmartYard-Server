({
    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "?#about", "about");
        moduleLoaded("about", this);
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");

        $.get("version.cli?_=" + Math.random()).
        done(cli => {
            GET("server", "version", false, true).
            done(v => {
                $("#mainForm").html(i18n("about.text", cli ? cli : 'unknown', v.appVersion ? v.appVersion : 'unknown', v.dbVersion ? v.dbVersion : 'unknown'));
            }).
            fail(FAIL).
            always(loadingDone);
        }).fail(FAIL);
    },
}).init();