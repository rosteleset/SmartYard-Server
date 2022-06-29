({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscribers", this);
    },

    renderSubscribers: function (list, formTarget) {
        loadingStart();

    },

    route: function (params) {
        subTop(params.house + ", " + params.flat);

        $("#altForm").hide();
        $("#mainForm").html("");

        loadingDone();
    }
}).init();
