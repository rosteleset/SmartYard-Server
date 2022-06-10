({
    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "#about", true);
        moduleLoaded("about", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");

        $("#mainForm").html(i18n("about.text"));

        loadingDone();
    },
}).init();