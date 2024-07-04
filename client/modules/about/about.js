({
    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "?#about", "about");
        moduleLoaded("about", this);
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");

        GET("server", "version", false, true).
        done(v => {
            $("#mainForm").html(i18n("about.text", v.serverVersion, version));
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();