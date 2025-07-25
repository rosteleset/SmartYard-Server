({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscribers", this);
    },

    doAddSubscriber: function (subscriber) {
        subscriber.message = {
            title: i18n("addresses.addFlatTitle"),
            msg: i18n("addresses.addFlatMsg"),
        }
        loadingStart();
        POST("subscribers", "subscriber", false, subscriber).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.subscriberWasAdded"));
        }).
        always(() => {
            modules.addresses.subscribers.route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
            pathToObject(modules, currentPage).route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
            window.location.href = refreshUrl();
//            modules.addresses.subscribers.route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
            modules.addresses.subscribers.route(hashParse("params"));
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
                    validate: v => {
                        return new RegExp("^" + config.regExp.phone + "$").test(v);
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
                {
                    id: "subscriberLast",
                    type: "text",
                    title: i18n("addresses.subscriberLast"),
                    placeholder: i18n("addresses.subscriberLast"),
                },
            ],
            callback: function (result) {
                let params = hashParse("params");

                if (params.flatId) {
                    result.flatId = params.flatId;
                }

                modules.addresses.subscribers.doAddSubscriber(result);
            },
        });
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
                },
            ],
            callback: function (result) {
                let params = hashParse("params");

                if (params.flatId) {
                    result.accessType = 2;
                    result.accessTo = params.flatId;
                }

                modules.addresses.subscribers.doAddKey(result);
            },
        });
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

                let link = '';

                if (subscriber.flats[i].house.streetId) {
                    link = `<a href='#addresses.subscribers&streetId=${subscriber.flats[i].house.streetId}&flatId=${subscriber.flats[i].flatId}&houseId=${subscriber.flats[i].house.houseId}&flat=${subscriber.flats[i].flat}'><i class='fas fa-fw fa-xs fa-link'></i></a>`;
                }

                if (subscriber.flats[i].house.settlementId) {
                    link = `<a href='#addresses.subscribers&settlementId=${subscriber.flats[i].house.settlementId}&flatId=${subscriber.flats[i].flatId}&houseId=${subscriber.flats[i].house.houseId}&flat=${subscriber.flats[i].flat}'><i class='fas fa-fw fa-xs fa-link'></i></a>`;
                }

                let flat = `
                    <div class="custom-control custom-checkbox mb-0">
                        <input type="checkbox" class="custom-control-input" id="subscriber-role-flat-${subscriber.flats[i].flatId}"${owner ? " checked" : ""}>
                        <label class="custom-control-label form-check-label" for="subscriber-role-flat-${subscriber.flats[i].flatId}">${i18n("addresses.subscriberFlatOwner")}</label>
                    </div>
                `;

                flats.push({
                    "id": subscriber.flats[i].flatId,
                    "text": trimStr($.trim(subscriber.flats[i].house.houseFull + ", " + subscriber.flats[i].flat), 64) + " " + link,
                    "checked": true,
                    "append": flat,
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
                        validate: v => {
                            return new RegExp("^" + config.regExp.phone + "$").test(v);
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
                        id: "subscriberLast",
                        type: "text",
                        title: i18n("addresses.subscriberLast"),
                        placeholder: i18n("addresses.subscriberLast"),
                        value: subscriber.subscriberLast,
                    },
                    {
                        id: "flats",
                        type: "multiselect",
                        title: i18n("addresses.subscriberFlats"),
                        options: flats,
                    },
                    {
                        id: "delete",
                        type: "select",
                        title: i18n("addresses.deleteSubscriber"),
                        options: [
                            {
                                id: "",
                                text: "",
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
                        let params = hashParse("params");

                        if (params.flatId) {
                            result.flatId = params.flatId;
                        }

                        let f = {};

                        for (let i in result.flats) {
                            f[result.flats[i]] = {
                                role: $("#subscriber-role-flat-" + result.flats[i]).prop("checked"),
                            };
                        }

                        result.flats = f;

                        result.forceNames = true;

                        modules.addresses.subscribers.doModifySubscriber(result);
                    }
                },
            });
        } else {
            error(i18n("addresses.subscriberNotFound"));
        }
    },

    modifySubscriberLim: function (subscriber) {
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
                    validate: v => {
                        return new RegExp("^" + config.regExp.phone + "$").test(v);
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
                    id: "subscriberLast",
                    type: "text",
                    title: i18n("addresses.subscriberLast"),
                    placeholder: i18n("addresses.subscriberLast"),
                    value: subscriber.subscriberLast,
                },
                {
                    id: "delete",
                    type: "select",
                    title: i18n("addresses.deleteSubscriber"),
                    options: [
                        {
                            id: "",
                            text: "",
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
                    modules.addresses.subscribers.completeDeleteSubscriber(subscriber.subscriberId);
                } else {
                    result.forceNames = true;
                    modules.addresses.subscribers.doModifySubscriber(result);
                }
            },
        });
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
                        hidden: parseInt(key.accessType) != 2,
                        value: key.watch,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.subscribers.deleteKey(keyId);
                    } else {
                        modules.addresses.subscribers.doModifyKey(result);
                    }
                },
            });
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

    renderSubscribers: function (list) {
        let params = hashParse("params");

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
                modules.addresses.subscribers.modifySubscriber(subscriberId, list, params.flatId);
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
                let subscribers = {};

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

                    subscribers[list[i].subscriberId] = list[i].mobile;

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
                                        window.location.href = "?#addresses.subscriberInbox&subscriberId=" + subscriberId + "&houseId=" + params.houseId + "&flatId=" + params.flatId + "&phone=" + subscribers[subscriberId] + "&flat=" + params.flat + "&settlementId=" + params.settlementId + "&streetId=" + params.streetId;
                                    },
                                },
                                {
                                    icon: "fas fa-key",
                                    title: i18n("addresses.keys"),
                                    click: subscriberId => {
                                        window.location.href = "?#addresses.keys&query=" + subscriberId + "&by=1&houseId=" + params.houseId + "&flatId=" + params.flatId + "&phone=" + subscribers[subscriberId] + "&flat=" + params.flat + "&settlementId=" + params.settlementId + "&streetId=" + params.streetId + "&back=1";
                                    },
                                },
                                {
                                    icon: "fas fa-mobile",
                                    title: i18n("addresses.devices"),
                                    click: subscriberId => {
                                        location.href = "?#addresses.subscriberDevices&subscriberId=" + subscriberId + "&houseId=" + params.houseId + "&flatId=" + params.flatId + "&phone=" + subscribers[subscriberId] + "&flat=" + params.flat + "&settlementId=" + params.settlementId + "&streetId=" + params.streetId;
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

    renderKeys: function (list) {
        let params = hashParse("params");

        cardTable({
            target: "#altForm",
            title: {
                caption: i18n("addresses.keys"),
                button: params.flatId ? {
                    caption: i18n("addresses.addKey"),
                    click: modules.addresses.subscribers.addKey,
                } : false,
            },
            edit: keyId => {
                modules.addresses.subscribers.modifyKey(keyId, list);
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

                for (let i in list) {
                    rows.push({
                        uid: list[i].keyId,
                        cols: [
                            {
                                data: list[i].keyId,
                                nowrap: true,
                            },
                            {
                                data: list[i].rfId,
                                nowrap: true,
                            },
                            {
                                data: list[i].lastSeen ? ttDate(list[i].lastSeen) : "",
                                nowrap: true,
                            },
                            {
                                data: list[i].comments,
                            },
                            {
                                data: parseInt(list[i].watch) ? i18n("yes") : i18n("no"),
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

            for (let i in response.cameras.cameras) {
                let url;
                try {
                    url = new URL(response.cameras.cameras[i].url);
                } catch (e) {
                    url = {
                        host: response.cameras.cameras[i].url,
                    }
                }
                let comments = $.trim(response.cameras.cameras[i].comments);
                let name = $.trim(response.cameras.cameras[i].name);
                let text = "";
                if (name && comments) {
                    text = name + " (" + comments + ") [" + url.host + "]";
                } else
                if (name && !comments) {
                    text = name + " [" + url.host + "]";
                } else
                if (!name && comments) {
                    text = comments + " [" + url.host + "]";
                } else {
                    text = url.host;
                }
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text: text,
                });
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
                        validate: v => {
                            return parseInt(v) > 0;
                        },
                    },
                ],
                callback: result => {
                    let params = hashParse("params");
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
        let params = hashParse("params");

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
                                data: list[i].comments ? list[i].comments : "",
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

        if (params.flat && params.houseId && params.flatId) {
            loadingStart();

            modules.addresses.houses.loadHouse(params.houseId, () => {
                QUERY("addresses", "addresses", {
                    houseId: params.houseId,
                }).
                done(modules.addresses.addresses).
                fail(FAIL).
                done(a => {
                    for (let i in a.addresses.houses) {
                        if (a.addresses.houses[i].houseId == params.houseId) {
                            document.title = i18n("windowTitle") + " :: " + a.addresses.houses[i].houseFull + ", " + params.flat;
                            subTop(modules.addresses.path((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId:params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="#" onclick="modules.addresses.houses.modifyFlat(${params.flatId}); event.preventDefault(); return false;">` + params.flat + "</a>");
                            break;
                        }
                    }

                    QUERY("subscribers", "subscribers", {
                        by: "flatId",
                        query: params.flatId,
                    }).done(response => {
                        modules.addresses.subscribers.renderSubscribers(response.flat.subscribers);
                        modules.addresses.subscribers.renderKeys(response.flat.keys);
                        modules.addresses.subscribers.renderCameras(response.flat.cameras);
                    }).
                    fail(FAIL).
                    fail(() => {
                        pageError();
                    }).
                    always(loadingDone);
                });
            });
        } else {
            pageError();
        }
    }
}).init();
