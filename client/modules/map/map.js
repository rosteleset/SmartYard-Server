({
    map: false,

    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");
        moduleLoaded("map", this);
    },

    route: function (params) {
        let height = $(window).height() - mainFormTop;

        $("#mainForm").html(`<div id='mapContainer' style='width: 100%; height: ${height}px; border: solid thin #dee2e6;' class='mt-2'></div>`);
        
        modules.map.map = L.map('mapContainer');

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modules.map.map);

        if (!navigator.geolocation) {
            modules.map.map.setView([51.505, -0.09], 13);
            L.marker([51.5, -0.09]).addTo(modules.map.map);
        } else {
            navigator.geolocation.getCurrentPosition(success => {
                modules.map.map.setView([success.coords.latitude, success.coords.longitude], 13);
                L.marker([success.coords.latitude, success.coords.longitude]).addTo(modules.map.map);
            }, () => {
                modules.map.map.setView([51.505, -0.09], 13);
                L.marker([51.5, -0.09]).addTo(modules.map.map);
            });
        }

        loadingDone();
    },
}).init();