({
    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");
        moduleLoaded("map", this);
    },

    render: function (params) {
        loadingDone();
    },
}).init();