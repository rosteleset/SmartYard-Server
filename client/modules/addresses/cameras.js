({
    init: function () {
        leftSide("fas fa-fw fa-video", i18n("addresses.cameras"), "#addresses.cameras");
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
        always(modules.addresses.cameras.route);
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

        cardForm({
            title: i18n("addresses.addCamera"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "enabled",
                    type: "yesno",
                    title: i18n("addresses.enabled"),
                    value: "1",
                },
                {
                    id: "model",
                    type: "select2",
                    title: i18n("addresses.model"),
                    placeholder: i18n("addresses.model"),
                    options: models,
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
                },
                {
                    id: "stream",
                    type: "text",
                    title: i18n("addresses.stream"),
                    placeholder: "rtsp://",
                    validate: v => {
                        try {
                            new URL(v);
                            return true;
                        } catch (_) {
                            return false;
                        }
                    },
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("addresses.credentials"),
                    placeholder: i18n("addresses.credentials"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "comment",
                    type: "text",
                    title: i18n("addresses.comment"),
                    placeholder: i18n("addresses.comment"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                },
            ],
            callback: modules.addresses.cameras.doAddCamera,
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

        if (camera) {
            cardForm({
                title: i18n("addresses.modifyCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("addresses.deleteCamera"),
                fields: [
                    {
                        id: "cameraId",
                        type: "text",
                        title: i18n("addresses.cameraId"),
                        value: cameraId,
                        readonly: true,
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("addresses.enabled"),
                        value: camera.enabled,
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("addresses.model"),
                        placeholder: i18n("addresses.model"),
                        options: models,
                        value: camera.model,
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
                    },
                    {
                        id: "stream",
                        type: "text",
                        title: i18n("addresses.stream"),
                        placeholder: "rtsp://",
                        value: camera.stream,
                        validate: v => {
                            try {
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        },
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("addresses.credentials"),
                        placeholder: i18n("addresses.credentials"),
                        value: camera.credentials,
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "comment",
                        type: "text",
                        title: i18n("addresses.comment"),
                        placeholder: i18n("addresses.comment"),
                        value: camera.comment,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                    },
                ],
                callback: result => {
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
        $("#subTop").html("");
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
                        title: i18n("addresses.cameraId"),
                    },
                    {
                        title: i18n("addresses.url"),
                    },
                    {
                        title: i18n("addresses.comment"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.cameras.meta.cameras) {
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
                                    data: modules.addresses.cameras.meta.cameras[i].comment,
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
