({
    map: false,

    init: function () {
        leftSide("fas fa-fw fa-map-marked-alt", i18n("map.map"), "?#map", "map");

        function trim(str) {
            return str.trim ? str.trim() : str.replace(/^\s+|\s+$/g, '');
        }

        function splitWords(str) {
            return trim(str).split(/\s+/);
        }

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

        L.DomUtil.addClass = function (el, name) {
            if (el.classList !== undefined) {
                let classes = splitWords(name);
                for (let i = 0, len = classes.length; i < len; i++) {
                    el.classList.add(classes[i]);
                }
            } else if (!hasClass(el, name)) {
                let className = getClass(el);
                setClass(el, (className ? className + ' ' : '') + name);
            }
        }

        L.DomUtil.removeClass = function (el, name) {
            if (el.classList !== undefined) {
                el.classList.remove(name);
            } else {
                setClass(el, trim((' ' + getClass(el) + ' ').replace(' ' + name + ' ', ' ')));
            }
        }

        L.Util.indexOf = function (array, el) {
            for (var i = 0; i < array.length; i++) {
                if (array[i] === el) { return i; }
            }
            return -1;
        }

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

        modules.map.map = L.map('mapContainer', { editable: true });

        if (config.map && config.map.hideAttribution) {
            $(".leaflet-control-attribution").hide();
        }

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
            lat = (config.map && config.map.default && config.map.default.lat) ? config.map.default.lat : 51.505,
            lon = (config.map && config.map.default && config.map.default.lon) ? config.map.default.lon : -0.09,
            zoom = (config.map && config.map.default && config.map.default.zoom) ? config.map.default.zoom : 13
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

        modules.map.map.addControl(new L.Control.Fullscreen({
            title: {
                'false': i18n("fullscreen"),
                'true': i18n("exitFullscreen"),
            }
        }));

        if (typeof lon != "undefined" && typeof lat != "undefined") {
            modules.map.map.setView([lat, lon], zoom);
            if (!(params.markersLine)) {
                L.marker([lat, lon]).addTo(modules.map.map);
            }
/*
            let layer_markers = new L.markerClusterGroup({ spiderfyOnMaxZoom: false, disableClusteringAtZoom: 15, });

            let homeMarker = L.AwesomeMarkers.icon({
                icon: 'home fas fa-fw fa-home',
                color: 'green'
            });
            for (let i = 0; i < 100; i++) {
                L.marker([lat + Math.random(), lon + Math.random()], {icon: homeMarker}).addTo(layer_markers);
            }
            layer_markers.addTo(modules.map.map);
            modules.map.map.fitBounds(layer_markers.getBounds());

            // polygon editor
            let pl = modules.map.map.editTools.startPolygon(); // start polygon editor

            pl.getLatLngs(); // get polygon points

            modules.map.map.removeLayer(pl);
*/
        } else {
            if (!navigator.geolocation) {
                modules.map.map.setView([lat, lon], zoom);
            } else {
                navigator.geolocation.getCurrentPosition(success => {
                    console.log(success.coords.latitude, success.coords.longitude);
                    modules.map.map.setView([success.coords.latitude, success.coords.longitude], zoom);
                    if (!(params.markersLine)) {
                        L.marker([success.coords.latitude, success.coords.longitude]).addTo(modules.map.map);
                    }
                }, () => {
                    modules.map.map.setView([lat, lon], zoom);
                });
            }
        }

        if (params.markersLine) {
            let cluster = new L.markerClusterGroup({ spiderfyOnMaxZoom: false, disableClusteringAtZoom: 15, });
            let markers = params.markersLine.split("|");
            let line = [];

            for (let i in markers) {
                let marker = markers[i].split(",");
                if (marker.length >= 3) {
                    line.push([ marker[0], marker[1] ]);
                    L.marker([ marker[0], marker[1] ]).bindTooltip(marker[2], { permanent: true, direction: "top", offset: [ -15, -6 ] }).addTo(cluster);
                }
            }
            L.polyline(line, { color: 'red', dashArray: '5, 10' }).addTo(cluster);
            cluster.addTo(modules.map.map);
            modules.map.map.fitBounds(cluster.getBounds());
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