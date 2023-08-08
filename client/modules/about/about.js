({
    version: "0.0.1 (beta)",

    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "?#about", "about");
        moduleLoaded("about", this);
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");

        $("#mainForm").html(i18n("about.text", modules.about.version));

        loadingDone();
    },
}).init();