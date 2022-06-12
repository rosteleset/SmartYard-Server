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

    doAddRegion: function (regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region) {
        loadingStart();
        POST("addresses", "region", false, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasAdded"));
        }).
        always(modules["addresses"].renderRegions);
    },

    doAddArea: function (regionId, areaUuid, areaWithType, areaType, areaTypeFull, area) {
        loadingStart();
        POST("addresses", "area", false, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasAdded"));
        }).
        always(() => {
            modules["addresses"].renderRegion(regionId);
        });
    },

    doAddCity: function (regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city) {
        loadingStart();
        POST("addresses", "city", false, {
            regionId,
            areaId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasAdded"));
        }).
        always(() => {
            if (regionId) {
                modules["addresses"].renderRegion(regionId);
            } else {
                modules["addresses"].renderArea(areaId);
            }
        });
    },

    addRegion: function () {
        cardForm({
            title: i18n("addresses.addRegion"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "regionUuid",
                    type: "text",
                    title: i18n("addresses.regionUuid"),
                    placeholder: i18n("addresses.regionUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}regionUuid`).val(guid());
                        },
                    },
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
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
                modules["addresses"].doAddRegion(result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region);
            },
        }).show();
    },

    doModifyRegion: function (regionId, regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region) {
        loadingStart();
        PUT("addresses", "region", regionId, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasChanged"));
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

    doModifyArea: function (areaId, regionId, areaUuid, areaWithType, areaType, areaTypeFull, area, targetRegionId) {
        loadingStart();
        PUT("addresses", "area", areaId, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasChanged"));
        }).
        always(() => {
            if (regionId == targetRegionId) {
                modules["addresses"].renderRegion(regionId);
            } else {
                location.href = "#addresses&show=region&regionId=" + regionId;
            }
        });
    },

    doModifyCity: function (cityId, regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city, targetRegionId, targetAreaId) {
        loadingStart();
        PUT("addresses", "city", cityId, {
            areaId,
            regionId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasChanged"));
        }).
        always(() => {
            if (regionId) {
                if (regionId == targetRegionId) {
                    modules["addresses"].renderRegion(regionId);
                } else {
                    location.href = "#addresses&show=region&regionId=" + regionId;
                }
            } else {
                if (areaId == targetAreaId) {
                    modules["addresses"].renderRegion(areaId);
                } else {
                    location.href = "#addresses&show=area&areaId=" + areaId;
                }
            }
        });
    },

    doDeleteArea: function (areaId, regionId) {
        loadingStart();
        DELETE("addresses", "area", areaId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasDeleted"));
        }).
        always(() => {
            modules["addresses"].renderRegion(regionId);
        });
    },

    doDeleteCity: function (cityId, regionId, areaId) {
        loadingStart();
        DELETE("addresses", "city", cityId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasDeleted"));
        }).
        always(() => {
            if (regionId) {
                modules["addresses"].renderRegion(regionId);
            } else {
                modules["addresses"].renderArea(areaId);
            }
        });
    },

    deleteRegion: function (regionId) {
        mConfirm(i18n("addresses.confirmDeleteRegion", regionId), i18n("confirm"), `danger:${i18n("addresses.deleteRegion")}`, () => {
            modules["addresses"].doDeleteRegion(regionId);
        });
    },

    deleteArea: function (areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteArea", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteArea")}`, () => {
            modules["addresses"].doDeleteArea(areaId, regionId);
        });
    },

    deleteCity: function (cityId, areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteCity", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteCity")}`, () => {
            modules["addresses"].doDeleteCity(cityId, areaId, regionId);
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
                size: "lg",
                fields: [
                    {
                        id: "regionId",
                        type: "text",
                        title: i18n("addresses.regionId"),
                        value: regionId,
                        readonly: true,
                    },
                    {
                        id: "regionUuid",
                        type: "text",
                        title: i18n("addresses.regionUuid"),
                        placeholder: i18n("addresses.regionUuid"),
                        value: region.regionUuid,
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
                        modules["addresses"].doModifyRegion(regionId, result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.regionNotFound"));
        }
    },

    addArea: function (regionId) {
        cardForm({
            title: i18n("addresses.addArea"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "areaUuid",
                    type: "text",
                    title: i18n("addresses.areaUuid"),
                    placeholder: i18n("addresses.areaUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}areaUuid`).val(guid());
                        },
                    },
                },
                {
                    id: "areaWithType",
                    type: "text",
                    title: i18n("addresses.areaWithType"),
                    placeholder: i18n("addresses.areaWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "areaType",
                    type: "text",
                    title: i18n("addresses.areaType"),
                    placeholder: i18n("addresses.areaType"),
                },
                {
                    id: "areaTypeFull",
                    type: "text",
                    title: i18n("addresses.areaTypeFull"),
                    placeholder: i18n("addresses.areaTypeFull"),
                },
                {
                    id: "area",
                    type: "text",
                    title: i18n("addresses.area"),
                    placeholder: i18n("addresses.area"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules["addresses"].doAddArea(regionId, result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area);
            },
        }).show();
    },

    addCity: function (regionId, areaId) {
        cardForm({
            title: i18n("addresses.addCity"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "cityUuid",
                    type: "text",
                    title: i18n("addresses.cityUuid"),
                    placeholder: i18n("addresses.cityUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}cityUuid`).val(guid());
                        },
                    },
                },
                {
                    id: "cityWithType",
                    type: "text",
                    title: i18n("addresses.cityWithType"),
                    placeholder: i18n("addresses.cityWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "cityType",
                    type: "text",
                    title: i18n("addresses.cityType"),
                    placeholder: i18n("addresses.cityType"),
                },
                {
                    id: "cityTypeFull",
                    type: "text",
                    title: i18n("addresses.cityTypeFull"),
                    placeholder: i18n("addresses.cityTypeFull"),
                },
                {
                    id: "city",
                    type: "text",
                    title: i18n("addresses.city"),
                    placeholder: i18n("addresses.city"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules["addresses"].doAddCity(regionId, areaId, result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city);
            },
        }).show();
    },

    modifyArea: function (areaId) {
        let area = false;

        for (let i in modules["addresses"].meta.areas) {
            if (modules["addresses"].meta.areas[i].areaId == areaId) {
                area = modules["addresses"].meta.areas[i];
                break;
            }
        }

        let regions = [];
        for (let i in modules["addresses"].meta.regions) {
            regions.push({
                id: modules["addresses"].meta.regions[i].regionId,
                text: modules["addresses"].meta.regions[i].regionWithType,
            });
        }

        if (area) {
            cardForm({
                title: i18n("addresses.editArea"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("address.deleteArea"),
                size: "lg",
                fields: [
                    {
                        id: "areaId",
                        type: "text",
                        title: i18n("addresses.areaId"),
                        value: areaId,
                        readonly: true,
                    },
                    {
                        id: "regionId",
                        type: "select2",
                        title: i18n("addresses.regionId"),
                        value: area.regionId,
                        options: regions,
                    },
                    {
                        id: "areaUuid",
                        type: "text",
                        title: i18n("addresses.areaUuid"),
                        placeholder: i18n("addresses.areaUuid"),
                        value: area.areaUuid,
                    },
                    {
                        id: "areaWithType",
                        type: "text",
                        title: i18n("addresses.areaWithType"),
                        placeholder: i18n("addresses.areaWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: area.areaWithType,
                    },
                    {
                        id: "areaType",
                        type: "text",
                        title: i18n("addresses.areaType"),
                        placeholder: i18n("addresses.areaType"),
                        value: area.areaType,
                    },
                    {
                        id: "areaTypeFull",
                        type: "text",
                        title: i18n("addresses.areaTypeFull"),
                        placeholder: i18n("addresses.areaTypeFull"),
                        value: area.areaTypeFull,
                    },
                    {
                        id: "area",
                        type: "text",
                        title: i18n("addresses.area"),
                        placeholder: i18n("addresses.area"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: area.area,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules["addresses"].deleteArea(result.areaId, parseInt(area.regionId));
                    } else {
                        modules["addresses"].doModifyArea(areaId, parseInt(result.regionId), result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area, parseInt(area.regionId));
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.areaNotFound"));
        }
    },

    modifyCity: function (cityId) {
        let city = false;

        for (let i in modules["addresses"].meta.cities) {
            if (modules["addresses"].meta.cities[i].cityId == cityId) {
                city = modules["addresses"].meta.cities[i];
                break;
            }
        }

        let regions = [];

        regions.push({
            id: "0",
            text: "-",
        })
        for (let i in modules["addresses"].meta.regions) {
            regions.push({
                id: modules["addresses"].meta.regions[i].regionId,
                text: modules["addresses"].meta.regions[i].regionWithType,
            });
        }

        let areas = [];

        areas.push({
            id: "0",
            text: "-",
        })
        for (let i in modules["addresses"].meta.areas) {
            areas.push({
                id: modules["addresses"].meta.areas[i].areaId,
                text: modules["addresses"].meta.areas[i].areaWithType,
            });
        }

        if (city) {
            cardForm({
                title: i18n("addresses.editCity"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("address.deleteCity"),
                size: "lg",
                fields: [
                    {
                        id: "cityId",
                        type: "text",
                        title: i18n("addresses.cityId"),
                        value: cityId,
                        readonly: true,
                    },
                    {
                        id: "regionId",
                        type: "select2",
                        title: i18n("addresses.regionId"),
                        value: city.regionId,
                        options: regions,
                        select: (el, id, prefix) => {
                            $(`#${prefix}areaId`).val("0").trigger("change");
                        },
                    },
                    {
                        id: "areaId",
                        type: "select2",
                        title: i18n("addresses.areaId"),
                        value: city.areaId,
                        options: areas,
                        select: (el, id, prefix) => {
                            $(`#${prefix}regionId`).val("0").trigger("change");
                        },
                    },
                    {
                        id: "cityUuid",
                        type: "text",
                        title: i18n("addresses.cityUuid"),
                        placeholder: i18n("addresses.cityUuid"),
                        value: city.cityUuid,
                    },
                    {
                        id: "cityWithType",
                        type: "text",
                        title: i18n("addresses.cityWithType"),
                        placeholder: i18n("addresses.cityWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: city.cityWithType,
                    },
                    {
                        id: "cityType",
                        type: "text",
                        title: i18n("addresses.cityType"),
                        placeholder: i18n("addresses.cityType"),
                        value: city.cityType,
                    },
                    {
                        id: "cityTypeFull",
                        type: "text",
                        title: i18n("addresses.cityTypeFull"),
                        placeholder: i18n("addresses.cityTypeFull"),
                        value: city.cityTypeFull,
                    },
                    {
                        id: "city",
                        type: "text",
                        title: i18n("addresses.city"),
                        placeholder: i18n("addresses.city"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: city.city,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules["addresses"].deleteCity(result.cityId, parseInt(city.regionId), parseInt(city.areaId));
                    } else {
                        modules["addresses"].doModifyCity(cityId, parseInt(result.regionId), parseInt(result.areaId), result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city, parseInt(city.regionId), parseInt(city.areaId));
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.cityNotFound"));
        }
    },

    cities: function (target, regionId, areaId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.cities"),
                button: {
                    caption: i18n("addresses.addCity"),
                    click: () => {
                        modules["addresses"].addCity(regionId, areaId);
                    },
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
                    if ((regionId && modules["addresses"].meta.cities[i].regionId == regionId && !modules["addresses"].meta.cities[i].areaId) || (areaId && modules["addresses"].meta.cities[i].areaId == areaId && !modules["addresses"].meta.cities[i].regionId)) {
                        rows.push({
                            uid: modules["addresses"].meta.cities[i].cityId,
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
    },

    settlements: function (target, areaId, cityId) {
        // TODO
    },

    streets: function (target, cityId, settlementId) {
        // TODO
    },

    houses: function (target, settlementId, streetId) {
        // TODO
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
                        click: () => {
                            modules["addresses"].addArea(regionId);
                        },
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
                                uid: modules["addresses"].meta.areas[i].areaId,
                                cols: [
                                    {
                                        data: modules["addresses"].meta.areas[i].areaId,
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
            modules["addresses"].cities("#altForm", regionId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderArea: function (areaId) {
        loadingStart();
        GET("addresses", "addresses", false, true).
        done(modules["addresses"].addresses).
        done(() => {
            modules["addresses"].cities("#mainForm", false, areaId);
            modules["addresses"].settlements("#altForm", areaId, false);
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

        if (!params.show) {
            params.show = "regions";
        }

        switch (params.show) {
            case "region":
                modules["addresses"].renderRegion(params.regionId);
                break;
            case "area":
                modules["addresses"].renderArea(params.areaId);
                break;
            case "regions":
                modules["addresses"].renderRegions();
                break;
            default:
                page404();
                break;
        }
    },

    // if search function is defined, search string will be displayed
    search: function (str) {
        console.log("addresses: " + str);
    },
}).init();