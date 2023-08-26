({
    map: false,

    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");
        moduleLoaded("map", this);
    },

    route: function (params) {
        let height = $(window).height() - mainFormTop;

        document.title = i18n("windowTitle") + " :: " + i18n("map.map");

        $("#altForm").hide();
        $("#mainForm").html(`<div id='mapContainer' style='width: 100%; height: ${height}px; border: solid thin #dee2e6;' class='mt-2'></div>`);
        
        modules.map.map = L.map('mapContainer');

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: 4,
            maxZoom: 20,
        }).addTo(modules.map.map);

        let lat, lon;

        if (params.coords) {
            lat = parseFloat(coords.split(",")[0]);
            lon = parseFloat(coords.split(",")[1]);
        }

        if (params.lat && params.lon) {
            lon = parseFloat(params.lon);
            lat = parseFloat(params.lat);
        }

        if (typeof lon != "undefined" && typeof lat != "undefined") {
            modules.map.map.setView([lat, lon], 13);
            L.marker([lat, lon]).addTo(modules.map.map);
        } else {
            if (!navigator.geolocation) {
                modules.map.map.setView([51.505, -0.09], 13);
            } else {
                navigator.geolocation.getCurrentPosition(success => {
                    console.log(success.coords.latitude, success.coords.longitude);
                    modules.map.map.setView([success.coords.latitude, success.coords.longitude], 13);
                    L.marker([success.coords.latitude, success.coords.longitude]).addTo(modules.map.map);
                }, () => {
                    modules.map.map.setView([51.505, -0.09], 13);
                });
            }
        }

        loadingDone();
    },
}).init();