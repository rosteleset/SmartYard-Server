({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-video", i18n("addresses.cameras"), "?#addresses.cameras", "households");
        }
        moduleLoaded("addresses.cameras", this);
    },

    meta: false,

    doAddCamera: function (camera) {
        loadingStart();
        POST("cameras", "camera", false, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasAdded"))
        }).
        always(modules.addresses.cameras.route);
    },

    doModifyCamera: function (camera) {
        loadingStart();
        PUT("cameras", "camera", camera.cameraId, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasChanged"))
        }).
        always(modules.addresses.cameras.route);
    },

    doDeleteCamera: function (cameraId) {
        loadingStart();
        DELETE("cameras", "camera", cameraId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasDeleted"))
        }).
        always(() => {
            modules.addresses.cameras.route();
        });
    },

    addCamera: function () {
        let models = [];
        let first;

        for (let id in modules.addresses.cameras.meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules.addresses.cameras.meta.models[id].title,
            })
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
                    validate: (v) => {
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
                    id: "md",
                    type: "text",
                    title: i18n("addresses.md"),
                    hint: i18n("addresses.left") + "," + i18n("addresses.right").toLowerCase() + "," + i18n("addresses.width").toLowerCase() + "," + i18n("addresses.height").toLowerCase(),
                    placeholder: "0,0,0,0",
                    value: "0,0,0,0",
                    validate: v => {
                        const regex = new RegExp('^\\d+,\\d+,\\d+,\\d+$', 'gm');

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
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                    tab: i18n("addresses.secondary"),
                },
            ],
            callback: result => {
                let g = result.geo.split(",");
                result.lat = $.trim(g[0]);
                result.lon = $.trim(g[1]);
                let p = result.position.split(",")
                result.direction = $.trim(p[0]);
                result.angle = $.trim(p[1]);
                result.distance = $.trim(p[2]);
                let m = result.md.split(",");
                result.mdLeft = $.trim(m[0]);
                result.mdTop = $.trim(m[1]);
                result.mdWidth = $.trim(m[2]);
                result.mdHeight = $.trim(m[3]);
                modules.addresses.cameras.doAddCamera(result);
            },
        });
    },

    modifyCamera: function (cameraId) {
        let models = [];
        let first;

        for (let id in modules.addresses.cameras.meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules.addresses.cameras.meta.models[id].title,
            })
        }

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
            cardForm({
                title: i18n("addresses.modifyCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "lg",
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
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: timezonesOptions(),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: camera.timezone,
                        tab: i18n("addresses.secondary"),
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
                        id: "md",
                        type: "text",
                        title: i18n("addresses.md"),
                        placeholder: "0,0,0,0",
                        hint: i18n("addresses.left") + "," + i18n("addresses.right").toLowerCase() + "," + i18n("addresses.width").toLowerCase() + "," + i18n("addresses.height").toLowerCase(),
                        value: camera.mdLeft + "," + camera.mdTop + "," + camera.mdWidth + "," + camera.mdHeight,
                        validate: v => {
                            const regex = new RegExp('^\\d+,\\d+,\\d+,\\d+$', 'gm');
                            return regex.exec(v) !== null;
                        },
                        tab: i18n("addresses.secondary"),
                    },
                    {
                        id: "common",
                        type: "yesno",
                        title: i18n("addresses.common"),
                        placeholder: i18n("addresses.common"),
                        value: camera.common,
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
                ],
                callback: result => {
                    let g = result.geo.split(",");
                    result.lat = $.trim(g[0]);
                    result.lon = $.trim(g[1]);
                    let p = result.position.split(",")
                    result.direction = $.trim(p[0]);
                    result.angle = $.trim(p[1]);
                    result.distance = $.trim(p[2]);
                    let m = result.md.split(",");
                    result.mdLeft = $.trim(m[0]);
                    result.mdTop = $.trim(m[1]);
                    result.mdWidth = $.trim(m[2]);
                    result.mdHeight = $.trim(m[3]);
                    if (result.delete === "yes") {
                        modules.addresses.cameras.deleteCamera(cameraId);
                    } else {
                        modules.addresses.cameras.doModifyCamera(result);
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

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.cameras");

        GET("cameras", "cameras", false, true).
        done(response => {
            modules.addresses.cameras.meta = response.cameras;

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.cameras"),
                    button: {
                        caption: i18n("addresses.addCamera"),
                        click: modules.addresses.cameras.addCamera,
                    },
                    filter: true,
                },
                edit: modules.addresses.cameras.modifyCamera,
                columns: [
                    {
                        title: i18n("addresses.cameraIdList"),
                    },
                    {
                        title: i18n("addresses.url"),
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
                        if (params && params.filter && params.filter != modules.addresses.cameras.meta.cameras[i].cameraId) continue;
                        
                        rows.push({
                            uid: modules.addresses.cameras.meta.cameras[i].cameraId,
                            cols: [
                                {
                                    data: modules.addresses.cameras.meta.cameras[i].cameraId,
                                },
                                {
                                    data: modules.addresses.cameras.meta.cameras[i].url,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.cameras.meta.models[modules.addresses.cameras.meta.cameras[i].model].title,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.cameras.meta.cameras[i].name,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.cameras.meta.cameras[i].comments,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();