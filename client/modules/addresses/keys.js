({
    init: function () {
        if (AVAIL("subscribers", "keys")) {
            leftSide("fab fa-fw fa-keycdn", i18n("addresses.superkeys"), "?#addresses.keys", "households");
        }
        moduleLoaded("addresses.keys", this);
    },

    route: function (params) {
        loadingDone();
    },
}).init();