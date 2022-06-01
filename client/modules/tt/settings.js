({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.settings", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let h = '';

        $("#mainForm").html(h);

        loadingDone();
    },
}).init();