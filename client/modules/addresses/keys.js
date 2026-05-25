({
    menuItem: false,

    init: function () {
        if (AVAIL("subscribers", "keys")) {
            this.menuItem = leftSide("fab fa-fw fa-keycdn", i18n("addresses.superKeys"), "?#addresses.keys", "households");
        }
        moduleLoaded("addresses.keys", this);
    },

    renderKeys: function (params) {
        loadingStart();
        QUERY("subscribers", "keys", {
            by: params.by ? params.by : "0",
            query: params.query ? params.query : "0",
        }, true).
        fail(FAILPAGE).
        done(result => {
            let target = (params.modal ? modalTable : cardTable)({
                caption: params.caption,
                target: "#mainForm",
                title: {
                    caption: parseInt(params.by) ? i18n("addresses.objectKeys", i18n("addresses.keysType" + parseInt(params.by))) : i18n("addresses.superKeys"),
                    button: {
                        caption: i18n("addresses.addKey"),
                        click: () => {
                            modules.addresses.keys.addKey(params);
                        },
                    },
                },
                edit: keyId => {
                    modules.addresses.keys.modifyKey(keyId, params);
                },
                columns: [
                    {
                        title: i18n("addresses.keyId"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.rfId"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.lastSeen"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.comments"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in result.keys) {
                        rows.push({
                            uid: result.keys[i].keyId,
                            cols: [
                                {
                                    data: result.keys[i].keyId,
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].rfId,
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].lastSeen ? ttDate(result.keys[i].lastSeen) : "&nbsp;",
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].comments ? result.keys[i].comments : "&nbsp;",
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-trash-alt",
                                        title: i18n("addresses.deleteKey"),
                                        class: "text-danger",
                                        click: keyId => {
                                            modules.addresses.keys.removeKey(keyId, params);
                                        },
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            });

            if (target) {
                target.show();
            }

            loadingDone();
        });
    },

    parseRfIds: function (value) {
        let rfIds = [];
        let parts = (value ? value : "").split(/[\s,;]+/);

        for (let i in parts) {
            let rfId = $.trim(parts[i]);
            if (rfId && rfIds.indexOf(rfId) < 0) {
                rfIds.push(rfId);
            }
        }

        return rfIds;
    },

    validateRfIds: function (value) {
        let rfIds = modules.addresses.keys.parseRfIds(value);

        if (!rfIds.length) {
            return false;
        }

        for (let i in rfIds) {
            if (!new RegExp("^" + config.regExp.rfid + "$").test(rfIds[i])) {
                return i18n("addresses.invalidKeys", rfIds[i]);
            }
        }

        return true;
    },

    addKeyMessage: function (response) {
        if (response.keys) {
            if (response.keys.added.length) {
                message(response.keys.total > 1 ? i18n("addresses.keysWereAdded", response.keys.added.length, response.keys.total) : i18n("addresses.keyWasAdded"));
            }
            if (response.keys.failed.length) {
                let failed = response.keys.failed.map(key => key.rfId).slice(0, 5).join(", ");
                error(i18n("addresses.keysWereNotAdded", response.keys.failed.length, response.keys.total) + (failed ? ": " + failed : ""));
            }
        } else {
            message(i18n("addresses.keyWasAdded"));
        }
    },

    addKey: function (params) {
        cardForm({
            title: i18n("addresses.addKey"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "rfIds",
                    type: "area",
                    title: i18n("addresses.keys"),
                    placeholder: "000000ABCDEF12\n00123456789ABC\nA1B2C3D4E5F607",
                    validate: modules.addresses.keys.validateRfIds,
                },
                {
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                },
            ],
            callback: function (result) {
                result.rfIds = modules.addresses.keys.parseRfIds(result.rfIds);
                result.accessType = params.by ? params.by : "0";
                result.accessTo = params.query ? params.query : "0";
                loadingStart();
                POST("subscribers", "key", false, result).
                fail(FAIL).
                fail(loadingDone).
                done(response => {
                    modules.addresses.keys.addKeyMessage(response);
                }).
                always(() => {
                    if (params.modal) {
                        modules.addresses.keys.modalKeys(params);
                    } else {
                        window.location = refreshUrl();
                    }
                });
            },
        });
    },

    removeKey: function (keyId, params) {
        mConfirm(i18n("addresses.confirmDeleteKey", keyId), i18n("confirm"), `danger:${i18n("addresses.deleteKey")}`, () => {
            DELETE("subscribers", "key", keyId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("addresses.keyWasDeleted"));
            }).
            always(() => {
                if (params.modal) {
                    modules.addresses.keys.modalKeys(params);
                } else {
                    window.location = refreshUrl();
                }
            });
        });
    },

    modifyKey: function (keyId, params) {
        loadingStart();
        QUERY("subscribers", "keys", {
            by: "keyId",
            query: keyId,
        }, true).
        fail(FAILPAGE).
        done(result => {
            loadingDone();

            let key = false;

            for (let i in result.keys) {
                if (result.keys[i].keyId == keyId) {
                    key = result.keys[i];
                    break;
                }
            }

            if (key) {
                cardForm({
                    title: i18n("addresses.editKey"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("edit"),
                    fields: [
                        {
                            id: "keyId",
                            type: "text",
                            title: i18n("addresses.keyId"),
                            readonly: true,
                            value: key.keyId,
                        },
                        {
                            id: "rfId",
                            type: "text",
                            title: i18n("addresses.rfId"),
                            readonly: true,
                            value: key.rfId,
                        },
                        {
                            id: "comments",
                            type: "text",
                            title: i18n("addresses.comments"),
                            placeholder: i18n("addresses.comments"),
                            value: key.comments,
                        },
                    ],
                    callback: result => {
                        PUT("subscribers", "key", result.keyId, result).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            message(i18n("addresses.keyWasChanged"));
                        }).
                        always(() => {
                            if (params.modal) {
                                modules.addresses.keys.modalKeys(params);
                            } else {
                                window.location = refreshUrl();
                            }
                        });
                    },
                });
            } else {
                error(i18n("addresses.keyNotFound"));
            }
        });
    },

    modalKeys: function (params) {
        params.modal = true;

        modules.addresses.keys.renderKeys(params);
    },

    route: function (params) {
        $("#altForm").hide();

        if (params.backStr && params.back) {
            subTop(`<a href="?#${params.back}">${params.backStr}</a>${params.backStrPlus ? (", " + params.backStrPlus) : ""}`);
        } else {
            subTop();
        }

        modules.addresses.keys.renderKeys(params);
    },
}).init();