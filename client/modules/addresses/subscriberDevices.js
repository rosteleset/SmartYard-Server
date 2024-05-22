({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscriberDevices", this);
    },

    platforms : ["android", "ios", "web"],

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

    rendersubscriberDevices: function (subscriberId) {
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
                                },
                                {
                                    data: this.platforms[devices[i].platform],
                                },
                                {
                                    data: new Date(devices[i].registered * 1000).toLocaleString(),
                                },
                                {
                                    data: new Date(devices[i].lastSeen * 1000).toLocaleString(),
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
        modules.addresses.topMenu();

        modules.addresses.subscriberDevices.rendersubscriberDevices(params.subscriberId);

        $("#altForm").hide();
    }
}).init();
