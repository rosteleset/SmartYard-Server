({
    init: function () {
        leftSide("fas fa-fw fa-video", i18n("cameras.cameras"), "#cameras");
        moduleLoaded("cameras", this);
    },

    meta: false,

    doAddCamera: function (camera) {
        loadingStart();
        POST("cameras", "camera", false, camera).
        fail(FAIL).
        done(() => {
            message(i18n("cameras.cameraWasAdded"))
        }).
        always(modules.cameras.route);
    },

    doModifyCamera: function (camera) {
        loadingStart();
        PUT("cameras", "camera", camera.cameraId, camera).
        fail(FAIL).
        done(() => {
            message(i18n("cameras.cameraWasChanged"))
        }).
        always(modules.cameras.route);
    },

    doDeleteCamera: function (cameraId) {
        loadingStart();
        DELETE("cameras", "camera", cameraId).
        fail(FAIL).
        done(() => {
            message(i18n("cameras.cameraWasDeleted"))
        }).
        always(modules.cameras.route);
    },

    addCamera: function () {
        let models = [];
        let first;

        for (let id in modules.cameras.meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules.cameras.meta.models[id].title,
            })
        }

        cardForm({
            title: i18n("cameras.addCamera"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "enabled",
                    type: "yesno",
                    title: i18n("cameras.enabled"),
                    value: "1",
                },
                {
                    id: "model",
                    type: "select2",
                    title: i18n("cameras.model"),
                    placeholder: i18n("cameras.model"),
                    options: models,
                },
                {
                    id: "ip",
                    type: "text",
                    title: i18n("cameras.ip"),
                    placeholder: "IP",
                    validate: v => {
                        return !!ip2long(v);
                    },
                },
                {
                    id: "port",
                    type: "text",
                    title: i18n("cameras.port"),
                    placeholder: "80",
                    value: "80",
                    validate: v => {
                        return !!parseInt(v);
                    },
                },
                {
                    id: "stream",
                    type: "text",
                    title: i18n("cameras.stream"),
                    placeholder: "rtsp://",
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("cameras.credentials"),
                    placeholder: i18n("cameras.credentials"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "comment",
                    type: "text",
                    title: i18n("cameras.comment"),
                    placeholder: i18n("cameras.comment"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                },
            ],
            callback: modules.cameras.doAddCamera,
        });
    },

    modifyCamera: function (cameraId) {
        let models = [];
        let first;

        for (let id in modules.cameras.meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules.cameras.meta.models[id].title,
            })
        }

        let camera = false;

        for (let i in modules.cameras.meta.cameras) {
            if (modules.cameras.meta.cameras[i].cameraId == cameraId) {
                camera = modules.cameras.meta.cameras[i];
                break;
            }
        }

        if (camera) {
            cardForm({
                title: i18n("cameras.modifyCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("cameras.deleteCamera"),
                fields: [
                    {
                        id: "cameraId",
                        type: "text",
                        title: i18n("cameras.cameraId"),
                        value: cameraId,
                        readonly: true,
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("cameras.enabled"),
                        value: camera.enabled,
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("cameras.model"),
                        placeholder: i18n("cameras.model"),
                        options: models,
                        value: camera.model,
                    },
                    {
                        id: "ip",
                        type: "text",
                        title: i18n("cameras.ip"),
                        placeholder: "IP",
                        value: camera.ip,
                        validate: v => {
                            return !!ip2long(v);
                        },
                    },
                    {
                        id: "port",
                        type: "text",
                        title: i18n("cameras.port"),
                        placeholder: "80",
                        value: camera.port,
                        validate: v => {
                            return !!parseInt(v);
                        },
                    },
                    {
                        id: "stream",
                        type: "text",
                        title: i18n("cameras.stream"),
                        placeholder: "rtsp://",
                        value: camera.stream,
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("cameras.credentials"),
                        placeholder: i18n("cameras.credentials"),
                        value: camera.credentials,
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "comment",
                        type: "text",
                        title: i18n("cameras.comment"),
                        placeholder: i18n("cameras.comment"),
                        value: camera.comment,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                    },
                ],
                callback: result => {
                    if (result.delete === "yes") {
                        modules.cameras.deleteCamera(cameraId);
                    } else {
                        modules.cameras.doModifyCamera(result);
                    }
                },
            });
        } else {
            error(i18n("cameras.cameraNotFound"));
        }
    },

    deleteCamera: function (cameraId) {
        mConfirm(i18n("cameras.confirmDeleteCamera", cameraId), i18n("confirm"), `danger:${i18n("cameras.deleteCamera")}`, () => {
            modules.cameras.doDeleteCamera(cameraId);
        });
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("cameras.cameras");

        GET("cameras", "cameras", false, true).
        done(response => {
            modules.cameras.meta = response.cameras;

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("cameras.cameras"),
                    button: {
                        caption: i18n("cameras.addCamera"),
                        click: modules.cameras.addCamera,
                    },
                    filter: true,
                },
                edit: modules.cameras.modifyCamera,
                columns: [
                    {
                        title: i18n("cameras.cameraId"),
                    },
                    {
                        title: i18n("cameras.ip"),
                    },
                    {
                        title: i18n("cameras.comment"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.cameras.meta.cameras) {
                        rows.push({
                            uid: modules.cameras.meta.cameras[i].cameraId,
                            cols: [
                                {
                                    data: modules.cameras.meta.cameras[i].cameraId,
                                },
                                {
                                    data: modules.cameras.meta.cameras[i].ip,
                                    nowrap: true,
                                },
                                {
                                    data: modules.cameras.meta.cameras[i].comment,
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
