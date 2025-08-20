({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.watchers", this);
    },

    renderWatchers: function (params) {
        GET("houses", "watch", params.flatId).
        done(r1 => {
            QUERY("subscribers", "subscribers", {
                by: "flatId",
                query: params.flatId,
            }).done(r2 => {
                console.log(r1, r2);

                for (let i in r1.watchers) {
                    for (let j in r2.subscribers) {

                    }
                }
/*
        "OPEN_BY_KEY" => 3,
        "OPEN_BY_APP" => 4,
        "OPEN_BY_FACE_ID" => 5,
        "OPEN_BY_CODE" => 6,
        "OPEN_BY_CALL" => 7,
        "OPEN_BY_BUTTON" => 8
*/
                cardTable({
                    target: "#mainForm",
                    title: {
                        caption: i18n("addresses.watchers"),
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
                                            icon: "fas fa-trash-alt",
                                            title: i18n("addresses.unwatch"),
                                            class: "text-danger",
                                            click: houseWatcherId => {
                                                mConfirm(i18n("addresses.confirmUnwatch", houseWatcherId), i18n("confirm"), `danger:${i18n("addresses.unwatch")}`, () => {
                                                    modules.addresses.watchers.doUnwatch(houseWatcherId, params.flatId);
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
            }).
            fail(FAIL).
            fail(FAILPAGE).
            always(loadingDone);
        }).
        fail(FAILPAGE).
        fail(loadingDone);
    },

    route: function (params) {
        modules.addresses.topMenu();
        $("#altForm").hide();

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

                    modules.addresses.watchers.renderWatchers(params);
                });
            });
        } else {
            pageError();
        }
    }
}).init();
