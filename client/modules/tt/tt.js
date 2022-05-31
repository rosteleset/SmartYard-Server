({
    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt");
        }
        moduleLoaded("tt", this);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
        $("#mainForm").html(i18n("tt.tt"));

        $("#altForm").hide();
        loadingDone();
    }
}).init();