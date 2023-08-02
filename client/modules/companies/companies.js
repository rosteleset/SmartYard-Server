({
    init: function () {
        if (AVAIL("companies", "companies")) {
            leftSide("far fa-fw fa-building", i18n("companies.companies"), "?#companies", "households");
        }
        moduleLoaded("companies", this);
    },

    route: function (params) {
        loadingDone();
    },
}).init();