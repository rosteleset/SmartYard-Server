({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-globe-americas", i18n("addresses.addresses"), "#addresses");
        }

        moduleLoaded("addresses", this);
    },

    addresses: function (addresses) {
        modules["addresses"].meta = addresses["addresses"];
        console.log(modules["addresses"].meta);
    },

    doAddRegion: function (regionFiasId, regionIsoCode, regionWithType, regionType, regionTypeFull, region) {
        loadingStart();
        POST("addresses", "region", false, {
            regionFiasId: regionFiasId,
            regionIsoCode: regionIsoCode,
            regionWithType: regionWithType,
            regionType: regionType,
            regionTypeFull: regionTypeFull,
            region: region,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasAdded"));
        }).
        always(modules["addresses"].renderRegions);
    },

    addRegion: function () {
        cardForm({
            title: i18n("addresses.addRegion"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "regionFiasId",
                    type: "text",
                    title: i18n("addresses.regionFiasId"),
                    placeholder: i18n("addresses.regionFiasId"),
                },
                {
                    id: "regionIsoCode",
                    type: "text",
                    title: i18n("addresses.regionIsoCode"),
                    placeholder: i18n("addresses.regionIsoCode"),
                },
                {
                    id: "regionWithType",
                    type: "text",
                    title: i18n("addresses.regionWithType"),
                    placeholder: i18n("addresses.regionWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "regionType",
                    type: "text",
                    title: i18n("addresses.regionType"),
                    placeholder: i18n("addresses.regionType"),
                },
                {
                    id: "regionTypeFull",
                    type: "text",
                    title: i18n("addresses.regionTypeFull"),
                    placeholder: i18n("addresses.regionTypeFull"),
                },
                {
                    id: "region",
                    type: "text",
                    title: i18n("addresses.region"),
                    placeholder: i18n("addresses.region"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules["addresses"].doAddRegion(result.regionFiasId, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region);
            },
        }).show();
    },

    doModifyRegion: function (regionId, regionFiasId, regionIsoCode, regionWithType, regionType, regionTypeFull, region) {
        loadingStart();
        PUT("addresses", "region", regionId, {
            regionFiasId: regionFiasId,
            regionIsoCode: regionIsoCode,
            regionWithType: regionWithType,
            regionType: regionType,
            regionTypeFull: regionTypeFull,
            region: region,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasAdded"));
        }).
        always(modules["addresses"].renderRegions);
    },

    doDeleteRegion: function (regionId) {
        loadingStart();
        DELETE("addresses", "region", regionId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasDeleted"));
        }).
        always(modules["addresses"].renderRegions);
    },

    deleteRegion: function (regionId) {
        mConfirm(i18n("addresses.confirmDeleteRegion", regionId), i18n("confirm"), `danger:${i18n("addresses.deleteRegion")}`, () => {
            modules["addresses"].doDeleteRegion(regionId);
        });
    },

    modifyRegion: function (regionId) {
        let region = false;

        for (let i in modules["addresses"].meta.regions) {
            if (modules["addresses"].meta.regions[i].regionId == regionId) {
                region = modules["addresses"].meta.regions[i];
                break;
            }
        }

        if (region) {
            cardForm({
                title: i18n("addresses.editRegion"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("address.deleteRegion"),
                fields: [
                    {
                        id: "regionId",
                        type: "text",
                        title: i18n("addresses.regionId"),
                        value: regionId,
                        readonly: true,
                    },
                    {
                        id: "regionFiasId",
                        type: "text",
                        title: i18n("addresses.regionFiasId"),
                        placeholder: i18n("addresses.regionFiasId"),
                        value: region.regionFiasId,
                    },
                    {
                        id: "regionIsoCode",
                        type: "text",
                        title: i18n("addresses.regionIsoCode"),
                        placeholder: i18n("addresses.regionIsoCode"),
                        value: region.regionIsoCode,
                    },
                    {
                        id: "regionWithType",
                        type: "text",
                        title: i18n("addresses.regionWithType"),
                        placeholder: i18n("addresses.regionWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: region.regionWithType,
                    },
                    {
                        id: "regionType",
                        type: "text",
                        title: i18n("addresses.regionType"),
                        placeholder: i18n("addresses.regionType"),
                        value: region.regionType,
                    },
                    {
                        id: "regionTypeFull",
                        type: "text",
                        title: i18n("addresses.regionTypeFull"),
                        placeholder: i18n("addresses.regionTypeFull"),
                        value: region.regionTypeFull,
                    },
                    {
                        id: "region",
                        type: "text",
                        title: i18n("addresses.region"),
                        placeholder: i18n("addresses.region"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: region.region,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules["addresses"].deleteRegion(result.regionId);
                    } else {
                        modules["addresses"].doModifyRegion(regionId, result.regionFiasId, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region);
                    }
                },
            }).show();
        }
    },

    addArea: function (regionId) {

    },

    addCity: function (regionId, areaId) {

    },

    modifyArea: function (areaId) {

    },

    modifyCity: function (cityId) {

    },

    renderRegion: function (regionId) {
        loadingStart();
        GET("addresses", "addresses", false, true).
        done(modules["addresses"].addresses).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.areas"),
                    button: {
                        caption: i18n("addresses.addArea"),
                        click: modules["addresses"].addArea(regionId),
                    },
                    filter: true,
                },
                edit: modules["addresses"].modifyArea,
                columns: [
                    {
                        title: i18n("addresses.areaId"),
                    },
                    {
                        title: i18n("addresses.area"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules["addresses"].meta.areas) {
                        if (modules["addresses"].meta.areas[i].regionId == regionId) {
                            rows.push({
                                uid: modules["addresses"].meta.areas[i].areasId,
                                cols: [
                                    {
                                        data: modules["addresses"].meta.areas[i].areasId,
                                    },
                                    {
                                        data: modules["addresses"].meta.areas[i].areaWithType,
                                        nowrap: true,
                                        click: "#addresses&show=area&areaId=%s",
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            });
            cardTable({
                target: "#altForm",
                title: {
                    caption: i18n("addresses.cities"),
                    button: {
                        caption: i18n("addresses.addCity"),
                        click: modules["addresses"].addCity(regionId),
                    },
                    filter: true,
                },
                edit: modules["addresses"].modifyCity,
                columns: [
                    {
                        title: i18n("addresses.cityId"),
                    },
                    {
                        title: i18n("addresses.city"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules["addresses"].meta.cities) {
                        if (modules["addresses"].meta.cities[i].regionId == regionId && !modules["addresses"].meta.cities[i].areaId) {
                            rows.push({
                                uid: modules["addresses"].meta.cities[i].citiyId,
                                cols: [
                                    {
                                        data: modules["addresses"].meta.cities[i].cityId,
                                    },
                                    {
                                        data: modules["addresses"].meta.cities[i].cityWithType,
                                        nowrap: true,
                                        click: "#addresses&show=city&cityId=%s",
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderRegions: function () {
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
                edit: modules["addresses"].modifyRegion,
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

                    for (let i in modules["addresses"].meta.regions) {
                        rows.push({
                            uid: modules["addresses"].meta.regions[i].regionId.toString(),
                            cols: [
                                {
                                    data: modules["addresses"].meta.regions[i].regionId,
                                },
                                {
                                    data: modules["addresses"].meta.regions[i].regionWithType,
                                    nowrap: true,
                                    click: "#addresses&show=region&regionId=%s",
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
            case "region":
                modules["addresses"].renderRegion(params.regionId);
                break;
            default:
                modules["addresses"].renderRegions();
                break;
        }
    },

    // if search function is defined, search string will be displayed
    search: function (str) {
        console.log("addresses: " + str);
    },
}).init();