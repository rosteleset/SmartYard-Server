({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscribers", this);
    },

    route: function (params) {
        $("#altForm").hide();
        subTop(params.house + ", " + params.flat);

        $("#mainForm").html("");

        loadingDone();
    }
}).init();
