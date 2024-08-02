({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscriberDevices", this);
    },

    platforms : [ "Android", "iOS", "Web" ],

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

    modifyDevice: function (deviceId,subscriber) {

        let device = subscriber.devices.find(i=>i.deviceId == deviceId);

        cardForm({
            title: i18n("addresses.device"),
            footer: true,
            borderless: true,
            // topApply: true,
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
                    id: "platform",
                    type: "text",
                    title: i18n("addresses.platform"),
                    value: this.platforms[device.platform],
                    readonly: true,
                },
                {
                    id: "voipEnabled",
                    type: "select",
                    title: i18n("addresses.voipEnabled"),
                    placeholder: i18n("addresses.voipEnabled"),
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
                }
            ],
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.addresses.subscriberDevices.doDeleteDevice(result.uid);
                } else {
                    modules.addresses.subscriberDevices.doModifyDevice({
                        uid: result.uid,
                        voipEnabled: result.voipEnabled
                    });
                }
            }
        }).show();
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
    }
}).init();
