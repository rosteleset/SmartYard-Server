({
    init: function () {
        moduleLoaded("houses", this);
    },

    addFlat: function (houseId) {

    },

    modifyFlat: function (flatId) {

    },

    addEntrance: function (houseId) {
        mYesNo(i18n("houses.useExistingEntranceQuestion"), "houses.addEntrance", () => {
            console.log("yes");
        }, () => {
            console.log("no");
        }, i18n("houses.useExistingEntrance"), i18n("houses.addNewEntrance"));
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
            render(response.house);
        });
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("houses.houses");

        if (AVAIL("addresses", "house", "GET")) {
            GET("addresses", "house", params.houseId).
            fail(FAIL).
            done(result => {
                modules["houses"].house(params.houseId, result.house);
            }).
            fail(() => {
                modules["houses"].house(params.houseId);
            });
        } else {
            modules["houses"].house(params.houseId);
        }

        loadingDone();
    },
}).init();