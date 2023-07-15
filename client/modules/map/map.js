({
    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");
    },

    render: function (params) {
        loadingDone();
    },
}).init();