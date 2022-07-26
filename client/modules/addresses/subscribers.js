({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscribers", this);
    },

    doAddSubscriber: function (subscriber) {
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

    doDeleteSubscriber: function (subscriberId) {
        loadingStart();
        DELETE("subscribers", "subscriber", subscriberId).
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
                    placeholder: i18n("addresses.mobile"),
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

    modifySubscriber: function (subscriberId, list) {
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
                delete: i18n("addresses.deleteSubscriber"),
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
                        placeholder: i18n("addresses.mobile"),
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
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.subscribers.deleteSubscriber(subscriberId);
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

    deleteSubscriber: function (subscriberId) {
        mConfirm(i18n("addresses.confirmDeleteSubscriber", subscriberId.toString()), i18n("confirm"), `danger:${i18n("addresses.deleteSubscriber")}`, () => {
            modules.addresses.subscribers.doDeleteSubscriber(subscriberId);
        });
    },

    deleteKey: function (keyId) {
        mConfirm(i18n("addresses.confirmDeleteKey", keyId.toString()), i18n("confirm"), `danger:${i18n("addresses.deleteKey")}`, () => {
            modules.addresses.subscribers.doDeleteKey(keyId);
        });
    },

    renderSubscribers: function (list, formTarget) {
        loadingStart();

        let params = hashParse()[1];

        cardTable({
            target: formTarget,
            title: {
                caption: i18n("addresses.subscribers"),
                button: {
                    caption: i18n("addresses.addSubscriber"),
                    click: modules.addresses.subscribers.addSubscriber,
                },
            },
            edit: subscriberId => {
                modules.addresses.subscribers.modifySubscriber(subscriberId, list);
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
                    });
                }

                return rows;
            },
        }).show();

        loadingDone();
    },

    renderKeys: function (list, formTarget) {
        loadingStart();

        let params = hashParse()[1];

        cardTable({
            target: formTarget,
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

    route: function (params) {
        modules.addresses.topMenu();

        if (params.flat) {
            subTop(params.house + ", " + params.flat);

            QUERY("subscribers", "subscribers", {
                by: "flat",
                query: params.flatId,
            }).done(responseSubscribers => {
                QUERY("subscribers", "keys", {
                    by: "flat",
                    query: params.flatId,
                }).done(responseKeys => {
                    modules.addresses.subscribers.renderSubscribers(responseSubscribers.subscribers, "#mainForm");
                    modules.addresses.subscribers.renderKeys(responseKeys.keys, "#altForm");
                }).
                fail(FAIL).
                fail(() => {
                    pageError();
                }).
                fail(loadingDone);
            }).
            fail(FAIL).
            fail(() => {
                pageError();
            }).
            fail(loadingDone);
        }
    }
}).init();
