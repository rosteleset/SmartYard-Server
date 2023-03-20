({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("cs.sheet", this);
    },

    route: function () {
        loadingDone();
    },
}).init();