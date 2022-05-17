({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("address.countries", this);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("address.countries");
        $("#mainForm").html(i18n("address.countries"));
        loadingDone();
    },

    search: function (str) {
        console.log("countries: " + str);
    },
}).init();