({
    init: function () {
        leftSide("fas fa-fw fa-video", i18n("cameras.cameras"), "#cameras");
        moduleLoaded("cameras", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("cameras.cameras");

        $("#mainForm").html(nl2br(i18n("cameras.cameras")));

        loadingDone();
    },
}).init();