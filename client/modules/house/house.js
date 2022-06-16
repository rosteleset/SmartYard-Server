({
    init: function () {
        leftSide("fas fa-fw fa-home", i18n("house.house"), "#house", false, true);

        $(".sidebar .nav-item a[href='#house']").on("click", function (event) {
            event.stopPropagation();
            return false;
        });

        moduleLoaded("house", this);
    },

    doAddEntrance: function (houseId, entranceId) {
        loadingStart();
        POST("houses", "entrance", false, {
            houseId,
            entranceId,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.entranceWasAdded"));
        }).
        always(() => {
            modules["house"].renderHouse(houseId);
        });
    },

    doCreateEntrance: function (houseId, entranceType, entrance, shared, lat, lon) {
        loadingStart();
        POST("houses", "entrance", false, {
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

    doAddFlat: function (houseId, floor, flat, entrances, apartmentsAndLevels, manualBlock, openCode, autoOpen, whiteRabbit, sipEnabled, sipPassword) {
        loadingStart();
        POST("houses", "flat", false, {
            houseId,
            floor,
            flat,
            entrances,
            apartmentsAndLevels,
            manualBlock,
            openCode,
            autoOpen,
            whiteRabbit,
            sipEnabled,
            sipPassword
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.flatWasAdded"));
        }).
        always(() => {
            modules["house"].renderHouse(houseId);
        });
    },

    doModifyEntrance: function (entranceId, entranceType, entrance, shared, lat, lon, houseId) {
        loadingStart();
        PUT("houses", "entrance", entranceId, {
            entranceType,
            entrance,
            shared,
            lat,
            lon
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.entranceWasChanged"));
        }).
        always(() => {
            modules["house"].renderHouse(houseId);
        });
    },

    doModifyFlat: function (flatId, floor, flat, entrances, apartmentsAndLevels, manualBlock, openCode, autoOpen, whiteRabbit, sipEnabled, sipPassword, houseId) {
        loadingStart();
        PUT("houses", "flat", flatId, {
            floor,
            flat,
            entrances,
            apartmentsAndLevels,
            manualBlock,
            openCode,
            autoOpen,
            whiteRabbit,
            sipEnabled,
            sipPassword
        }).
        fail(FAIL).
        done(() => {
            message(i18n("house.flatWasChanged"));
        }).
        always(() => {
            if (houseId) {
                modules["house"].renderHouse(houseId);
            }
        });
    },

    doDeleteEntrance: function (entranceId, complete, houseId) {
        loadingStart();
        if (complete) {
            DELETE("houses", "entrance", entranceId).
            fail(FAIL).
            done(() => {
                message(i18n("house.entranceWasDeleted"));
            }).
            always(() => {
                modules["house"].renderHouse(houseId);
            });
        } else {
            DELETE("houses", "entrance", entranceId, {
                houseId
            }).
            fail(FAIL).
            done(() => {
                message(i18n("house.entranceWasDeleted"));
            }).
            always(() => {
                modules["house"].renderHouse(houseId);
            });
        }
    },

    doDeleteFlat: function (flatId, houseId) {
        loadingStart();
        DELETE("houses", "flat", flatId).
        fail(FAIL).
        done(() => {
            message(i18n("house.flatWasDeleted"));
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
                        id: "lon",
                        type: "text",
                        title: i18n("house.lon"),
                        placeholder: i18n("house.lon"),
                    },
                    {
                        id: "lat",
                        type: "text",
                        title: i18n("house.lat"),
                        placeholder: i18n("house.lat"),
                    },
                ],
                callback: result => {
                    modules["house"].doCreateEntrance(houseId, result.entranceType, result.entrance, result.shared, result.lat, result.lon);
                },
            });
        }, () => {
            loadingStart();
            GET("houses", "sharedEntrances", houseId, true).
            done(response => {
                let entrances = [];

                entrances.push({
                    id: 0,
                    text: "-",
                });

                for (let j in response.entrances) {
                    let house = "";

                    if (modules["addresses"] && modules["addresses"].meta && modules["addresses"].meta.houses) {
                        for (let i in modules["addresses"].meta.houses) {
                            if (modules["addresses"].meta.houses[i].houseId == response.entrances[j].houseId) {
                                house = modules["addresses"].meta.houses[i].houseFull;
                            }
                        }
                    }

                    if (!house) {
                        house = "#" + houseId;
                    }

                    entrances.push({
                        id: response.entrances[j].entranceId,
                        text: house + ", " + i18n("house.entranceType" +response.entrances[j].entranceType.substring(0, 1).toUpperCase() + response.entrances[j].entranceType.substring(1) + "Full").toLowerCase() + " " + response.entrances[j].entrance,
                    });
                }

                cardForm({
                    title: i18n("house.addEntrance"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("add"),
                    fields: [
                        {
                            id: "entranceId",
                            type: "select2",
                            title: i18n("house.entrance"),
                            options: entrances,
                        },
                    ],
                    callback: result => {
                        if (parseInt(result.entranceId)) {
                            modules["house"].doAddEntrance(houseId, result.entranceId);
                        }
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }, i18n("house.addNewEntrance"), i18n("house.useExistingEntrance"));
    },

    addFlat: function (houseId) {
        let entrances = [];
        let prefx = md5(guid());

        for (let i in modules["house"].meta.entrances) {
            let inputs = `
                <div class="row mt-2 ${prefx}" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" style="display: none;">
                    <div class="col-6">
                        <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" placeholder="${i18n("house.apartment")}">
                    </div>
                    <div class="col-6">
                        <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" placeholder="${i18n("house.apartmentLevels")}">
                    </div>
                </div>
            `;
            entrances.push({
                id: modules["house"].meta.entrances[i].entranceId,
                text: i18n("house.entranceType" + modules["house"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["house"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["house"].meta.entrances[i].entrance + inputs,
            });
        }

        cardForm({
            title: i18n("house.addFlat"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
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
                },
                {
                    id: "manualBlock",
                    type: "select",
                    title: i18n("house.manualBlock"),
                    placeholder: i18n("house.manualBlock"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("yes"),
                        },
                    ]
                },
                {
                    id: "openCode",
                    type: "text",
                    title: i18n("house.openCode"),
                    placeholder: i18n("house.openCode"),
                },
                {
                    id: "autoOpen",
                    type: "select",
                    title: i18n("house.autoOpen"),
                    placeholder: i18n("house.autoOpen"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("yes"),
                        },
                    ]
                },
                {
                    id: "whiteRabbit",
                    type: "select",
                    title: i18n("house.whiteRabbit"),
                    placeholder: i18n("house.whiteRabbit"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("yes"),
                        },
                    ]
                },
                {
                    id: "sipEnabled",
                    type: "select",
                    title: i18n("house.sipEnabled"),
                    placeholder: i18n("house.sipEnabled"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("house.sip"),
                        },
                        {
                            id: "2",
                            text: i18n("house.webRtc"),
                        },
                    ]
                },
                {
                    id: "sipPassword",
                    type: "text",
                    title: i18n("sipPassword"),
                    placeholder: i18n("sipPassword"),
                    validate: v => {
                        return $.trim(v).length === 0 || $.trim(v).length >= 8;
                    },
                    button: {
                        "class": "fas fa-magic",
                        click: prefix => {
                            PWGen.initialize();
                            $("#" + prefix + "sipPassword").val(PWGen.generate());
                        }
                    }
                },
            ],
            callback: result => {
                let apartmentsAndLevels = {};
                for (let i in entrances) {
                    apartmentsAndLevels[entrances[i].id] = {
                        apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                        apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                    }
                }
                modules["house"].doAddFlat(houseId, result.floor, result.flat, result.entrances, apartmentsAndLevels, result.manualBlock, result.openCode, result.autoOpen, result.whiteRabbit, result.sipEnabled, result.sipPassword);
            },
        });

        $(".checkBoxOption-entrances").off("change").on("change", function () {
            if ($(this).prop("checked")) {
                $("." + prefx + "[data-entrance-id='" + $(this).attr("data-id") + "']").show();
            } else {
                $("." + prefx + "[data-entrance-id='" + $(this).attr("data-id") + "']").hide();
            }
        });
    },

    modifyEntrance: function (entranceId, houseId) {
        let entrance = false;

        for (let i in modules["house"].meta.entrances) {
            if (modules["house"].meta.entrances[i].entranceId == entranceId) {
                entrance = modules["house"].meta.entrances[i];
                break;
            }
        }

        if (entrance) {
            cardForm({
                title: i18n("house.editEntrance"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("house.deleteEntrance"),
                fields: [
                    {
                        id: "entranceId",
                        type: "text",
                        title: i18n("house.entrance"),
                        value: entranceId,
                        readonly: true,
                    },
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
                        ],
                        value: entrance.entranceType,
                    },
                    {
                        id: "entrance",
                        type: "text",
                        title: i18n("house.entrance"),
                        placeholder: i18n("house.entrance"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: entrance.entrance,
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
                        ],
                        value: entrance.shared.toString(),
                    },
                    {
                        id: "lon",
                        type: "text",
                        title: i18n("house.lon"),
                        placeholder: i18n("house.lon"),
                        value: entrance.lon,
                    },
                    {
                        id: "lat",
                        type: "text",
                        title: i18n("house.lat"),
                        placeholder: i18n("house.lat"),
                        value: entrance.lat,
                    },
                ],
                callback: result => {
                    if (result.delete === "yes") {
                        modules["house"].deleteEntrance(entranceId, parseInt(entrance.shared), houseId);
                    } else {
                        modules["house"].doModifyEntrance(entranceId, result.entranceType, result.entrance, result.shared, result.lat, result.lon, houseId);
                    }
                },
            });
        } else {
            error(i18n("house.entranceNotFound"));
        }
    },

    modifyFlat: function (flatId, houseId) {
        let flat = false;

        for (let i in modules["house"].meta.flats) {
            if (modules["house"].meta.flats[i].flatId == flatId) {
                flat = modules["house"].meta.flats[i];
                break;
            }
        }

        if (flat) {

            let entrances = [];
            let entrances_selected = [];
            let entrances_settings = {};

            let prefx = md5(guid());

            for (let i in flat.entrances) {
                entrances_selected.push(flat.entrances[i].entranceId);
                entrances_settings[flat.entrances[i].entranceId] = flat.entrances[i];
            }

            for (let i in modules["house"].meta.entrances) {
                let inputs = `
                    <div class="row mt-2 ${prefx}" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" style="display: none;">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" placeholder="${i18n("house.apartment")}" value="${entrances_settings[modules["house"].meta.entrances[i].entranceId]?entrances_settings[modules["house"].meta.entrances[i].entranceId].apartment:""}">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules["house"].meta.entrances[i].entranceId}" placeholder="${i18n("house.apartmentLevels")}" value="${entrances_settings[modules["house"].meta.entrances[i].entranceId]?entrances_settings[modules["house"].meta.entrances[i].entranceId].apartmentLevels:""}">
                        </div>
                    </div>
                `;
                entrances.push({
                    id: modules["house"].meta.entrances[i].entranceId,
                    text: i18n("house.entranceType" + modules["house"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["house"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["house"].meta.entrances[i].entrance + inputs,
                });
            }

            cardForm({
                title: i18n("house.editFlat"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: houseId?i18n("house.deleteFlat"):false,
                apply: i18n("edit"),
                size: "lg",
                fields: [
                    {
                        id: "flatId",
                        type: "text",
                        title: i18n("house.flatId"),
                        value: flatId,
                        readonly: true,
                    },
                    {
                        id: "floor",
                        type: "text",
                        title: i18n("house.floor"),
                        placeholder: i18n("house.floor"),
                        value: flat.floor,
                    },
                    {
                        id: "flat",
                        type: "text",
                        title: i18n("house.flat"),
                        placeholder: i18n("house.flat"),
                        value: flat.flat,
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
                        value: entrances_selected,
                    },
                    {
                        id: "manualBlock",
                        type: "select",
                        title: i18n("house.manualBlock"),
                        placeholder: i18n("house.manualBlock"),
                        value: flat.manualBlock,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            },
                        ]
                    },
                    {
                        id: "openCode",
                        type: "text",
                        title: i18n("house.openCode"),
                        placeholder: i18n("house.openCode"),
                        value: flat.openCode,
                    },
                    {
                        id: "autoOpen",
                        type: "select",
                        title: i18n("house.autoOpen"),
                        placeholder: i18n("house.autoOpen"),
                        value: flat.autoOpen,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            },
                        ]
                    },
                    {
                        id: "whiteRabbit",
                        type: "select",
                        title: i18n("house.whiteRabbit"),
                        placeholder: i18n("house.whiteRabbit"),
                        value: flat.whiteRabbit,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            },
                        ]
                    },
                    {
                        id: "sipEnabled",
                        type: "select",
                        title: i18n("house.sipEnabled"),
                        placeholder: i18n("house.sipEnabled"),
                        value: flat.sipEnabled,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("house.sip"),
                            },
                            {
                                id: "2",
                                text: i18n("house.webRtc"),
                            },
                        ]
                    },
                    {
                        id: "sipPassword",
                        type: "text",
                        title: i18n("sipPassword"),
                        placeholder: i18n("sipPassword"),
                        value: flat.sipPassword,
                        validate: v => {
                            return $.trim(v).length === 0 || $.trim(v).length >= 8;
                        },
                        button: {
                            "class": "fas fa-magic",
                            click: prefix => {
                                PWGen.initialize();
                                $("#" + prefix + "sipPassword").val(PWGen.generate());
                            }
                        }
                    },
                ],
                callback: result => {
                    let apartmentsAndLevels = {};
                    for (let i in entrances) {
                        apartmentsAndLevels[entrances[i].id] = {
                            apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                            apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                        }
                    }
                    if (result.delete === "yes") {
                        modules["house"].deleteFlat(flatId, houseId);
                    } else {
                        modules["house"].doModifyFlat(flatId, result.floor, result.flat, result.entrances, apartmentsAndLevels, result.manualBlock, result.openCode, result.autoOpen, result.whiteRabbit, result.sipEnabled, result.sipPassword, houseId);
                    }
                },

            });

            for (let i in entrances_selected) {
                $("." + prefx + "[data-entrance-id='" + entrances_selected[i] + "']").show();
            }

            $(".checkBoxOption-entrances").off("change").on("change", function () {
                if ($(this).prop("checked")) {
                    $("." + prefx + "[data-entrance-id='" + $(this).attr("data-id") + "']").show();
                } else {
                    $("." + prefx + "[data-entrance-id='" + $(this).attr("data-id") + "']").hide();
                }
            });
        } else {
            error(i18n("houses.flatNotFound"));
        }
    },

    deleteEntrance: function (entranceId, shared, houseId) {
        if (shared) {
            mYesNo(i18n("house.completelyDeleteEntrance", entranceId), i18n("house.deleteEntrance"), () => {
                modules["house"].doDeleteEntrance(entranceId, true, houseId);
            }, () => {
                modules["house"].doDeleteEntrance(entranceId, false, houseId);
            }, i18n("house.deleteEntranceComletely"), i18n("house.deleteEntranceLink"));
        } else {
            mConfirm(i18n("house.confirmDeleteEntrance", entranceId), i18n("confirm"), `danger:${i18n("house.deleteEntrance")}`, () => {
                modules["house"].doDeleteEntrance(entranceId, true, houseId);
            });
        }
    },

    deleteFlat: function (flatId, houseId) {
        mConfirm(i18n("house.confirmDeleteFlat", flatId), i18n("confirm"), `danger:${i18n("house.deleteFlat")}`, () => {
            modules["house"].doDeleteFlat(flatId, houseId);
        });
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
                edit: flatId => {
                    modules["house"].modifyFlat(flatId, houseId);
                },
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
                                    data: modules["house"].meta.flats[i].floor?modules["house"].meta.flats[i].floor:"-",
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
                edit: entranceId => {
                    modules["house"].modifyEntrance(entranceId, houseId);
                },
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
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-door-open",
                                        title: i18n("domophones.domophone"),
                                        click: entranceId => {
                                            location.href = "#domophones&entranceId=" + entranceId;
                                        },
                                    },
                                    {
                                        icon: "fas fa-video",
                                        title: i18n("cctv.cctv"),
                                        click: entranceId => {
                                            location.href = "#cctv&entranceId=" + entranceId;
                                        },
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            }).show();
        }

        if (modules["addresses"] && modules["addresses"].meta && modules["addresses"].meta.houses) {
            let f = false;
            for (let i in modules["addresses"].meta.houses) {
                if (modules["addresses"].meta.houses[i].houseId == houseId) {
                    if (!modules["house"].meta) {
                        modules["house"].meta = {};
                    }
                    modules["house"].meta.house = modules["addresses"].meta.houses[i];
                    subTop(modules["house"].meta.house.houseFull);
                    f = true;
                }
            }
            if (!f) {
                subTop("#" + houseId);
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

            if (modules["house"].meta.house && modules["house"].meta.house.houseFull) {
                document.title = i18n("windowTitle") + " :: " + i18n("house.house") + " :: " + modules["house"].meta.house.houseFull;
            }

            render();
        });
    },

    renderHouse: function (houseId) {
        if (AVAIL("addresses", "addresses", "GET")) {
            GET("addresses", "addresses").
            done(modules["addresses"].addresses).
            fail(FAIL).
            fail(() => {
                history.back();
            }).
            done(() => {
                modules["house"].house(houseId);
            });
        } else {
            modules["house"].house(houseId);
        }

        loadingDone();
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("house.house");

        modules["house"].renderHouse(params.houseId);
    },
}).init();