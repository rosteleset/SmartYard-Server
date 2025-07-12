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
            cardTable({
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
                    {
                        title: i18n("addresses.watch"),
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
                                    data: result.keys[i].comments,
                                },
                                {
                                    data: parseInt(result.keys[i].watch) ? i18n("yes") : i18n("no"),
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
            }).show();

            loadingDone();
        });
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
                    id: "rfId",
                    type: "text",
                    title: i18n("addresses.key"),
                    placeholder: "00000000ABCDEF",
                    validate: v => {
                        return new RegExp("^" + config.regExp.rfid + "$").test(v);
                    }
                },
                {
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                },
                {
                    id: "watch",
                    type: "noyes",
                    title: i18n("addresses.watch"),
                    hidden: parseInt(params.by) != 1 && parseInt(params.by) != 2,
                },
            ],
            callback: function (result) {
                result.accessType = params.by ? params.by : "0";
                result.accessTo = params.query ? params.query : "0";
                loadingStart();
                POST("subscribers", "key", false, result).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    message(i18n("addresses.keyWasAdded"));
                }).
                always(() => {
                    window.location = refreshUrl();
                });
            },
        });
    },

    removeKey: function (keyId) {
        mConfirm(i18n("addresses.confirmDeleteKey", keyId), i18n("confirm"), `danger:${i18n("addresses.deleteKey")}`, () => {
            DELETE("subscribers", "key", keyId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("addresses.keyWasDeleted"));
            }).
            always(() => {
                window.location = refreshUrl();
            });
        });
    },

    modifyKey: function (keyId) {
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
                        {
                            id: "watch",
                            type: "noyes",
                            title: i18n("addresses.watch"),
                            hidden: parseInt(key.accessType) != 1,
                            value: key.watch,
                        },
                    ],
                    callback: function (result) {
                        PUT("subscribers", "key", result.keyId, {
                            comments: result.comments,
                        }).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            message(i18n("addresses.keyWasChanged"));
                        }).
                        always(() => {
                            window.location = refreshUrl();
                        });
                    },
                });
            } else {
                error(i18n("addresses.keyNotFound"));
            }
        });
    },

    route: function (params) {
        $("#altForm").hide();

        if (params.houseId) {
            QUERY("addresses", "addresses", {
                houseId: params.houseId,
            }).
            done(modules.addresses.addresses).
            fail(FAIL).
            done(a => {
                for (let i in a.addresses.houses) {
                    if (a.addresses.houses[i].houseId == params.houseId) {
                        document.title = i18n("windowTitle") + " :: " + a.addresses.houses[i].houseFull + ", " + params.flat;
                        if (modules.addresses.keys.menuItem) {
                            $("#" + modules.addresses.keys.menuItem).children().first().attr("href", "?#addresses.keys&_=" + Math.random());
                        }

                        if (params.back) {
                            let st = '';
                            if (parseInt(params.by) == 1) {
                                st = modules.addresses.path((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId : params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + '<i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + `<a href='?#addresses.subscribers&flatId=${params.flatId}&houseId=${params.houseId}&flat=${params.flat}&settlementId=${params.settlementId}&streetId=${params.streetId}'>` +  params.flat + '</a><i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + params.phone;
                            }
                            if (parseInt(params.by) == 2) {
                                st = modules.addresses.path((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId : params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + '<i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + `<a href='?#addresses.subscribers&flatId=${params.flatId}&houseId=${params.houseId}&flat=${params.flat}&settlementId=${params.settlementId}&streetId=${params.streetId}'>` +  params.flat + '</a>';
                            }
                            subTop(st);
                        } else {
                            subTop();
                        }

                        if (parseInt(params.by)) {
                            document.title = i18n("windowTitle") + " :: " + i18n("addresses.objectKeys", i18n("addresses.keysType" + parseInt(params.by)));
                        } else {
                            document.title = i18n("windowTitle") + " :: " + i18n("addresses.superKeys");
                        }

                        modules.addresses.keys.renderKeys(params);
                        break;
                    }
                }
            });
        } else {
            modules.addresses.keys.renderKeys(params);
        }
    },
}).init();