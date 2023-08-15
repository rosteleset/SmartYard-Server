({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscribers", this);
    },

    doAddSubscriber: function (subscriber) {
        subscriber.message = {
            title: i18n("addresses.addFlatTtitle"),
            msg: i18n("addresses.addFlatMsg"),
        }
        loadingStart();
        POST("subscribers", "subscriber", false, subscriber).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.subscriberWasAdded"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doAddKey: function (key) {
        loadingStart();
        POST("subscribers", "key", false, key).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.keyWasAdded"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doAddCamera: function (camera) {
        loadingStart();
        POST("subscribers", "flatCameras", false, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasAdded"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doModifySubscriber: function (subscriber) {
        loadingStart();
        PUT("subscribers", "subscriber", subscriber.subscriberId, subscriber).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.subscriberWasChanged"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doModifyKey: function (key) {
        loadingStart();
        PUT("subscribers", "key", key.keyId, key).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.keyWasChanged"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doDeleteSubscriber: function (flatId, subscriberId) {
        loadingStart();
        DELETE("subscribers", "subscriber", flatId, { subscriberId: subscriberId }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.subscriberWasDeleted"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doCompleteDeleteSubscriber: function (subscriberId) {
        loadingStart();
        DELETE("subscribers", "subscriber", subscriberId, { complete: true }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.subscriberWasDeleted"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doDeleteKey: function (keyId) {
        loadingStart();
        DELETE("subscribers", "key", keyId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.keyWasDeleted"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    doDeleteCamera: function (cameraId, flatId) {
        loadingStart();
        DELETE("subscribers", "flatCameras", false, { from: "flat", cameraId, flatId }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasDeleted"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse()[1]);
        });
    },

    addSubscriber: function () {
        cardForm({
            title: i18n("addresses.addSubscriber"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "mobile",
                    type: "text",
                    title: i18n("addresses.mobile"),
                    placeholder: config.phonePattern?config.phonePattern:i18n("addresses.mobile"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "subscriberName",
                    type: "text",
                    title: i18n("addresses.subscriberName"),
                    placeholder: i18n("addresses.subscriberName"),
                },
                {
                    id: "subscriberPatronymic",
                    type: "text",
                    title: i18n("addresses.subscriberPatronymic"),
                    placeholder: i18n("addresses.subscriberPatronymic"),
                },
            ],
            callback: function (result) {
                let params = hashParse()[1];

                if (params.flatId) {
                    result.flatId = params.flatId;
                }

                modules.addresses.subscribers.doAddSubscriber(result);
            },
        }).show();
    },

    addKey: function () {
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
                    placeholder: i18n("addresses.key"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                },
            ],
            callback: function (result) {
                let params = hashParse()[1];

                if (params.flatId) {
                    result.accessType = 2;
                    result.accessTo = params.flatId;
                }

                modules.addresses.subscribers.doAddKey(result);
            },
        }).show();
    },

    modifySubscriber: function (subscriberId, list, flatId) {
        let subscriber = false;

        for (let i in list) {
            if (list[i].subscriberId == subscriberId) {
                subscriber = list[i];
                break;
            }
        }

        if (subscriber) {

            let flats = [];

            for (let i in subscriber.flats) {
                let owner;

                try {
                    owner = subscriber.flats[i].role.toString() !== "1";
                } catch (e) {
                    owner = true;
                }

                let link = `<a href='#addresses.subscribers&flatId=${subscriber.flats[i].flatId}&houseId=${subscriber.flats[i].house.houseId}&flat=${subscriber.flats[i].flat}&house=${encodeURIComponent(subscriber.flats[i].house.houseFull)}'><i class='fas fa-fw fa-xs fa-link'></i></a>`;
                let role = `
                    <div class="custom-control custom-checkbox mb-0">
                        <input type="checkbox" class="custom-control-input" id="subscriber-role-flat-${subscriber.flats[i].flatId}"${owner?" checked":""}>
                        <label class="custom-control-label form-check-label" for="subscriber-role-flat-${subscriber.flats[i].flatId}">${i18n("addresses.subscriberFlatOwner")}</label>
                    </div>
                `;
                flats.push({
                    "id": subscriber.flats[i].flatId,
                    "text": subscriber.flats[i].house.houseFull + ", " + subscriber.flats[i].flat + " " + link,
                    "checked": true,
                    "append": role,
                });
            }

            cardForm({
                title: i18n("addresses.editSubscriber"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                size: "lg",
                fields: [
                    {
                        id: "subscriberId",
                        type: "text",
                        title: i18n("addresses.subscriberId"),
                        readonly: true,
                        value: subscriber.subscriberId,
                    },
                    {
                        id: "mobile",
                        type: "text",
                        title: i18n("addresses.mobile"),
                        placeholder: config.phonePattern?config.phonePattern:i18n("addresses.mobile"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: subscriber.mobile,
                    },
                    {
                        id: "subscriberName",
                        type: "text",
                        title: i18n("addresses.subscriberName"),
                        placeholder: i18n("addresses.subscriberName"),
                        value: subscriber.subscriberName,
                    },
                    {
                        id: "subscriberPatronymic",
                        type: "text",
                        title: i18n("addresses.subscriberPatronymic"),
                        placeholder: i18n("addresses.subscriberPatronymic"),
                        value: subscriber.subscriberPatronymic,
                    },
                    {
                        id: "flats",
                        type: "multiselect",
                        title: i18n("addresses.subscriberFlats"),
                        options: flats,
                    },
                    {
                        id: "authToken",
                        type: "text",
                        title: i18n("addresses.authToken"),
                        value: subscriber.authToken,
                        readonly: true,
                    },
                    {
                        id: "pushToken",
                        type: "text",
                        title: i18n("addresses.pushToken"),
                        value: subscriber.pushToken,
                        readonly: true,
                    },
                    {
                        id: "voipEnabled",
                        type: "select",
                        title: i18n("addresses.voipEnabled"),
                        placeholder: i18n("addresses.voipEnabled"),
                        value: subscriber.voipEnabled,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            },
                        ],
                    },
                    {
                        id: "delete",
                        type: "select",
                        title: i18n("addresses.deleteSubscriber"),
                        options: [
                            {
                                id: "0",
                                text: "&nbsp;",
                            },
                            {
                                id: "1",
                                text: i18n("addresses.deleteSubscriberFromFlat"),
                            },
                            {
                                id: "2",
                                text: i18n("addresses.completeDeleteSubscriber"),
                            }
                        ]
                    }
                ],
                callback: function (result) {
                    if (parseInt(result.delete) == 2) {
                        modules.addresses.subscribers.completeDeleteSubscriber(subscriberId);
                    } else
                    if (parseInt(result.delete) == 1) {
                        modules.addresses.subscribers.deleteSubscriber(flatId, subscriberId);
                    } else {
                        let params = hashParse()[1];

                        if (params.flatId) {
                            result.flatId = params.flatId;
                        }

                        let f = {};

                        for (let i in result.flats) {
                            f[result.flats[i]] = $("#subscriber-role-flat-" + result.flats[i]).prop("checked");
                        }

                        result.flats = f;

                        result.forceNames = true;

                        modules.addresses.subscribers.doModifySubscriber(result);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.subscriberNotFound"));
        }
    },

    modifyKey: function (keyId, list) {
        let key = false;

        for (let i in list) {
            if (list[i].keyId == keyId) {
                key = list[i];
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
                delete: i18n("addresses.deleteKey"),
                fields: [
                    {
                        id: "keyId",
                        type: "text",
                        title: i18n("addresses.keyId"),
                        readonly: true,
                        value: key.keyId,
                    },
                    {
                        id: "comments",
                        type: "text",
                        title: i18n("addresses.comments"),
                        placeholder: i18n("addresses.comments"),
                        value: key.comments,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.subscribers.deleteKey(keyId);
                    } else {
                        modules.addresses.subscribers.doModifyKey(result);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.keyNotFound"));
        }
    },

    deleteSubscriber: function (flatId, subscriberId) {
        mConfirm(i18n("addresses.confirmDeleteSubscriber", subscriberId.toString(), flatId.toString()), i18n("confirm"), `danger:${i18n("addresses.deleteSubscriber")}`, () => {
            modules.addresses.subscribers.doDeleteSubscriber(flatId, subscriberId);
        });
    },

    completeDeleteSubscriber: function (subscriberId) {
        mConfirm(i18n("addresses.confirmCompleteDeleteSubscriber", subscriberId.toString()), i18n("confirm"), `danger:${i18n("addresses.deleteSubscriber")}`, () => {
            modules.addresses.subscribers.doCompleteDeleteSubscriber(subscriberId);
        });
    },

    deleteKey: function (keyId) {
        mConfirm(i18n("addresses.confirmDeleteKey", keyId.toString()), i18n("confirm"), `danger:${i18n("addresses.deleteKey")}`, () => {
            modules.addresses.subscribers.doDeleteKey(keyId);
        });
    },

    renderSubscribers: function (list, flatId) {
        loadingStart();

        let params = hashParse()[1];

        cardTable({
            target: "#mainForm",
            title: {
                caption: i18n("addresses.subscribers"),
                button: {
                    caption: i18n("addresses.addSubscriber"),
                    click: modules.addresses.subscribers.addSubscriber,
                },
            },
            edit: subscriberId => {
                modules.addresses.subscribers.modifySubscriber(subscriberId, list, flatId);
            },
            columns: [
                {
                    title: i18n("addresses.subscriberId"),
                },
                {
                    title: i18n("addresses.mobile"),
                    nowrap: true,
                    fullWidth: true,
                },
                {
                    title: i18n("addresses.subscriberFlatOwner"),
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in list) {
                    let owner;

                    for (let j in list[i].flats) {
                        if (list[i].flats[j].flatId == params.flatId) {
                            try {
                                owner = list[i].flats[j].role.toString() !== "1";
                            } catch (e) {
                                owner = true;
                            }
                        }
                    }

                    rows.push({
                        uid: list[i].subscriberId,
                        cols: [
                            {
                                data: list[i].subscriberId,
                            },
                            {
                                data: list[i].mobile,
                            },
                            {
                                data: owner?i18n("yes"):i18n("no"),
                            },
                        ],
                        dropDown: {
                            items: [
                                {
                                    icon: "far fa-envelope",
                                    title: i18n("addresses.subscriberInbox"),
                                    click: subscriberId => {
                                        location.href = "?#addresses.subscriberInbox&subscriberId=" + subscriberId;
                                    },
                                },
                            ]
                        },
                    });
                }

                return rows;
            },
        }).show();
    },

    renderKeys: function (list) {
        let params = hashParse()[1];

        cardTable({
            target: "#altForm",
            title: {
                caption: i18n("addresses.keys"),
                button: params.flatId?{
                    caption: i18n("addresses.addSubscribers"),
                    click: modules.addresses.subscribers.addKey,
                }:false,
            },
            edit: keyId => {
                modules.addresses.subscribers.modifyKey(keyId, list);
            },
            columns: [
                {
                    title: i18n("addresses.keyId"),
                },
                {
                    title: i18n("addresses.rfId"),
                    nowrap: true,
                },
                {
                    title: i18n("addresses.comments"),
                    nowrap: true,
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in list) {
                    rows.push({
                        uid: list[i].keyId,
                        cols: [
                            {
                                data: list[i].keyId,
                            },
                            {
                                data: list[i].rfId,
                            },
                            {
                                data: list[i].comments,
                            },
                        ],
                    });
                }

                return rows;
            },
        }).show();

        loadingDone();
    },

    addCamera: function () {
        GET("cameras", "cameras", false, true).
        done(response => {
            modules.addresses.cameras.meta = response.cameras;
            let cameras = [];

            cameras.push({
                id: "0",
                text: i18n("no"),
            })

            for (let i in response.cameras.cameras) {
                let url;
                try {
                    url = new URL(response.cameras.cameras[i].url);
                } catch (e) {
                    url = {
                        host: response.cameras.cameras[i].url,
                    }
                }
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text:  url.host,
                })
            }

            cardForm({
                title: i18n("addresses.addCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                size: "lg",
                fields: [
                    {
                        id: "cameraId",
                        type: "select2",
                        title: i18n("addresses.cameraId"),
                        options: cameras,
                    },
                ],
                callback: result => {
                    let params = hashParse()[1];
                    result.flatId = params.flatId;
                    modules.addresses.subscribers.doAddCamera(result);
                },
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    renderCameras: function (list) {
        let params = hashParse()[1];

        cardTable({
            target: "#altForm",
            mode: "append",
            title: {
                caption: i18n("addresses.cameras"),
                button: {
                    caption: i18n("addresses.addCamera"),
                    click: () => {
                        modules.addresses.subscribers.addCamera(params.flatId);
                    },
                },
            },
            columns: [
                {
                    title: i18n("addresses.cameraIdList"),
                },
                {
                    title: i18n("addresses.url"),
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

                for (let i in list) {
                    rows.push({
                        uid: list[i].cameraId,
                        cols: [
                            {
                                data: list[i].cameraId?list[i].cameraId:i18n("addresses.deleted"),
                                click: list[i].cameraId?("#addresses.cameras&filter=" + list[i].cameraId):false,
                            },
                            {
                                data: list[i].url?list[i].url:"",
                            },
                            {
                                data: list[i].name?list[i].name:"",
                                nowrap: true,
                            },
                            {
                                data: list[i].comment?list[i].comment:"",
                                nowrap: true,
                            },
                        ],
                        dropDown: {
                            items: [
                                {
                                    icon: "fas fa-trash-alt",
                                    title: i18n("addresses.deleteCamera"),
                                    class: "text-danger",
                                    disabled: !list[i].cameraId,
                                    click: cameraId => {
                                        mConfirm(i18n("addresses.confirmDeleteCamera", cameraId), i18n("confirm"), `danger:${i18n("addresses.deleteCamera")}`, () => {
                                            modules.addresses.subscribers.doDeleteCamera(cameraId, params.flatId);
                                        });
                                    },
                                },
                            ],
                        },
                    });
                }

                return rows;
            },
        }).show();
    },

    route: function (params) {
        modules.addresses.topMenu();

        if (params.flat) {
            loadingStart();

            QUERY("addresses", "addresses", {
                houseId: params.houseId,
            }).
            done(modules.addresses.addresses).
            fail(FAIL).
            done(a => {
                for (let i in a.addresses.houses) {
                    if (a.addresses.houses[i].houseId == params.houseId) {
                        document.title = i18n("windowTitle") + " :: " + a.addresses.houses[i].houseFull + ", " + params.flat;
                        subTop(modules.addresses.path((parseInt(params.settlementId)?"settlement":"street"), parseInt(params.settlementId)?params.settlementId:params.streetId) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + ", " + params.flat);
                    }
                }

                QUERY("subscribers", "subscribers", {
                    by: "flatId",
                    query: params.flatId,
                }).done(response => {
                    modules.addresses.subscribers.renderSubscribers(response.flat.subscribers, params.flatId);
                    modules.addresses.subscribers.renderKeys(response.flat.keys);
                    modules.addresses.subscribers.renderCameras(response.flat.cameras);
                }).
                fail(FAIL).
                fail(() => {
                    pageError();
                }).
                always(loadingDone);
            });
        }
    }
}).init();
