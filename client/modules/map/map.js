({
    map: false,

    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");

        L.Util.isArray = Array.isArray || function (obj) {
            return (Object.prototype.toString.call(obj) === '[object Array]');
        };

        L.bind = function (fn, obj) {
            let slice = Array.prototype.slice;

            if (fn.bind) {
                return fn.bind.apply(fn, slice.call(arguments, 1));
            }

            let args = slice.call(arguments, 2);

            return function () {
                return fn.apply(obj, args.length ? args.concat(slice.call(arguments)) : arguments);
            };
        };

        L.DomUtil.hasClass = function (el, name) {
            if (el.classList !== undefined) {
                return el.classList.contains(name);
            }
            let className = getClass(el);
            return className.length > 0 && new RegExp('(^|\\s)' + name + '(\\s|$)').test(className);
        };

        moduleLoaded("map", this);
    },

    route: function (params) {
        let height = $(window).height() - mainFormTop;
        if ($('#subTop:visible').length) {
            height -= $('#subTop').height();
        }

        document.title = i18n("windowTitle") + " :: " + i18n("map.map");

        $("#altForm").hide();
        $("#mainForm").html(`<div id='mapContainer' style='width: 100%; height: ${height}px; border: solid thin #dee2e6;' class='mt-2 resizable'></div>`);

        modules.map.map = L.map('mapContainer');

        if (config.map && config.map.crs) {
            switch (config.map.crs) {
                case "EPSG3395":
                    modules.map.map.options.crs = L.CRS.EPSG3395;
                    break;
                case "EPSG3857":
                    modules.map.map.options.crs = L.CRS.EPSG3857;
                    break;
            }
        }

        L.tileLayer((config.map && config.map.tile) ? config.map.tile : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: (config.map && config.map.min) ? config.map.min : 4,
            maxZoom: (config.map && config.map.max) ? config.map.max : 18,
        }).addTo(modules.map.map);

        let
            lat = (config.map && config.map.default && config.map.default.lat)?config.map.default.lat:51.505,
            lon = (config.map && config.map.default && config.map.default.lon)?config.map.default.lon:-0.09,
            zoom = (config.map && config.map.default && config.map.default.zoom)?config.map.default.zoom:13
        ;

        if (params.coords) {
            lat = parseFloat(coords.split(",")[0]);
            lon = parseFloat(coords.split(",")[1]);
        }

        if (params.lat && params.lon) {
            lon = parseFloat(params.lon);
            lat = parseFloat(params.lat);
            if (params.zoom && parseInt(params.zoom)) {
                zoom = parseInt(params.zoom);
            }
        }

//        let layer_markers = new L.markerClusterGroup({ spiderfyOnMaxZoom: false, disableClusteringAtZoom: 15, });

        if (typeof lon != "undefined" && typeof lat != "undefined") {
            modules.map.map.setView([lat, lon], zoom);
            L.marker([lat, lon]).addTo(modules.map.map);
/*
            let homeMarker = L.AwesomeMarkers.icon({
                icon: 'home fas fa-fw fa-home',
                color: 'green'
            });
            for (let i = 0; i < 100; i++) {
                L.marker([lat + Math.random(), lon + Math.random()], {icon: homeMarker}).addTo(layer_markers);
            }
            layer_markers.addTo(modules.map.map);
            modules.map.map.fitBounds(layer_markers.getBounds());
*/
        } else {
            if (!navigator.geolocation) {
                modules.map.map.setView([lat, lon], zoom);
            } else {
                navigator.geolocation.getCurrentPosition(success => {
                    console.log(success.coords.latitude, success.coords.longitude);
                    modules.map.map.setView([success.coords.latitude, success.coords.longitude], zoom);
                    L.marker([success.coords.latitude, success.coords.longitude]).addTo(modules.map.map);
                }, () => {
                    modules.map.map.setView([lat, lon], zoom);
                });
            }
        }

        $("#mapContainer").off("windowResized").on("windowResized", () => {
            let height = $(window).height() - mainFormTop;
            if ($('#subTop:visible').length) {
                height -= $('#subTop').height();
            }
            $("#mapContainer").css("height", height + "px");
        });

        loadingDone();
    },
}).init();