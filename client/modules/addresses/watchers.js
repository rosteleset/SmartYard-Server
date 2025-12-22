({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.watchers", this);
    },

    renderWatchers: function (params) {
        GET("houses", "watch", params.flatId).
        done(r1 => {
            QUERY("subscribers", "devices", {
                by: "flatId",
                query: params.flatId,
            }).done(r2 => {
                for (let i in r1.watchers) {
                    for (let j in r2.devices) {
                        if (r1.watchers[i].deviceId == r2.devices[j].deviceId) {
                            r1.watchers[i].subscriber = r2.devices[j].subscriber;
                        }
                    }
                }

                cardTable({
                    target: "#mainForm",
                    title: {
                        caption: i18n("addresses.watchers"),
                    },
                    columns: [
                        {
                            title: i18n("addresses.houseWatcherId"),
                        },
                        {
                            title: i18n("addresses.mobile"),
                            nowrap: true,
                        },
                        {
                            title: i18n("addresses.eventType"),
                            nowrap: true,
                        },
                        {
                            title: i18n("addresses.eventDetail"),
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

                        for (let i in r1.watchers) {
                            rows.push({
                                uid: r1.watchers[i].houseWatcherId,
                                cols: [
                                    {
                                        data: r1.watchers[i].houseWatcherId,
                                    },
                                    {
                                        data: r1.watchers[i].subscriber.mobile,
                                    },
                                    {
                                        data: i18n("addresses.eventType" + r1.watchers[i].eventType),
                                    },
                                    {
                                        data: r1.watchers[i].eventDetail,
                                    },
                                    {
                                        data: r1.watchers[i].comments,
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
                                                    loadingStart();
                                                    DELETE("houses", "watch", houseWatcherId).
                                                    fail(FAIL).
                                                    always(() => {
                                                        modules.addresses.watchers.renderWatchers(params);
                                                    });
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
                            subTop(modules.addresses.addressPath((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId:params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="#" onclick="modules.addresses.houses.modifyFlat(${params.flatId}); event.preventDefault(); return false;">` + params.flat + "</a>");
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
