({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscriberDevices", this);
    },

    platforms : [ "Android", "iOS", "Web" ],
    tokenTypes : [ "FCM", "FCM", "FCM", "FCM", "HMS", "RUS" ],

    doModifyDevice: function (device) {
        loadingStart();
        PUT("subscribers", "device", device.uid, device).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.deviceWasChanged"));
        }).
        always(() => {
            modules.addresses.subscriberDevices.route(hashParse("params"));
        });
    },

    doDeleteDevice: function (deviceId) {
        loadingStart();
        DELETE("subscribers", "device", deviceId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.deviceWasDeleted"));
        }).
        always(() => {
            modules.addresses.subscriberDevices.route(hashParse("params"));
        });
    },

    modifyDevice: function (deviceId, subscriber) {
        let device = subscriber.devices.find( i => i.deviceId == deviceId);

        QUERY("subscribers", "subscribers", {
            by: "subscriberId",
            query: device.subscriberId,
        }).done(r => {
            let voipFlats = [];
            let paranoidFlats = [];

            if (r && r.subscribers) {
                for (let i in r.subscribers[0].flats) {
                    let link = '';

                    if (r.subscribers[0].flats[i].house.streetId) {
                        link = `<a href='#addresses.subscribers&streetId=${r.subscribers[0].flats[i].house.streetId}&flatId=${r.subscribers[0].flats[i].flatId}&houseId=${r.subscribers[0].flats[i].house.houseId}&flat=${r.subscribers[0].flats[i].flat}'><i class='fas fa-fw fa-xs fa-link'></i></a>`;
                    }

                    if (r.subscribers[0].flats[i].house.settlementId) {
                        link = `<a href='#addresses.subscribers&settlementId=${r.subscribers[0].flats[i].house.settlementId}&flatId=${r.subscribers[0].flats[i].flatId}&houseId=${r.subscribers[0].flats[i].house.houseId}&flat=${r.subscribers[0].flats[i].flat}'><i class='fas fa-fw fa-xs fa-link'></i></a>`;
                    }

                    let voip = false;
                    let paranoid = false;

                    for (let j in device.flats) {
                        if (device.flats[j].flatId == r.subscribers[0].flats[i].flatId) {
                            voip = device.flats[j].voipEnabled;
                            paranoid = device.flats[j].paranoid;
                        }
                    }

                    voipFlats.push({
                        "id": r.subscribers[0].flats[i].flatId,
                        "text": trimStr($.trim(r.subscribers[0].flats[i].house.houseFull + ", " + r.subscribers[0].flats[i].flat), 64) + " " + link,
                        "checked": !!voip,
                    });

                    paranoidFlats.push({
                        "id": r.subscribers[0].flats[i].flatId,
                        "text": trimStr($.trim(r.subscribers[0].flats[i].house.houseFull + ", " + r.subscribers[0].flats[i].flat), 64) + " " + link,
                        "checked": !!paranoid,
                    });
                }
            }

            cardForm({
                title: i18n("addresses.device"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("addresses.deleteDevice"),
                size: "lg",
                fields: [
                    {
                        id: "uid",
                        type: "text",
                        title: "id",
                        readonly: true,
                        value: device.deviceId,
                    },
                    {
                        id: "authToken",
                        type: "text",
                        title: i18n("addresses.authToken"),
                        value: device.authToken,
                        readonly: true,
                    },
                    {
                        id: "pushToken",
                        type: "text",
                        title: i18n("addresses.pushToken"),
                        value: device.pushToken,
                        readonly: true,
                    },
                    {
                        id: "voipToken",
                        type: "text",
                        title: i18n("addresses.voipToken"),
                        value: device.voipToken,
                        readonly: true,
                    },
                    {
                        id: "bundle",
                        type: "text",
                        title: i18n("addresses.bundle"),
                        value: device.bundle,
                        readonly: true,
                    },
                    {
                        id: "platform",
                        type: "text",
                        title: i18n("addresses.platform"),
                        value: this.platforms[device.platform],
                        readonly: true,
                    },
                    {
                        id: "voipEnabled",
                        type: "select",
                        title: i18n("addresses.voipEnabledGlobal"),
                        value: device.voipEnabled,
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
                        id: "voipFlats",
                        type: "multiselect",
                        title: i18n("addresses.voipEnabledFlats"),
                        options: voipFlats,
                        hidden: voipFlats.length == 0,
                    },
                    {
                        id: "paranoidFlats",
                        type: "multiselect",
                        title: i18n("addresses.paranoidFlats"),
                        options: paranoidFlats,
                        hidden: paranoidFlats.length == 0,
                    },
                    {
                        id: "pushDisable",
                        type: "select",
                        title: i18n("addresses.pushDisable"),
                        value: device.pushDisable,
                        options: [
                            {
                                id: "1",
                                text: i18n("no"),
                            },
                            {
                                id: "0",
                                text: i18n("yes"),
                            },
                        ],
                    },
                    {
                        id: "moneyDisable",
                        type: "select",
                        title: i18n("addresses.moneyDisable"),
                        value: device.moneyDisable,
                        options: [
                            {
                                id: "1",
                                text: i18n("no"),
                            },
                            {
                                id: "0",
                                text: i18n("yes"),
                            },
                        ],
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.subscriberDevices.doDeleteDevice(result.uid);
                    } else {
                        if (voipFlats.length) {
                            f = [];
                            for (let i in voipFlats) {
                                f.push({
                                    flatId: voipFlats[i].id,
                                    voipEnabled: (result.voipFlats.indexOf(voipFlats[i].id) >= 0 || result.voipFlats.indexOf(voipFlats[i].id.toString()) >= 0) ? 1 : 0,
                                    paranoid: (result.paranoidFlats.indexOf(paranoidFlats[i].id) >= 0 || result.paranoidFlats.indexOf(paranoidFlats[i].id.toString()) >= 0) ? 1 : 0,
                                });
                            }
                        }
                        modules.addresses.subscriberDevices.doModifyDevice({
                            uid: result.uid,
                            voipEnabled: result.voipEnabled,
                            pushDisable: result.pushDisable,
                            moneyDisable: result.moneyDisable,
                            flats: f,
                        });
                    }
                }
            });
        });
    },

    renderSubscriberDevices: function (subscriberId) {
        loadingStart();
        QUERY("subscribers", "devices", {
            by: "subscriber",
            query: subscriberId,
        }).
        fail(FAILPAGE).
        done(response => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.devices"),
                    filter: true,
                    pagerItemsCount: 250,
                },
                edit: deviceId => {
                    modules.addresses.subscriberDevices.modifyDevice(deviceId, response);
                },
                columns: [
                    {
                        title: i18n("addresses.deviceToken"),
                    },
                    {
                        title: i18n("addresses.platform"),
                    },
                    {
                        title: i18n("addresses.bundle"),
                    },
                    {
                        title: i18n("addresses.version"),
                    },
                    {
                        title: i18n("addresses.device"),
                    },
                    {
                        title: i18n("addresses.tokenType"),
                    },
                    {
                        title: i18n("addresses.VoIP"),
                    },
                    {
                        title: i18n("addresses.voipEnabledTable"),
                    },
                    {
                        title: i18n("addresses.registered"),
                    },
                    {
                        title: i18n("addresses.lastSeen"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];
                    const devices = response.devices;

                    for (let i in devices) {

                        rows.push({
                            uid: devices[i].deviceId,
                            cols: [
                                {
                                    data: devices[i].deviceToken,
                                    nowrap: true,
                                },
                                {
                                    data: this.platforms[devices[i].platform],
                                    nowrap: true,
                                },
                                {
                                    data: devices[i].bundle,
                                    nowrap: true,
                                },
                                {
                                    data: devices[i].version ? devices[i].version : i18n("addresses.unknown"),
                                    nowrap: true,
                                },
                                {
                                    data:devices[i].ua ?? i18n("addresses.unknown"),
                                    nowrap: true,
                                },
                                {
                                    data: this.tokenTypes[devices[i].tokenType],
                                    nowrap: true,
                                },
                                {
                                    data: (devices[i].voipToken && devices[i].voipToken.toString().length > 12) ? i18n("yes") : i18n("no"),
                                    nowrap: true,
                                },
                                {
                                    data: parseInt(devices[i].voipEnabled) ? i18n("yes") : i18n("no"),
                                    nowrap: true,
                                },
                                {
                                    data: ttDate(devices[i].registered, false),
                                    nowrap: true,
                                },
                                {
                                    data: ttDate(devices[i].lastSeen, false),
                                    nowrap: true,
                                }
                            ],
                        });
                    }

                    return rows;
                }

            }).show();
        }).
        always(loadingDone);
    },

    route: function (params) {
        $("#altForm").hide();

        modules.addresses.topMenu();

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
                        subTop(modules.addresses.path((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId : params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + '<i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + `<a href='?#addresses.subscribers&flatId=${params.flatId}&houseId=${params.houseId}&flat=${params.flat}&settlementId=${params.settlementId}&streetId=${params.streetId}'>` +  params.flat + '</a><i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + params.phone);
                        break;
                    }
                }

                modules.addresses.subscriberDevices.renderSubscriberDevices(params.subscriberId);
            });
        } else {
            subTop(params.phone);

            modules.addresses.subscriberDevices.renderSubscriberDevices(params.subscriberId);
        }
    }
}).init();
