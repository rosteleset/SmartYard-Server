({
    init: function () {
        moduleLoaded("houses", this);
    },

    addFlat: function (houseId) {

    },

    modifyFlat: function (flatId) {

    },

    doCreateEntrance: function (houseId, entranceType, entrance, multidest, lat, lon) {
        loadingStart();
        PUT("houses", "house", false, {
            action: "createEntrance",
            houseId,
            entranceType,
            entrance,
            multidest,
            lat,
            lon
        }).
        fail(FAIL).
        done(() => {
            message(i18n("houses.entranceWasCreated"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
        });
    },

    addEntrance: function (houseId) {
        mYesNo(i18n("houses.useExistingEntranceQuestion"), i18n("houses.addEntrance"), () => {
            cardForm({
                title: i18n("houses.addEntrance"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                fields: [
                    {
                        id: "entranceType",
                        type: "select",
                        title: i18n("houses.entranceType"),
                        options: [
                            // "entrance","wicket","gate","barrier"
                            {
                                id: "entrance",
                                text: i18n("houses.entranceTypeEntrance"),
                            },
                            {
                                id: "wicket",
                                text: i18n("houses.entranceTypeWicket"),
                            },
                            {
                                id: "gate",
                                text: i18n("houses.entranceTypeGate"),
                            },
                            {
                                id: "barrier",
                                text: i18n("houses.entranceTypeBarrier"),
                            }
                        ]
                    },
                    {
                        id: "entrance",
                        type: "text",
                        title: i18n("houses.entrance"),
                        placeholder: i18n("houses.entrance"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "multidest",
                        type: "select",
                        title: i18n("houses.multidest"),
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            }
                        ]
                    },
                    {
                        id: "lat",
                        type: "text",
                        title: i18n("houses.lat"),
                        placeholder: i18n("houses.lat"),
                    },
                    {
                        id: "lon",
                        type: "text",
                        title: i18n("houses.lon"),
                        placeholder: i18n("houses.lon"),
                    },
                ],
                callback: result => {
                    modules["houses"].doCreateEntrance(houseId, result.entranceType, result.entrance, result.multidest, result.lat, result.lon);
                },
            });
        }, () => {
            console.log("no");
        }, i18n("houses.addNewEntrance"), i18n("houses.useExistingEntrance"));
    },

    modifyEntrance: function (entranceId) {

    },

    house: function (houseId, address_house) {

        function render(house) {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("houses.flats"),
                    button: {
                        caption: i18n("houses.addFlat"),
                        click: () => {
                            modules["houses"].addFlat(houseId);
                        },
                    },
                },
                edit: modules["houses"].modifyFlat,
                columns: [
                    {
                        title: i18n("houses.flatId"),
                    },
                    {
                        title: i18n("houses.flat"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in house.flats) {
                        rows.push({
                            uid: house.flats[i].flatId,
                            cols: [
                                {
                                    data: house.flats[i].flatId,
                                },
                                {
                                    data: house.flats[i].flat,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
            cardTable({
                target: "#altForm",
                title: {
                    caption: i18n("houses.entrances"),
                    button: {
                        caption: i18n("houses.addEntrance"),
                        click: () => {
                            modules["houses"].addEntrance(houseId);
                        },
                    },
                },
                edit: modules["houses"].modifyEntrance,
                columns: [
                    {
                        title: i18n("houses.entranceId"),
                    },
                    {
                        title: i18n("houses.entranceType"),
                    },
                    {
                        title: i18n("houses.multidest"),
                    },
                    {
                        title: i18n("houses.entrance"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in house.entrances) {
                        rows.push({
                            uid: house.entrances[i].entranceId,
                            cols: [
                                {
                                    data: house.entrances[i].entranceId,
                                },
                                {
                                    data: house.entrances[i].entranceType,
                                },
                                {
                                    data: parseInt(house.entrances[i].entranceType)?i18n("yes"):i18n("no"),
                                },
                                {
                                    data: house.entrances[i].entrance,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
        }

        if (address_house) {
            subTop(address_house.houseFull);
        } else {
            subTop("#" + houseId);
        }

        GET("houses", "house", houseId, true).
        fail(response => {
            // ?
        }).
        done(response => {
            console.log(houseId, response);
            render(response.house);
        });
    },

    renderHouse: function (houseId) {
        if (AVAIL("addresses", "house", "GET")) {
            GET("addresses", "house", houseId).
            fail(FAIL).
            fail(() => {
//                history.back();
            }).
            done(result => {
                modules["houses"].house(houseId, result.house);
            });
        } else {
            modules["houses"].house(houseId);
        }

        loadingDone();
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("houses.houses");

        modules["houses"].renderHouse(params.houseId)
    },
}).init();