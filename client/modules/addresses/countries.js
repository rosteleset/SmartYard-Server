({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.countries", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.countries");
        $("#mainForm").html(i18n("addresses.countries"));

        loadingDone();
    },

    search: function (str) {
        console.log("countries: " + str);
    },
}).init();