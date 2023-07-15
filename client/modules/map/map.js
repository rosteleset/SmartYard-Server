({
    map: false,

    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");
        moduleLoaded("map", this);
    },

    route: function (params) {
        let height = $(window).height() - mainFormTop;

        $("#mainForm").html(`<div id='mapContainer' style='width: 100%; height: ${height}px;' class='mt-2'></div>`);
        
        modules.map.map = L.map('mapContainer').setView([51.505, -0.09], 13);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modules.map.map);

        if (!navigator.geolocation) {
            modules.map.map.setView([51.505, -0.09], 13);
        } else {
            navigator.geolocation.getCurrentPosition(success => {
                modules.map.map.setView([success.coords.latitude, success.coords.longitude], 13);
            }, () => {
                modules.map.map.setView([51.505, -0.09], 13);
            });
        }

        loadingDone();
    },
}).init();