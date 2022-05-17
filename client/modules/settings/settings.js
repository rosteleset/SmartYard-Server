({
    init: function () {
        moduleLoaded("settings", this);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("settings.settings");
        $("#mainForm").html(i18n("settings.settings"));
        console.log(params);
        loadingDone();
    }
}).init();