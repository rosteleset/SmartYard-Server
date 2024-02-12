({
    menuItem: false,

    init: function () {
        if (AVAIL("subscribers", "keys")) {
            this.menuItem = leftSide("fab fa-fw fa-keycdn", i18n("addresses.superkeys"), "?#addresses.keys", "households");
        }
        moduleLoaded("addresses.keys", this);
    },
    
    renderKeys: function () {

    },

    addKey: function () {

    },

    removeKey: function () {

    },

    modifyKey: function () {

    },

    route: function (params) {
        if (modules.addresses.keys.menuItem) {
            $("#" + modules.addresses.keys.menuItem).children().first().attr("href", "?#addresses.keys&_=" + Math.random());
        }

        loadingDone();
    },
}).init();