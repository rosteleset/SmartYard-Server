({
    init: function () {
        moduleLoaded("house", this);
    },

    doAddFlat: function (houseId, floor, flat, entrances) {
        loadingStart();
        PUT("houses", "house", false, {
            action: "addFlat",
            houseId,
            floor,
            flat,
            entrances
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.flatWasAdded"));
        }).
        always(() => {
            modules["house"].renderHouse(houseId);
        });
    },

    addFlat: function (houseId) {
        let entrances = [];

        for (let i in modules["house"].meta.entrances) {
            entrances.push({
                id: modules["house"].meta.entrances[i].entranceId,
                text: i18n("house.entranceType" + modules["house"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["house"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["house"].meta.entrances[i].entrance,
            });
        }

        cardForm({
            title: i18n("house.addFlat"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "floor",
                    type: "text",
                    title: i18n("house.floor"),
                    placeholder: i18n("house.floor"),
                },
                {
                    id: "flat",
                    type: "text",
                    title: i18n("house.flat"),
                    placeholder: i18n("house.flat"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "entrances",
                    type: "multiselect",
                    title: i18n("house.entrances"),
                    hidden: entrances.length <= 0,
                    options: entrances,
                }
            ],
            callback: result => {
                modules["house"].doAddFlat(houseId, result.floor, result.flat, result.entrances);
            },
        });
    },

    modifyFlat: function (flatId) {

    },

    doCreateEntrance: function (houseId, entranceType, entrance, shared, lat, lon) {
        loadingStart();
        PUT("houses", "house", false, {
            action: "createEntrance",
            houseId,
            entranceType,
            entrance,
            shared,
            lat,
            lon
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.entranceWasCreated"));
        }).
        always(() => {
            modules["house"].renderHouse(houseId);
        });
    },

    addEntrance: function (houseId) {
        mYesNo(i18n("house.useExistingEntranceQuestion"), i18n("house.addEntrance"), () => {
            cardForm({
                title: i18n("house.addEntrance"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                fields: [
                    {
                        id: "entranceType",
                        type: "select",
                        title: i18n("house.entranceType"),
                        options: [
                            {
                                id: "entrance",
                                text: i18n("house.entranceTypeEntranceFull"),
                            },
                            {
                                id: "wicket",
                                text: i18n("house.entranceTypeWicketFull"),
                            },
                            {
                                id: "gate",
                                text: i18n("house.entranceTypeGateFull"),
                            },
                            {
                                id: "barrier",
                                text: i18n("house.entranceTypeBarrierFull"),
                            }
                        ]
                    },
                    {
                        id: "entrance",
                        type: "text",
                        title: i18n("house.entrance"),
                        placeholder: i18n("house.entrance"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "shared",
                        type: "select",
                        title: i18n("house.shared"),
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
                        title: i18n("house.lat"),
                        placeholder: i18n("house.lat"),
                    },
                    {
                        id: "lon",
                        type: "text",
                        title: i18n("house.lon"),
                        placeholder: i18n("house.lon"),
                    },
                ],
                callback: result => {
                    modules["house"].doCreateEntrance(houseId, result.entranceType, result.entrance, result.shared, result.lat, result.lon);
                },
            });
        }, () => {
            console.log("no");
        }, i18n("house.addNewEntrance"), i18n("house.useExistingEntrance"));
    },

    modifyEntrance: function (entranceId) {

    },

    house: function (houseId) {

        function render() {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("house.flats"),
                    button: {
                        caption: i18n("house.addFlat"),
                        click: () => {
                            modules["house"].addFlat(houseId);
                        },
                    },
                },
                edit: modules["house"].modifyFlat,
                columns: [
                    {
                        title: i18n("house.flatId"),
                    },
                    {
                        title: i18n("house.floor"),
                    },
                    {
                        title: i18n("house.flat"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules["house"].meta.flats) {
                        rows.push({
                            uid: modules["house"].meta.flats[i].flatId,
                            cols: [
                                {
                                    data: modules["house"].meta.flats[i].flatId,
                                },
                                {
                                    data: modules["house"].meta.flats[i].floor,
                                },
                                {
                                    data: modules["house"].meta.flats[i].flat,
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
                    caption: i18n("house.entrances"),
                    button: {
                        caption: i18n("house.addEntrance"),
                        click: () => {
                            modules["house"].addEntrance(houseId);
                        },
                    },
                },
                edit: modules["house"].modifyEntrance,
                columns: [
                    {
                        title: i18n("house.entranceId"),
                    },
                    {
                        title: i18n("house.entranceType"),
                    },
                    {
                        title: i18n("house.shared"),
                    },
                    {
                        title: i18n("house.entrance"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules["house"].meta.entrances) {
                        rows.push({
                            uid: modules["house"].meta.entrances[i].entranceId,
                            cols: [
                                {
                                    data: modules["house"].meta.entrances[i].entranceId,
                                },
                                {
                                    data: i18n("house.entranceType" + modules["house"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["house"].meta.entrances[i].entranceType.substring(1) + "Full"),
                                },
                                {
                                    data: parseInt(modules["house"].meta.entrances[i].shared)?i18n("yes"):i18n("no"),
                                },
                                {
                                    data: modules["house"].meta.entrances[i].entrance,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
        }

        if (modules["addresses"] && modules["addresses"].meta && modules["addresses"].meta.houses) {
            for (let i in modules["addresses"].meta.houses) {
                if (modules["addresses"].meta.houses[i].houseId == houseId) {
                    if (!modules["house"].meta) {
                        modules["house"].meta = {};
                    }
                    modules["house"].meta.house = modules["addresses"].meta.houses[i];
                    subTop(modules["house"].meta.house.houseFull);
                } else {
                    subTop("#" + houseId);
                }
            }
        }

        GET("houses", "house", houseId, true).
        fail(response => {
            // ?
        }).
        done(response => {
            if (!modules["house"].meta) {
                modules["house"].meta = {};
            }
            modules["house"].meta.entrances = response["house"].entrances;
            modules["house"].meta.flats = response["house"].flats;
            console.log(modules["house"].meta);
            render();
        });
    },

    renderHouse: function (houseId) {
        if (AVAIL("addresses", "addresses", "GET")) {
            GET("addresses", "addresses").
            done(modules["addresses"].addresses).
            fail(FAIL).
            fail(() => {
//                history.back();
            }).
            done(result => {
                modules["house"].house(houseId);
            });
        } else {
            modules["house"].house(houseId);
        }

        loadingDone();
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("house.houses");

        modules["house"].renderHouse(params.houseId)
    },
}).init();