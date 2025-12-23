({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-video", i18n("addresses.cameras"), "?#addresses.cameras", "households");
        }
        moduleLoaded("addresses.cameras", this);
    },

    meta: false,
    map: false,
    fiases: {},
    marker: false,

    doAddCamera: function (camera) {
        loadingStart();
        POST("cameras", "camera", false, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasAdded"))
        }).
        always(modules.addresses.cameras.route);
    },

    doModifyCamera: function (camera, params) {
        loadingStart();
        PUT("cameras", "camera", camera.cameraId, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasChanged"))
        }).
        always(() => {
            modules.addresses.cameras.route(params)
        });
    },

    doDeleteCamera: function (cameraId) {
        loadingStart();
        DELETE("cameras", "camera", cameraId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasDeleted"))
        }).
        always(modules.addresses.cameras.route);
    },

    addCamera: function () {
        let models = [];

        for (let id in modules.addresses.cameras.meta.models) {
            models.push({
                id,
                text: modules.addresses.cameras.meta.models[id].title,
            })
        }

        models.sort((a, b) => {
            if (a.text.toLowerCase() > b.text.toLowerCase()) {
                return 1;
            }
            if (a.text.toLowerCase() < b.text.toLowerCase()) {
                return -1;
            }
            return 0;
        });

        frss = [];

        frss.push({
            value: "-",
            text: i18n('no'),
        });

        for (let i in modules.addresses.cameras.meta.frsServers) {
            frss.push({
                value: modules.addresses.cameras.meta.frsServers[i].url,
                text: modules.addresses.cameras.meta.frsServers[i].title,
            });
        }

        cardForm({
            title: i18n("addresses.addCamera"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            apply: i18n("add"),
            fields: [
                {
                    id: "enabled",
                    type: "yesno",
                    title: i18n("addresses.enabled"),
                    value: "1",
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "model",
                    type: "select2",
                    title: i18n("addresses.model"),
                    placeholder: i18n("addresses.model"),
                    options: models,
                    tab: i18n("addresses.primary")
                },
                {
                    id: "url",
                    type: "text",
                    title: i18n("addresses.url"),
                    placeholder: "http://",
                    validate: v => {
                        try {
                            if (v && !/^https?:\/\/.+/.test(v)) {
                                throw new Error();;
                            }
                            new URL(v);
                            return true;
                        } catch (_) {
                            return false;
                        }
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "stream",
                    type: "text",
                    title: i18n("addresses.stream"),
                    placeholder: "rtsp://",
                    validate: v => {
                        if (v) {
                            try {
                                if (!/^rtsp:\/\/.+/.test(v)) {
                                    throw new Error();
                                }
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        } else {
                            return true;
                        }
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("addresses.credentials"),
                    placeholder: i18n("addresses.credentials"),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "name",
                    type: "text",
                    title: i18n("addresses.cameraName"),
                    placeholder: i18n("addresses.cameraName"),
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "dvrStream",
                    type: "text",
                    title: i18n("addresses.dvrStream"),
                    placeholder: "https://",
                    validate: v => {
                        if (v) {
                            try {
                                if (!/^https?:\/\/.+/.test(v)) {
                                    throw new Error();
                                }
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        } else {
                            return true;
                        }
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "sound",
                    type: "noyes",
                    title: i18n("addresses.sound"),
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: timezonesOptions(),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                    value: "-",
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "frs",
                    type: "select2",
                    title: i18n("addresses.frs"),
                    value: "-",
                    options: frss,
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "frsMode",
                    type: "select2",
                    title: i18n("addresses.frsMode"),
                    value: "1",
                    options: [
                        {
                            value: "0",
                            text: i18n("addresses.frsNone"),

                        },
                        {
                            value: "1",
                            text: i18n("addresses.frsRecognition"),

                        },
                        {
                            value: "2",
                            text: i18n("addresses.frsDetection"),

                        },
                    ],
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "geo",
                    type: "text",
                    title: i18n("addresses.geo"),
                    placeholder: "0.0,0.0",
                    hint: i18n("addresses.lat") + "," + i18n("addresses.lon").toLowerCase(),
                    value: "0.0,0.0",
                    validate: v => {
                        const regex = new RegExp('^[+-]?((\\d+\\.?\\d*)|(\\.\\d+)),[+-]?((\\d+\\.?\\d*)|(\\.\\d+))$', 'gm');

                        return regex.exec(v) !== null;
                    },
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "position",
                    type: "text",
                    title: i18n("addresses.position"),
                    placeholder: "0,0,0",
                    hint: i18n("addresses.direction") + "," + i18n("addresses.angle").toLowerCase() + "," + i18n("addresses.distance").toLowerCase(),
                    value: "0,0,0",
                    validate: v => {
                        const regex = new RegExp('^\\d+,\\d+,\\d+$', 'gm');

                        return regex.exec(v) !== null;
                    },
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "common",
                    type: "noyes",
                    title: i18n("addresses.common"),
                    placeholder: i18n("addresses.common"),
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "monitoring",
                    type: "yesno",
                    title: i18n("addresses.monitoring"),
                    placeholder: i18n("addresses.monitoring"),
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "webrtc",
                    type: "noyes",
                    title: i18n("addresses.webrtc"),
                    placeholder: i18n("addresses.webrtc"),
                    tab: i18n("addresses.webrtc"),
                },
                {
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                    tab: i18n("addresses.secondary"),
                },
                {
                    id: "geoSuggestion",
                    type: "select2",
                    title: false,
                    placeholder: i18n("addresses.address"),
                    tab: i18n("addresses.map"),
                    hidden: !AVAIL("geo", "suggestions") || !modules.map,
                    ajax: {
                        delay: 1000,
                        transport: function (params, success) {
                            if (params.data.term) {
                                QUERY("geo", "suggestions", {
                                    search: params.data.term,
                                }).
                                then(success).
                                fail(response => {
                                    FAIL(response);
                                    success({
                                        suggestions: [],
                                    });
                                });
                            } else {
                                success({
                                    suggestions: [],
                                });
                            }
                        },
                        processResults: function (data) {
                            let suggestions = [];
                            for (let i in data.suggestions) {
                                if (parseInt(data.suggestions[i].data.fias_level) === 8 || (parseInt(data.suggestions[i].data.fias_level) === -1 && data.suggestions[i].data.house)) {
                                    suggestions.push({
                                        id: data.suggestions[i].data.house_fias_id,
                                        text: data.suggestions[i].value,
                                    });
                                    modules.addresses.cameras.fiases[data.suggestions[i].data.house_fias_id] = data.suggestions[i].data;
                                }
                            }
                            return {
                                results: suggestions,
                            };
                        },
                    },
                },
                {
                    id: "geoMap",
                    type: "empty",
                    title: false,
                    placeholder: i18n("search"),
                    tab: i18n("addresses.map"),
                    noHover: true,
                },
                {
                    id: "ext",
                    type: "json",
                    title: false,
                    tab: i18n("addresses.ext"),
                    noHover: true,
                },
            ],
            done: function (prefix) {
                $("#" + prefix + "geoSuggestion").off("change").on("change", e => {
                    let fias = $("#" + prefix + "geoSuggestion").val();
                    if (modules.addresses.cameras.fiases[fias] && modules.addresses.cameras.fiases[fias].geo_lat && modules.addresses.cameras.fiases[fias].geo_lon) {
                        modules.addresses.cameras.map.setView([modules.addresses.cameras.fiases[fias].geo_lat, modules.addresses.cameras.fiases[fias].geo_lon], 18);
                        modules.addresses.cameras.marker.setLatLng([modules.addresses.cameras.fiases[fias].geo_lat, modules.addresses.cameras.fiases[fias].geo_lon]).update();
                        $("#" + prefix + "geo").val(modules.addresses.cameras.marker.getLatLng().lat + "," + modules.addresses.cameras.marker.getLatLng().lng);
                    }
                });

                $("#" + prefix + "geoMap").css("height", "400px");

                modules.addresses.cameras.map = L.map(prefix + "geoMap");

                if (config.map && config.map.crs) {
                    switch (config.map.crs) {
                        case "EPSG3395":
                            modules.addresses.cameras.map.options.crs = L.CRS.EPSG3395;
                            break;
                        case "EPSG3857":
                            modules.addresses.cameras.map.options.crs = L.CRS.EPSG3857;
                            break;
                    }
                }

                let
                    lat = (config.map && config.map.default && config.map.default.lat) ? config.map.default.lat : 51.505,
                    lon = (config.map && config.map.default && config.map.default.lon) ? config.map.default.lon : -0.09,
                    zoom = (config.map && config.map.default && config.map.default.zoom) ? config.map.default.zoom : 13
                ;

                L.tileLayer((config.map && config.map.tile) ? config.map.tile : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    minZoom: (config.map && config.map.min) ? config.map.min : 4,
                    maxZoom: (config.map && config.map.max) ? config.map.max : 18,
                }).addTo(modules.addresses.cameras.map);

                modules.addresses.cameras.map.setView([lat, lon], zoom);
                modules.addresses.cameras.marker = L.marker([lat, lon], { draggable: true }).addTo(modules.addresses.cameras.map);

                modules.addresses.cameras.map.addControl(new L.Control.Fullscreen({
                    title: {
                        'false': i18n("fullscreen"),
                        'true': i18n("exitFullscreen"),
                    }
                }));

                modules.addresses.cameras.marker.on('dragend', () => {
                    $("#" + prefix + "geo").val(modules.addresses.cameras.marker.getLatLng().lat + "," + modules.addresses.cameras.marker.getLatLng().lng);
                });

                if (config.map && config.map.hideAttribution) {
                    $(".leaflet-control-attribution").hide();
                }
            },
            tabActivate: function (prefix, tab) {
                if (tab == i18n("addresses.map")) {
                    modules.addresses.cameras.map.invalidateSize();
                }
            },
            callback: result => {
                let g = result.geo.split(",");
                result.lat = $.trim(g[0]);
                result.lon = $.trim(g[1]);
                let p = result.position.split(",")
                result.direction = $.trim(p[0]);
                result.angle = $.trim(p[1]);
                result.distance = $.trim(p[2]);
                result.rcArea = [];
                result.mdArea = [];
                modules.addresses.cameras.doAddCamera(result);
            },
        });
    },

    modifyCamera: function (cameraId, params) {
        let models = [];

        for (let id in modules.addresses.cameras.meta.models) {
            models.push({
                id,
                text: modules.addresses.cameras.meta.models[id].title,
            })
        }

        models.sort((a, b) => {
            if (a.text.toLowerCase() > b.text.toLowerCase()) {
                return 1;
            }
            if (a.text.toLowerCase() < b.text.toLowerCase()) {
                return -1;
            }
            return 0;
        });

        let camera = false;

        for (let i in modules.addresses.cameras.meta.cameras) {
            if (modules.addresses.cameras.meta.cameras[i].cameraId == cameraId) {
                camera = modules.addresses.cameras.meta.cameras[i];
                break;
            }
        }

        frss = [];

        frss.push({
            value: "-",
            text: i18n('no'),
        });

        for (let i in modules.addresses.cameras.meta.frsServers) {
            frss.push({
                value: modules.addresses.cameras.meta.frsServers[i].url,
                text: modules.addresses.cameras.meta.frsServers[i].title,
            });
        }

        if (camera) {
            let mdArea, rcArea, image;

            for (let i in modules.addresses.cameras.meta.tree) {
                if (modules.addresses.cameras.meta.tree[i].tree[modules.addresses.cameras.meta.tree[i].tree.length - 1] == ".") {
                    modules.addresses.cameras.meta.tree[i].tree = modules.addresses.cameras.meta.tree[i].tree.substr(0, modules.addresses.cameras.meta.tree[i].tree.length - 1);
                }
                modules.addresses.cameras.meta.tree[i].id = modules.addresses.cameras.meta.tree[i].tree + ".";
                modules.addresses.cameras.meta.tree[i].text = modules.addresses.cameras.meta.tree[i].name;
                modules.addresses.cameras.meta.tree[i].state = (modules.addresses.cameras.meta.tree[i].id == camera.tree) ? { selected: true, } : {};
            }

            let t = buildTreeFromPaths(modules.addresses.cameras.meta.tree);

            cardForm({
                title: i18n("addresses.modifyCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "xl",
                apply: i18n("edit"),
                delete: i18n("addresses.deleteCamera"),
                deleteTab: i18n("addresses.secondary"),
                fields: [
                    {
                        id: "cameraId",
                        type: "text",
                        title: i18n("addresses.cameraId"),
                        value: cameraId,
                        readonly: true,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("addresses.enabled"),
                        value: camera.enabled,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("addresses.model"),
                        placeholder: i18n("addresses.model"),
                        options: models,
                        value: camera.model,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "url",
                        type: "text",
                        title: i18n("addresses.url"),
                        placeholder: "http://",
                        value: camera.url,
                        validate: v => {
                            try {
                                if (v && !/^https?:\/\/.+/.test(v)) {
                                    throw new Error();
                                }
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "stream",
                        type: "text",
                        title: i18n("addresses.stream"),
                        placeholder: "rtsp://",
                        value: camera.stream,
                        validate: v => {
                            if (v) {
                                try {
                                    if (!/^rtsp:\/\/.+/.test(v)) {
                                        throw new Error();
                                    }
                                    new URL(v);
                                    return true;
                                } catch (_) {
                                    return false;
                                }
                            } else {
                                return true;
                            }
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("addresses.credentials"),
                        placeholder: i18n("addresses.credentials"),
                        value: camera.credentials,
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "name",
                        type: "text",
                        title: i18n("addresses.cameraName"),
                        placeholder: i18n("addresses.cameraName"),
                        value: camera.name,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "dvrStream",
                        type: "text",
                        title: i18n("addresses.dvrStream"),
                        placeholder: "https://",
                        value: camera.dvrStream,
                        validate: v => {
                            if (v) {
                                try {
                                    if (!/^https?:\/\/.+/.test(v)) {
                                        throw new Error();
                                    }
                                    new URL(v);
                                    return true;
                                } catch (_) {
                                    return false;
                                }
                            } else {
                                return true;
                            }
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "sound",
                        type: "yesno",
                        title: i18n("addresses.sound"),
                        value: camera.sound,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "frs",
                        type: "select2",
                        title: i18n("addresses.frs"),
                        value: camera.frs,
                        options: frss,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "frsMode",
                        type: "select2",
                        title: i18n("addresses.frsMode"),
                        value: camera.frsMode,
                        options: [
                            {
                                value: "0",
                                text: i18n("addresses.frsNone"),

                            },
                            {
                                value: "1",
                                text: i18n("addresses.frsRecognition"),

                            },
                            {
                                value: "2",
                                text: i18n("addresses.frsDetection"),

                            },
                        ],
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "mdArea",
                        type: "empty",
                        title: false,
                        tab: i18n("addresses.md"),
                        noHover: true,
                    },
                    {
                        id: "rcArea",
                        type: "empty",
                        title: false,
                        tab: i18n("addresses.rc"),
                        noHover: true,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: timezonesOptions(),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: camera.timezone,
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "geo",
                        type: "text",
                        title: i18n("addresses.geo"),
                        placeholder: "0.0,0.0",
                        hint: i18n("addresses.lat") + "," + i18n("addresses.lon").toLowerCase(),
                        value: camera.lat + "," + camera.lon,
                        validate: v => {
                            const regex = new RegExp('^[+-]?((\\d+\\.?\\d*)|(\\.\\d+)),[+-]?((\\d+\\.?\\d*)|(\\.\\d+))$', 'gm');
                            return regex.exec(v) !== null;
                        },
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "position",
                        type: "text",
                        title: i18n("addresses.position"),
                        placeholder: "0,0.0",
                        value: camera.direction + "," + camera.angle + "," + camera.distance,
                        hint: i18n("addresses.direction") + "," + i18n("addresses.angle").toLowerCase() + "," + i18n("addresses.distance").toLowerCase(),
                        validate: v => {
                            const regex = new RegExp('^\\d+,\\d+,\\d+$', 'gm');
                            return regex.exec(v) !== null;
                        },
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "common",
                        type: "noyes",
                        title: i18n("addresses.common"),
                        placeholder: i18n("addresses.common"),
                        value: camera.common,
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "monitoring",
                        type: "yesno",
                        title: i18n("addresses.monitoring"),
                        placeholder: i18n("addresses.monitoring"),
                        value: camera.monitoring,
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "webrtc",
                        type: "noyes",
                        title: i18n("addresses.webrtc"),
                        placeholder: i18n("addresses.webrtc"),
                        value: camera.webrtc,
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "comments",
                        type: "text",
                        title: i18n("addresses.comments"),
                        placeholder: i18n("addresses.comments"),
                        value: camera.comments,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "geoSuggestion",
                        type: "select2",
                        title: false,
                        placeholder: i18n("addresses.address"),
                        tab: i18n("addresses.map"),
                        hidden: !AVAIL("geo", "suggestions") || !modules.map,
                        ajax: {
                            delay: 1000,
                            transport: function (params, success) {
                                if (params.data.term) {
                                    QUERY("geo", "suggestions", {
                                        search: params.data.term,
                                    }).
                                    then(success).
                                    fail(response => {
                                        FAIL(response);
                                        success({
                                            suggestions: [],
                                        });
                                    });
                                } else {
                                    success({
                                        suggestions: [],
                                    });
                                }
                            },
                            processResults: function (data) {
                                let suggestions = [];
                                for (let i in data.suggestions) {
                                    if (parseInt(data.suggestions[i].data.fias_level) === 8 || (parseInt(data.suggestions[i].data.fias_level) === -1 && data.suggestions[i].data.house)) {
                                        suggestions.push({
                                            id: data.suggestions[i].data.house_fias_id,
                                            text: data.suggestions[i].value,
                                        });
                                        modules.addresses.cameras.fiases[data.suggestions[i].data.house_fias_id] = data.suggestions[i].data;
                                    }
                                }
                                return {
                                    results: suggestions,
                                };
                            },
                        },
                    },
                    {
                        id: "geoMap",
                        type: "empty",
                        title: false,
                        tab: i18n("addresses.map"),
                        noHover: true,
                    },
                    {
                        id: "tree",
                        type: "jstree",
                        title: false,
                        tab: i18n("addresses.path"),
                        data: t,
                        value: camera.tree,
/*
                        addRoot: function (instance) {
                            POST("houses", "path", treeName, {
                                text: i18n("addresses.newNode"),
                            }).done(result => {
                                if (result && result.nodeId) {
                                    let node = {
                                        id: result.nodeId,
                                        text: i18n("addresses.newNode"),
                                    };
                                    instance.jstree().create_node("#", node, 'last', newNode => {
                                        setTimeout(() => {
                                            instance.jstree().deselect_all();
                                            instance.jstree().select_node(newNode);
                                            instance.jstree().edit(newNode);
                                        }, 100);
                                    });
                                }
                            }).fail(FAIL);
                        },

                        add: function (instance) {
                            let parent = instance.jstree().get_selected();
                            parent = parent.length ? parent[0] : "#";
                            POST("houses", "path", (parent === "#") ? treeName : parent, {
                                text: i18n("addresses.newNode"),
                            }).done(result => {
                                if (result && result.nodeId) {
                                    let node = {
                                        id: result.nodeId,
                                        text: i18n("addresses.newNode"),
                                    };
                                    modules.addresses.houses.pathNodes[result.nodeId] = i18n("addresses.newNode");
                                    instance.jstree().create_node(parent, node, 'last', newNode => {
                                        setTimeout(() => {
                                            instance.jstree().deselect_all();
                                            instance.jstree().select_node(newNode);
                                            instance.jstree().edit(newNode);
                                        }, 100);
                                    });
                                }
                            }).fail(FAIL);
                        },

                        rename: function (instance) {
                            let node = instance.jstree().get_selected();
                            if (node && node.length) {
                                node = instance.jstree().get_node(node[0]);
                                modules.addresses.houses.pathNodes[node.id] = node.text;
                                setTimeout(() => {
                                    instance.jstree().edit(node);
                                }, 100);
                            }
                        },

                        renamed: function (e, data) {
                            if (data && data.obj && data.obj.id && data.text && data.text != modules.addresses.houses.pathNodes[data.obj.id]) {
                                PUT("houses", "path", data.obj.id, {
                                    text: data.text,
                                }).
                                done(() => {
                                    modules.addresses.houses.pathNodes[data.obj.id] = data.text;
                                }).
                                fail(FAIL);
                            }
                        },

                        delete: function (instance) {
                            let node = instance.jstree().get_selected();
                            if (node && node.length) {
                                node = instance.jstree().get_node(node[0]);
                                mConfirm(i18n("addresses.confirmDeleteNode", escapeHTML(node.text)), i18n("confirm"), `danger:${i18n("addresses.deleteNode")}`, () => {
                                    DELETE("houses", "path", node.id).
                                    done(() => {
                                        instance.jstree().delete_node(node.id);
                                    }).
                                    fail(FAIL);
                                });
                            }
                        },

                        search: function (instance, str) {
                            if (str) {
                                QUERYID("houses", "path", treeName, { search: str }).
                                done(result => {
                                    instance.jstree().settings.core.data = result.tree;
                                    instance.jstree().refresh();
                                    setTimeout(() => {
                                        instance.jstree().search(str);
                                        instance.jstree().settings.core.data = path;
                                    }, 100);
                                }).
                                fail(FAIL);
                            } else {
                                instance.jstree().clear_search();

                                QUERYID("houses", "path", entrance.path ? entrance.path : treeName, {
                                    withParents: true,
                                    tree: treeName,
                                }).
                                done(result => {
                                    if (result && result.tree) {
                                        instance.jstree().settings.core.data = result.tree;
                                    } else {
                                        instance.jstree().settings.core.data = [];
                                    }
                                    instance.jstree().refresh();
                                }).
                                fail(FAIL);

                                setTimeout(() => {
                                    instance.jstree().select_node(entrance.path);
                                    instance.jstree().settings.core.data = path;
                                }, 100);
                            }
                        }
*/
                    },
                    {
                        id: "ext",
                        type: "json",
                        title: false,
                        tab: i18n("addresses.ext"),
                        value: camera.ext,
                        noHover: true,
                    },
                ],
                done: function (prefix) {
                    $("#" + prefix + "geoSuggestion").off("change").on("change", e => {
                        let fias = $("#" + prefix + "geoSuggestion").val();
                        if (modules.addresses.cameras.fiases[fias] && modules.addresses.cameras.fiases[fias].geo_lat && modules.addresses.cameras.fiases[fias].geo_lon) {
                            modules.addresses.cameras.map.setView([modules.addresses.cameras.fiases[fias].geo_lat, modules.addresses.cameras.fiases[fias].geo_lon], 18);
                            modules.addresses.cameras.marker.setLatLng([modules.addresses.cameras.fiases[fias].geo_lat, modules.addresses.cameras.fiases[fias].geo_lon]).update();
                            $("#" + prefix + "geo").val(modules.addresses.cameras.marker.getLatLng().lat + "," + modules.addresses.cameras.marker.getLatLng().lng);
                        }
                    });

                    $("#" + prefix + "geoMap").css("height", "400px");

                    modules.addresses.cameras.map = L.map(prefix + "geoMap");

                    if (config.map && config.map.crs) {
                        switch (config.map.crs) {
                            case "EPSG3395":
                                modules.addresses.cameras.map.options.crs = L.CRS.EPSG3395;
                                break;
                            case "EPSG3857":
                                modules.addresses.cameras.map.options.crs = L.CRS.EPSG3857;
                                break;
                        }
                    }

                    let
                        lat = (config.map && config.map.default && config.map.default.lat) ? config.map.default.lat : 51.505,
                        lon = (config.map && config.map.default && config.map.default.lon) ? config.map.default.lon : -0.09,
                        zoom = (config.map && config.map.default && config.map.default.zoom) ? config.map.default.zoom : 13
                    ;

                    if (parseFloat(camera.lat) && parseFloat(camera.lon)) {
                        lat = camera.lat;
                        lon = camera.lon;
                        zoom = 18;
                    }

                    L.tileLayer((config.map && config.map.tile) ? config.map.tile : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        minZoom: (config.map && config.map.min) ? config.map.min : 4,
                        maxZoom: (config.map && config.map.max) ? config.map.max : 18,
                    }).addTo(modules.addresses.cameras.map);

                    modules.addresses.cameras.map.setView([lat, lon], zoom);
                    modules.addresses.cameras.marker = L.marker([lat, lon], { draggable: true }).addTo(modules.addresses.cameras.map);

                    modules.addresses.cameras.map.addControl(new L.Control.Fullscreen({
                        title: {
                            'false': i18n("fullscreen"),
                            'true': i18n("exitFullscreen"),
                        }
                    }));

                    modules.addresses.cameras.marker.on('dragend', () => {
                        $("#" + prefix + "geo").val(modules.addresses.cameras.marker.getLatLng().lat + "," + modules.addresses.cameras.marker.getLatLng().lng);
                    });

                    if (config.map && config.map.hideAttribution) {
                        $(".leaflet-control-attribution").hide();
                    }

                    let h = '';
                    h += `<div id="${prefix}md"></div>`;
                    h += `<div class="mt-2">`;
                    h += `<button id="${prefix}mdClear" type="button" class="btn btn-danger mr-2" title="${i18n("addresses.areaClear")}"><i class="fas fa-fw fa-eraser"></i></button>`;
                    h += `<button id="${prefix}mdRevert" type="button" class="btn btn-warning mr-2" title="${i18n("addresses.areaRevert")}"><i class="fas fa-fw fa-undo"></i></button>`;
                    h += `</div>`;

                    $("#" + prefix + "mdArea").html(h);

                    h = '';
                    h += `<div id="${prefix}rc"></div>`;
                    h += `<div class="mt-2">`;
                    h += `<button id="${prefix}rcClear" type="button" class="btn btn-danger mr-2" title="${i18n("addresses.areaClear")}"><i class="fas fa-fw fa-eraser"></i></button>`;
                    h += `<button id="${prefix}rcRevert" type="button" class="btn btn-warning mr-2" title="${i18n("addresses.areaRevert")}"><i class="fas fa-fw fa-undo"></i></button>`;
                    h += `</div>`;

                    $("#" + prefix + "rcArea").html(h);

                    $(`#${prefix}mdClear`).off("click").on("click", () => {
                        mdArea = [];
                        rectangles(`${prefix}md`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", [], r => {
                            mdArea = r;
                        });
                        xblur();
                    });

                    $(`#${prefix}mdRevert`).off("click").on("click", () => {
                        rectangles(`${prefix}md`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", camera.mdArea, r => {
                            mdArea = r;
                        });
                        xblur();
                    });

                    $(`#${prefix}rcClear`).off("click").on("click", () => {
                        rcArea = [];
                        polygon(`${prefix}rc`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", [], r => {
                            rcArea = r;
                        });
                        xblur();
                    });

                    $(`#${prefix}rcRevert`).off("click").on("click", () => {
                        polygon(`${prefix}rc`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", camera.rcArea, r => {
                            rcArea = r;
                        });
                        xblur();
                    });
                },
                tabActivate: function (prefix, tab) {
                    if (tab == i18n("addresses.md")) {
                        rectangles(`${prefix}md`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", camera.mdArea, r => {
                            mdArea = r;
                        });
                    }

                    if (tab == i18n("addresses.rc")) {
                        polygon(`${prefix}rc`, callback => {
                            if (image) {
                                if (typeof callback == "function") {
                                    callback(image);
                                }
                            } else {
                                GET("cameras", "camshot", cameraId, true).
                                done(r => {
                                    if (r && r.shot) {
                                        image = "data:image/jpg;base64," + r.shot;
                                        if (typeof callback == "function") {
                                            callback("data:image/jpg;base64," + r.shot);
                                        }
                                    }
                                }).
                                fail(FAIL);
                            }
                        }, image ? image: "img/cctv.png", camera.rcArea, r => {
                            rcArea = r;
                        });
                    }

                    if (tab == i18n("addresses.map")) {
                        modules.addresses.cameras.map.invalidateSize();
                    }
                },
                callback: result => {
                    let g = result.geo.split(",");
                    result.lat = $.trim(g[0]);
                    result.lon = $.trim(g[1]);
                    let p = result.position.split(",")
                    result.direction = $.trim(p[0]);
                    result.angle = $.trim(p[1]);
                    result.distance = $.trim(p[2]);
                    if (mdArea) {
                        result.mdArea = mdArea;
                    } else {
                        result.mdArea = camera.mdArea;
                    }
                    if (rcArea) {
                        result.rcArea = rcArea;
                    } else {
                        result.rcArea = camera.rcArea;
                    }
                    if (result.delete === "yes") {
                        modules.addresses.cameras.deleteCamera(cameraId);
                    } else {
                        modules.addresses.cameras.doModifyCamera(result, params);
                    }
                },
            });
        } else {
            error(i18n("addresses.cameraNotFound"));
        }
    },

    deleteCamera: function (cameraId) {
        mConfirm(i18n("addresses.confirmDeleteCamera", cameraId), i18n("confirm"), `danger:${i18n("addresses.deleteCamera")}`, () => {
            modules.addresses.cameras.doDeleteCamera(cameraId);
        });
    },

    handleDeviceStatus: function (status) {
        let statusClass;
        switch (status) {
            case 'OK':
                statusClass = 'status-ok';
                break;
            case 'Offline':
                statusClass = 'status-offline';
                break;
            case 'Disabled':
                statusClass = 'status-disabled';
                break;
            case 'DVR error':
                statusClass = 'status-dvr-err';
                break;
            case 'Other':
                statusClass = 'status-other-error';
                break;
            default:
                if (status == i18n("addresses.disabled")) {
                    statusClass = 'status-disabled';
                    status = i18n("addresses.disabled")
                } else {
                    statusClass = 'status-unknown';
                    status = i18n("addresses.unknown")
                }
        }
        return `
            <div class="status-container">
                <span class="status-indicator ${statusClass}" title="${status}"></span>
            </div>
        `;
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.cameras");

        if (params.filter && typeof params.filter !== "function") {
            lStore("cameras.filter", params.filter);
            modules.addresses.cameras.filter = params.filter;
        } else {
            modules.addresses.cameras.filter = lStore("cameras.filter");
        }

        QUERY("cameras", "cameras", { by: (params.tree ? "tree" : false), query: (params.tree ? params.tree : false) }, true).
        done(response => {
            modules.addresses.cameras.meta = response.cameras;

            if (response.cameras.tree != "unavailable") {
                modules.addresses.treePath(response.cameras.tree, params.tree);
            } else {
                subTop();
            }

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.cameras"),
                    button: {
                        caption: i18n("addresses.addCamera"),
                        click: modules.addresses.cameras.addCamera,
                    },
                    filter: modules.addresses.cameras.filter ? modules.addresses.cameras.filter : true,
                    filterChange: f => {
                        lStore("cameras.filter", f);
                        modules.addresses.cameras.filter = f;
                    },
                },
                edit: id => {
                    modules.addresses.cameras.modifyCamera(id, params);
                },
                columns: [
                    {
                        title: i18n("addresses.cameraIdList"),
                    },
                    {
                        title: i18n("addresses.status"),
                    },
                    {
                        title: i18n("addresses.url"),
                    },
                    {
                        title: i18n("addresses.common"),
                    },
                    {
                        title: i18n("addresses.model"),
                    },
                    {
                        title: i18n("addresses.cameraName"),
                    },
                    {
                        title: i18n("addresses.comments"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.cameras.meta.cameras) {
                        if (!params.id || params.id == modules.addresses.cameras.meta.cameras[i].cameraId) {
                            rows.push({
                                uid: modules.addresses.cameras.meta.cameras[i].cameraId,
                                cols: [
                                    {
                                        data: modules.addresses.cameras.meta.cameras[i].cameraId,
                                    },
                                    {
                                        data: (modules.addresses.cameras.meta.cameras[i].enabled && modules.addresses.cameras.meta.cameras[i].monitoring)
                                            ? modules.addresses.cameras.handleDeviceStatus(
                                                modules.addresses.cameras.meta.cameras[i].status
                                                    ? modules.addresses.cameras.meta.cameras[i].status.status : i18n("addresses.unknown"))
                                            : modules.addresses.cameras.handleDeviceStatus(i18n("addresses.disabled")),
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.cameras.meta.cameras[i].url,
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.cameras.meta.cameras[i].common ? i18n("addresses.yes") : i18n("addresses.no"),
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.cameras.meta.models[modules.addresses.cameras.meta.cameras[i].model]?.title ?? "&nbsp;",
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.cameras.meta.cameras[i].name ? modules.addresses.cameras.meta.cameras[i].name : "&nbsp;",
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.cameras.meta.cameras[i].comments ? modules.addresses.cameras.meta.cameras[i].comments : "&nbsp;",
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();