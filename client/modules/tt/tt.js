({
    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt");
        }
        loadSubModules("tt", [
            "createIssue",
            "settings",
        ], () => {
            moduleLoaded("tt", this);
        })
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
        $("#mainForm").html(i18n("tt.tt"));

        loadingDone();
    }
}).init();