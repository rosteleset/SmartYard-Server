({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-home", i18n("addresses.addresses"), "#addresses");
        }

        moduleLoaded("addresses", this);
    },

    addresses: function (addresses) {
        modules["addresses"].meta = addresses["addresses"];
        console.log(modules["addresses"].meta);
    },

    regions: function () {
        loadingStart();
        GET("addresses", "addresses", false, true).
        done(modules["addresses"].addresses).
        done(() => {
            cardTable({
                title: {
                    caption: i18n("addresses.regions"),
                    button: {
                        caption: i18n("addresses.addRegion"),
                        click: modules["addresses"].addRegion,
                    },
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("addresses.regionId"),
                    },
                    {
                        title: i18n("addresses.region"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules["addresses"].meta.regions; i++) {
                        rows.push({
                            uid: modules["addresses"].meta.regions[i].regionId.toString(),
                            cols: [
                                {
                                    data: modules["addresses"].meta.regions[i].regionId,
                                    click: modules["addresses"].modifyRegion,
                                },
                                {
                                    data: rmodules["addresses"].meta.regions[i].region,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
                target: "#mainForm",
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.addresses");
        $("#mainForm").html(i18n("addresses.addresses"));

        switch (params.show) {
            default:
                modules["addresses"].regions();
                break;
        }
    },

    // if search function is defined, search string will be displayed
    search: function (str) {
        console.log("addresses: " + str);
    },
}).init();