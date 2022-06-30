({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.keys", this);
    },

    renderKeys: function (target, targetId, formTarget) {
        $(formTarget).html("").show();
    },

    route: function (params) {
        subTop(params.house + ", " + params.flat);

        loadingDone();
    }
}).init();
