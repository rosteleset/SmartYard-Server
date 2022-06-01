({
    init: function () {
        moduleLoaded("settings", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("settings.settings");
        $("#mainForm").html(i18n("settings.settings"));
        console.log(params);

        loadingDone();
    }
}).init();