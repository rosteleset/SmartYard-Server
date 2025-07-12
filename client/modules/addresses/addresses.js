({
    menuItem: false,
    favorites: [],
    subModules: [
        "keys",
        "houses",
        "domophones",
        "cameras",
        "subscribers",
        "subscriberInbox",
        "subscriberDevices",
        "_search",
    ],

    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            this.menuItem = leftSide("fas fa-fw fa-globe-americas", i18n("addresses.addresses"), "?#addresses", "households");
        }

        loadSubModules("addresses", JSON.parse(JSON.stringify(this.subModules)), this);
    },

    allLoaded: function () {
        if (modules.addresses.menuItem) {
            GET("addresses", "favorites", false, true).
            done(r => {
                if (r && r.favorites) {
                    modules.addresses.favorites = r.favorites;
                    let h = "";
                    for (let i in r.favorites) {
                        let url;
                        if (r.favorites[i].object == "house") {
                            url = `?#addresses.houses&houseId=${r.favorites[i].id}`;
                        } else {
                            url = `?#addresses&show=${r.favorites[i].object}&${r.favorites[i].object}Id=${r.favorites[i].id}`;
                        }
                        h += `
                            <li class="nav-item" title="${escapeHTML(r.favorites[i].title)}" style="margin-top: 3px;">
                                    <a href="${url}" class="nav-link" onclick="xblur(); return true;">
                                    <i class="nav-icon fa-fw ${r.favorites[i].icon} ${r.favorites[i].color}"></i>
                                    <p class="text-nowrap">${escapeHTML(r.favorites[i].title)}</p>
                                </a>
                            </li>
                        `;
                        $(`.addressFavoriteIcon[data-object='${r.favorites[i].object}'][data-object-id='${r.favorites[i].id}']`).removeClass("far").addClass("fas");
                    }
                    let i = $('#' + modules.addresses.menuItem);
                    let f = false;
                    while (i.next().length) {
                        i = i.next();
                        if ($.trim(i.text()) == "") {
                            $(h).insertBefore(i);
                            f = true;
                            break;
                        }
                    }
                    if (!f && i.length) {
                        $(h).insertAfter(i);
                    }
                }
            }).
            fail(FAIL);
        }

        for (let i in modules.addresses.subModules) {
            if (modules.addresses.subModules[i] != "_search") {
                modules.addresses[modules.addresses.subModules[i]].search = modules.addresses._search.search;
            }
        }

        modules.addresses.search = modules.addresses._search.search;
    },

    moduleLoaded: function () {
        //
    },

    addresses: function (addresses) {
        modules.addresses.meta = addresses["addresses"];
    },

    toggleFavorite: function (object, id) {
        let f = false;
        for (let i in modules.addresses.favorites) {
            if (modules.addresses.favorites[i].object == object && modules.addresses.favorites[i].id == id) {
                f = true;
                break;
            }
        }

        let title = "";

        if (object == "house") {
            for (let i in modules.addresses.meta.houses) {
                if (modules.addresses.meta.houses[i]["houseId"] == id) {
                    title = modules.addresses.meta.houses[i].houseFull;
                    break;
                }
            }
        }
        if (object == "city") {
            for (let i in modules.addresses.meta.cities) {
                if (modules.addresses.meta.cities[i]["cityId"] == id) {
                    title = modules.addresses.meta.cities[i].cityWithType;
                    break;
                }
            }
        }
        else {
            for (let i in modules.addresses.meta[object + "s"]) {
                if (modules.addresses.meta[object + "s"][i][object + "Id"] == id) {
                    title = modules.addresses.meta[object + "s"][i][object + "WithType"];
                    break;
                }
            }
        }

        if (!f) {
            let icons = [];
            for (let i in faIcons) {
                icons.push({
                    icon: faIcons[i].title + " fa-fw",
                    text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                    value: faIcons[i].title,
                });
            }

            cardForm({
                title: i18n("addresses.addFavorite"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                size: "lg",
                fields: [
                    {
                        id: "title",
                        title: i18n("addresses.favoriteTitle"),
                        type: "text",
                        value: title,
                    },
                    {
                        id: "icon",
                        title: i18n("addresses.favoriteIcon"),
                        type: "select2",
                        options: icons,
                        value: "far fa-bookmark",
                    },
                    {
                        id: "color",
                        title: i18n("addresses.favoriteColor"),
                        type: "select2",
                        options: [
                            {
                                text: "По умолчанию",
                                value: "",
                                class: "",
                            },
                            {
                                text: "Primary",
                                value: "text-primary",
                                class: "text-primary",
                            },
                            {
                                text: "Secondary",
                                value: "text-secondary",
                                class: "text-secondary",
                            },
                            {
                                text: "Success",
                                value: "text-success",
                                class: "text-success",
                            },
                            {
                                text: "Danger",
                                value: "text-danger",
                                class: "text-danger",
                            },
                            {
                                text: "Warning",
                                value: "text-warning",
                                class: "text-warning",
                            },
                            {
                                text: "Info",
                                value: "text-info",
                                class: "text-info",
                            },
                        ],
                        value: ""
                    },
                ],
                callback: r => {
                    loadingStart();
                    POST("addresses", "favorites", false, {
                        object: object,
                        id: id,
                        title: r.title,
                        icon: r.icon,
                        color: r.color,
                    }).
                    done(() => {
                        window.location.reload();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                },
            });
        } else {
            mConfirm(i18n("addresses.removeFavorite") + "?", title, i18n("remove"), () => {
                loadingStart();
                DELETE("addresses", "favorites", false, {
                    object: object,
                    id: id,
                }).
                done(() => {
                    window.location.reload();
                }).
                fail(FAIL).
                fail(loadingDone);
            });
        }
    },

    path: function (object, id, _link) {
        let sp = "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>";

        function link(target, text, id) {
            return `<a href="?#addresses&show=${target}&${target}Id=${id}">${text}</a>`;
        }

        function favorite(object, id) {
            let f = false;
            for (let i in modules.addresses.favorites) {
                if (modules.addresses.favorites[i].object == object && modules.addresses.favorites[i].id == id) {
                    f = true;
                    break;
                }
            }
            if (f) {
                return `<span style='position: absolute; right: 0px;' class='mr-3' onclick='modules.addresses.toggleFavorite("${object}", ${id})'><i class='fas fa-fw fa-bookmark text-primary pointer addressFavoriteIcon' data-object='${object}' data-object-id='${id}'></i></span>`;
            } else {
                return `<span style='position: absolute; right: 0px;' class='mr-3' onclick='modules.addresses.toggleFavorite("${object}", ${id})'><i class='far fa-fw fa-bookmark text-primary pointer addressFavoriteIcon' data-object='${object}' data-object-id='${id}'></i></span>`;
            }
        }

        function region(id) {
            for (let i in modules.addresses.meta.regions) {
                if (modules.addresses.meta.regions[i].regionId == id) {
                    return modules.addresses.meta.regions[i];
                }
            }
        }

        function area(id) {
            for (let i in modules.addresses.meta.areas) {
                if (modules.addresses.meta.areas[i].areaId == id) {
                    let a = modules.addresses.meta.areas[i];
                    let r = region(a.regionId);
                    a.parent = link("region", r.regionWithType, r.regionId);
                    return a;
                }
            }
        }

        function city(id) {
            for (let i in modules.addresses.meta.cities) {
                if (modules.addresses.meta.cities[i].cityId == id) {
                    let c = modules.addresses.meta.cities[i];
                    if (c.regionId) {
                        let r = region(c.regionId);
                        c.parent = link("region", r.regionWithType, r.regionId);
                    } else {
                        let a = area(c.areaId);
                        c.parent = a.parent + sp + link("area", a.areaWithType, a.areaId);
                    }
                    return c;
                }
            }
        }

        function settlement(id) {
            for (let i in modules.addresses.meta.settlements) {
                if (modules.addresses.meta.settlements[i].settlementId == id) {
                    let s = modules.addresses.meta.settlements[i];
                    if (s.areaId) {
                        let a = area(s.areaId);
                        s.parent = a.parent + sp + link("area", a.areaWithType, a.areaId);
                    } else {
                        let c = city(s.cityId);
                        s.parent = c.parent + sp + link("city", c.cityWithType, c.cityId);
                    }
                    return s;
                }
            }
        }

        function street(id) {
            for (let i in modules.addresses.meta.streets) {
                if (modules.addresses.meta.streets[i].streetId == id) {
                    let s = modules.addresses.meta.streets[i];
                    if (s.cityId) {
                        let c = city(s.cityId);
                        s.parent = c.parent + sp + link("city", c.cityWithType, c.cityId);
                    } else {
                        let e = settlement(s.settlementId);
                        s.parent = e.parent + sp + link("settlement", e.settlementWithType, e.settlementId);
                    }
                    return s;
                }
            }
        }

        switch (object) {
            case "region":
                return region(id).regionWithType + favorite(object, id);

            case "area":
                let a = area(id);
                if (_link) {
                    return a.parent + sp + link("area", a.areaWithType, id);
                } else {
                    return a.parent + sp + a.areaWithType + favorite(object, id);
                }

            case "city":
                let c = city(id);
                if (_link) {
                    return c.parent + sp + link("city", c.cityWithType, id);
                } else {
                    return c.parent + sp + c.cityWithType + favorite(object, id);
                }

            case "settlement":
                let se = settlement(id);
                if (_link) {
                    return se.parent + sp + link("settlement", se.settlementWithType, id);
                } else {
                    return se.parent + sp + se.settlementWithType + favorite(object, id);
                }

            case "street":
                let st = street(id);
                if (_link) {
                    return st.parent + sp + link("street", st.streetWithType, id);
                } else {
                    return st.parent + sp + st.streetWithType + favorite(object, id);
                }

            default:
                return "";
        }
    },

    doAddRegion: function (regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region, timezone) {
        loadingStart();
        POST("addresses", "region", false, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasAdded"));
        }).
        always(modules.addresses.renderRegions);
    },

    doAddArea: function (regionId, areaUuid, areaWithType, areaType, areaTypeFull, area, timezone) {
        loadingStart();
        POST("addresses", "area", false, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasAdded"));
        }).
        always(() => {
            modules.addresses.renderRegion(regionId);
        });
    },

    doAddCity: function (regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city, timezone) {
        loadingStart();
        POST("addresses", "city", false, {
            regionId,
            areaId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasAdded"));
        }).
        always(() => {
            if (regionId) {
                modules.addresses.renderRegion(regionId);
            } else {
                modules.addresses.renderArea(areaId);
            }
        });
    },

    doAddSettlement: function (areaId, cityId, settlementUuid, settlementWithType, settlementType, settlementTypeFull, settlement) {
        loadingStart();
        POST("addresses", "settlement", false, {
            areaId,
            cityId,
            settlementUuid,
            settlementWithType,
            settlementType,
            settlementTypeFull,
            settlement,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasAdded"));
        }).
        always(() => {
            if (areaId) {
                modules.addresses.renderArea(areaId);
            } else {
                modules.addresses.renderCity(cityId);
            }
        });
    },

    doAddStreet: function (cityId, settlementId, streetUuid, streetWithType, streetType, streetTypeFull, street) {
        loadingStart();
        POST("addresses", "street", false, {
            cityId,
            settlementId,
            streetUuid,
            streetWithType,
            streetType,
            streetTypeFull,
            street,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasAdded"));
        }).
        always(() => {
            if (cityId) {
                modules.addresses.renderCity(cityId);
            } else {
                modules.addresses.renderSettlement(settlementId);
            }
        });
    },

    doAddHouse: function (settlementId, streetId, houseUuid, houseType, houseTypeFull, houseFull, house, companyId) {
        loadingStart();
        POST("addresses", "house", false, {
            settlementId,
            streetId,
            houseUuid,
            houseType,
            houseTypeFull,
            houseFull,
            house,
            companyId,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasAdded"));
        }).
        always(() => {
            if (settlementId) {
                modules.addresses.renderSettlement(settlementId);
            } else {
                modules.addresses.renderStreet(streetId);
            }
        });
    },

    doModifyRegion: function (regionId, regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region, timezone) {
        loadingStart();
        PUT("addresses", "region", regionId, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasChanged"));
        }).
        always(modules.addresses.renderRegions);
    },

    doModifyArea: function (areaId, regionId, areaUuid, areaWithType, areaType, areaTypeFull, area, targetRegionId, timezone) {
        loadingStart();
        PUT("addresses", "area", areaId, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasChanged"));
        }).
        always(() => {
            if (regionId == targetRegionId) {
                modules.addresses.renderRegion(regionId);
            } else {
                window.location.href = "?#addresses&show=region&regionId=" + regionId;
            }
        });
    },

    doModifyCity: function (cityId, regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city, targetRegionId, targetAreaId, timezone) {
        loadingStart();
        PUT("addresses", "city", cityId, {
            areaId,
            regionId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasChanged"));
        }).
        always(() => {
            if (regionId) {
                if (regionId == targetRegionId) {
                    modules.addresses.renderRegion(regionId);
                } else {
                    window.location.href = "?#addresses&show=region&regionId=" + targetRegionId + "&_=" + Math.random();
                }
            } else {
                if (areaId == targetAreaId) {
                    modules.addresses.renderArea(areaId);
                } else {
                    window.location.href = "?#addresses&show=area&areaId=" + targetAreaId + "&_=" + Math.random();
                }
            }
        });
    },

    doModifySettlement: function (settlementId, areaId, cityId, settlementUuid, settlementWithType, settlementType, settlementTypeFull, settlement, targetAreaId, targetCityId) {
        loadingStart();
        PUT("addresses", "settlement", settlementId, {
            settlementId,
            areaId,
            cityId,
            settlementUuid,
            settlementWithType,
            settlementType,
            settlementTypeFull,
            settlement
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasChanged"));
        }).
        always(() => {
            if (areaId) {
                if (areaId == targetAreaId) {
                    modules.addresses.renderArea(areaId);
                } else {
                    window.location.href = "?#addresses&show=area&areaId=" + areaId;
                }
            } else {
                if (cityId == targetCityId) {
                    modules.addresses.renderCity(cityId);
                } else {
                    window.location.href = "?#addresses&show=city&cityId=" + cityId;
                }
            }
        });
    },

    doModifyStreet: function (streetId, cityId, settlementId, streetUuid, streetWithType, streetType, streetTypeFull, street, targetCityId, targetSettlementId) {
        loadingStart();
        PUT("addresses", "street", streetId, {
            streetId,
            cityId,
            settlementId,
            streetUuid,
            streetWithType,
            streetType,
            streetTypeFull,
            street
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasChanged"));
        }).
        always(() => {
            if (cityId) {
                if (cityId == targetCityId) {
                    modules.addresses.renderCity(cityId);
                } else {
                    window.location.href = "?#addresses&show=city&cityId=" + cityId;
                }
            } else {
                if (settlementId == targetSettlementId) {
                    modules.addresses.renderSettlement(settlementId);
                } else {
                    window.location.href = "?#addresses&show=settlement&settlementId=" + settlementId;
                }
            }
        });
    },

    doModifyHouse: function (houseId, settlementId, streetId, houseUuid, houseType, houseTypeFull, houseFull, house, targetSettlementId, targetStreetId, companyId) {
        loadingStart();
        PUT("addresses", "house", houseId, {
            houseId,
            settlementId,
            streetId,
            houseUuid,
            houseType,
            houseTypeFull,
            houseFull,
            house,
            companyId,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasChanged"));
        }).
        always(() => {
            if (settlementId) {
                if (settlementId == targetSettlementId) {
                    modules.addresses.renderSettlement(settlementId);
                } else {
                    window.location.href = "?#addresses&show=settlement&settlementId=" + settlementId;
                }
            } else {
                if (streetId == targetStreetId) {
                    modules.addresses.renderStreet(streetId);
                } else {
                    window.location.href = "?#addresses&show=street&streetId=" + streetId;
                }
            }
        });
    },

    doDeleteRegion: function (regionId) {
        loadingStart();
        DELETE("addresses", "region", regionId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasDeleted"));
        }).
        always(modules.addresses.renderRegions);
    },

    doDeleteArea: function (areaId, regionId) {
        loadingStart();
        DELETE("addresses", "area", areaId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasDeleted"));
        }).
        always(() => {
            modules.addresses.renderRegion(regionId);
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
                modules.addresses.renderRegion(regionId);
            } else {
                modules.addresses.renderArea(areaId);
            }
        });
    },

    doDeleteSettlement: function (settlementId, areaId, cityId) {
        loadingStart();
        DELETE("addresses", "settlement", settlementId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasDeleted"));
        }).
        always(() => {
            if (areaId) {
                modules.addresses.renderArea(areaId);
            } else {
                modules.addresses.renderCity(cityId);
            }
        });
    },

    doDeleteStreet: function (streetId, cityId, settlementId) {
        loadingStart();
        DELETE("addresses", "street", streetId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasDeleted"));
        }).
        always(() => {
            if (cityId) {
                modules.addresses.renderCity(cityId);
            } else {
                modules.addresses.renderSettlement(settlementId);
            }
        });
    },

    doDeleteHouse: function (houseId, settlementId, streetId) {
        loadingStart();
        DELETE("addresses", "house", houseId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasDeleted"));
        }).
        always(() => {
            if (settlementId) {
                modules.addresses.renderSettlement(settlementId);
            } else {
                modules.addresses.renderStreet(streetId);
            }
        });
    },

    deleteRegion: function (regionId) {
        mConfirm(i18n("addresses.confirmDeleteRegion", regionId), i18n("confirm"), `danger:${i18n("addresses.deleteRegion")}`, () => {
            modules.addresses.doDeleteRegion(regionId);
        });
    },

    deleteArea: function (areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteArea", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteArea")}`, () => {
            modules.addresses.doDeleteArea(areaId, regionId);
        });
    },

    deleteCity: function (cityId, areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteCity", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteCity")}`, () => {
            modules.addresses.doDeleteCity(cityId, areaId, regionId);
        });
    },

    deleteSettlement: function (settlementId, cityId, areaId) {
        mConfirm(i18n("addresses.confirmDeleteSettlement", settlementId), i18n("confirm"), `danger:${i18n("addresses.deleteSettlement")}`, () => {
            modules.addresses.doDeleteSettlement(settlementId, cityId, areaId);
        });
    },

    deleteStreet: function (streetId, settlementId, cityId) {
        mConfirm(i18n("addresses.confirmDeleteStreet", streetId), i18n("confirm"), `danger:${i18n("addresses.deleteStreet")}`, () => {
            modules.addresses.doDeleteStreet(streetId, settlementId, cityId);
        });
    },

    deleteHouse: function (houseId, streetId, settlementId) {
        mConfirm(i18n("addresses.confirmDeleteHouse", houseId), i18n("confirm"), `danger:${i18n("addresses.deleteHouse")}`, () => {
            modules.addresses.doDeleteHouse(houseId, streetId, settlementId);
        });
    },

    modifyRegion: function (regionId) {
        let region = false;

        for (let i in modules.addresses.meta.regions) {
            if (modules.addresses.meta.regions[i].regionId == regionId) {
                region = modules.addresses.meta.regions[i];
                break;
            }
        }

        if (region) {
            cardForm({
                title: i18n("addresses.editRegion"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteRegion"),
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
                        validate: v => {
                            return !!v;
                        },
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
                        validate: v => {
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
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: region.region,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: timezonesOptions(),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: region.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteRegion(result.regionId);
                    } else {
                        modules.addresses.doModifyRegion(regionId, result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region, result.timezone);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.regionNotFound"));
        }
    },

    modifyArea: function (areaId) {
        let area = false;

        for (let i in modules.addresses.meta.areas) {
            if (modules.addresses.meta.areas[i].areaId == areaId) {
                area = modules.addresses.meta.areas[i];
                break;
            }
        }

        let regions = [];
        for (let i in modules.addresses.meta.regions) {
            regions.push({
                id: modules.addresses.meta.regions[i].regionId,
                text: modules.addresses.meta.regions[i].regionWithType,
            });
        }

        if (area) {
            cardForm({
                title: i18n("addresses.editArea"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteArea"),
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
                        title: i18n("addresses.region"),
                        value: area.regionId,
                        options: regions,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "areaUuid",
                        type: "text",
                        title: i18n("addresses.areaUuid"),
                        placeholder: i18n("addresses.areaUuid"),
                        value: area.areaUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "areaWithType",
                        type: "text",
                        title: i18n("addresses.areaWithType"),
                        placeholder: i18n("addresses.areaWithType"),
                        validate: v => {
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
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: area.area,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: timezonesOptions(),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: area.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteArea(result.areaId, parseInt(area.regionId));
                    } else {
                        modules.addresses.doModifyArea(areaId, parseInt(result.regionId), result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area, parseInt(area.regionId), result.timezone);
                    }
                },
            });
        } else {
            error(i18n("addresses.areaNotFound"));
        }
    },

    modifyCity: function (cityId) {
        let city = false;

        for (let i in modules.addresses.meta.cities) {
            if (modules.addresses.meta.cities[i].cityId == cityId) {
                city = modules.addresses.meta.cities[i];
                break;
            }
        }

        let regions = [];

        regions.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.regions) {
            regions.push({
                id: modules.addresses.meta.regions[i].regionId,
                text: modules.addresses.meta.regions[i].regionWithType,
            });
        }

        let areas = [];

        areas.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.areas) {
            areas.push({
                id: modules.addresses.meta.areas[i].areaId,
                text: modules.addresses.meta.areas[i].areaWithType,
            });
        }

        if (city) {
            cardForm({
                title: i18n("addresses.editCity"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteCity"),
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
                        title: i18n("addresses.region"),
                        value: city.regionId,
                        options: regions,
                        select: (el, id, prefix) => {
                            $(`#${prefix}areaId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}areaId`).val());
                        },
                    },
                    {
                        id: "areaId",
                        type: "select2",
                        title: i18n("addresses.area"),
                        value: city.areaId,
                        options: areas,
                        select: (el, id, prefix) => {
                            $(`#${prefix}regionId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}regionId`).val());
                        },
                    },
                    {
                        id: "cityUuid",
                        type: "text",
                        title: i18n("addresses.cityUuid"),
                        placeholder: i18n("addresses.cityUuid"),
                        value: city.cityUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "cityWithType",
                        type: "text",
                        title: i18n("addresses.cityWithType"),
                        placeholder: i18n("addresses.cityWithType"),
                        validate: v => {
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
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: city.city,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: timezonesOptions(),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: city.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteCity(result.cityId, parseInt(city.regionId), parseInt(city.areaId));
                    } else {
                        modules.addresses.doModifyCity(cityId, parseInt(result.regionId), parseInt(result.areaId), result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city, parseInt(city.regionId), parseInt(city.areaId), result.timezone);
                    }
                },
            });
        } else {
            error(i18n("addresses.cityNotFound"));
        }
    },

    modifySettlement: function (settlementId) {
        let settlement = false;

        for (let i in modules.addresses.meta.settlements) {
            if (modules.addresses.meta.settlements[i].settlementId == settlementId) {
                settlement = modules.addresses.meta.settlements[i];
                break;
            }
        }

        let areas = [];

        areas.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.areas) {
            areas.push({
                id: modules.addresses.meta.areas[i].areaId,
                text: modules.addresses.meta.areas[i].areaWithType,
            });
        }

        let cities = [];

        cities.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.cities) {
            cities.push({
                id: modules.addresses.meta.cities[i].cityId,
                text: modules.addresses.meta.cities[i].cityWithType,
            });
        }

        if (settlement) {
            cardForm({
                title: i18n("addresses.editSettlement"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteSettlement"),
                size: "lg",
                fields: [
                    {
                        id: "settlementId",
                        type: "text",
                        title: i18n("addresses.settlementId"),
                        value: settlementId,
                        readonly: true,
                    },
                    {
                        id: "areaId",
                        type: "select2",
                        title: i18n("addresses.area"),
                        value: settlement.areaId,
                        options: areas,
                        select: (el, id, prefix) => {
                            $(`#${prefix}cityId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}cityId`).val());
                        },
                    },
                    {
                        id: "cityId",
                        type: "select2",
                        title: i18n("addresses.city"),
                        value: settlement.cityId,
                        options: cities,
                        select: (el, id, prefix) => {
                            $(`#${prefix}areaId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}areaId`).val());
                        },
                    },
                    {
                        id: "settlementUuid",
                        type: "text",
                        title: i18n("addresses.settlementUuid"),
                        placeholder: i18n("addresses.settlementUuid"),
                        value: settlement.settlementUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "settlementWithType",
                        type: "text",
                        title: i18n("addresses.settlementWithType"),
                        placeholder: i18n("addresses.settlementWithType"),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: settlement.settlementWithType,
                    },
                    {
                        id: "settlementType",
                        type: "text",
                        title: i18n("addresses.settlementType"),
                        placeholder: i18n("addresses.settlementType"),
                        value: settlement.settlementType,
                    },
                    {
                        id: "settlementTypeFull",
                        type: "text",
                        title: i18n("addresses.settlementTypeFull"),
                        placeholder: i18n("addresses.settlementTypeFull"),
                        value: settlement.settlementTypeFull,
                    },
                    {
                        id: "settlement",
                        type: "text",
                        title: i18n("addresses.settlement"),
                        placeholder: i18n("addresses.settlement"),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: settlement.settlement,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteSettlement(result.settlementId, parseInt(settlement.areaId), parseInt(settlement.cityId));
                    } else {
                        modules.addresses.doModifySettlement(settlementId, parseInt(result.areaId), parseInt(result.cityId), result.settlementUuid, result.settlementWithType, result.settlementType, result.settlementTypeFull, result.settlement, parseInt(settlement.areaId), parseInt(settlement.cityId));
                    }
                },
            });
        } else {
            error(i18n("addresses.settlementNotFound"));
        }
    },

    modifyStreet: function (streetId) {
        let street = false;

        for (let i in modules.addresses.meta.streets) {
            if (modules.addresses.meta.streets[i].streetId == streetId) {
                street = modules.addresses.meta.streets[i];
                break;
            }
        }

        let cities = [];

        cities.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.cities) {
            cities.push({
                id: modules.addresses.meta.cities[i].cityId,
                text: modules.addresses.meta.cities[i].cityWithType,
            });
        }

        let settlements = [];

        settlements.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.settlements) {
            settlements.push({
                id: modules.addresses.meta.settlements[i].settlementId,
                text: modules.addresses.meta.settlements[i].settlementWithType,
            });
        }

        if (street) {
            cardForm({
                title: i18n("addresses.editStreet"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteStreet"),
                size: "lg",
                fields: [
                    {
                        id: "streetId",
                        type: "text",
                        title: i18n("addresses.streetId"),
                        value: streetId,
                        readonly: true,
                    },
                    {
                        id: "cityId",
                        type: "select2",
                        title: i18n("addresses.city"),
                        value: street.cityId,
                        options: cities,
                        select: (el, id, prefix) => {
                            $(`#${prefix}settlementId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}settlementId`).val());
                        },
                    },
                    {
                        id: "settlementId",
                        type: "select2",
                        title: i18n("addresses.settlement"),
                        value: street.settlementId,
                        options: settlements,
                        select: (el, id, prefix) => {
                            $(`#${prefix}cityId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}cityId`).val());
                        },
                    },
                    {
                        id: "streetUuid",
                        type: "text",
                        title: i18n("addresses.streetUuid"),
                        placeholder: i18n("addresses.streetUuid"),
                        value: street.streetUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "streetWithType",
                        type: "text",
                        title: i18n("addresses.streetWithType"),
                        placeholder: i18n("addresses.streetWithType"),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: street.streetWithType,
                    },
                    {
                        id: "streetType",
                        type: "text",
                        title: i18n("addresses.streetType"),
                        placeholder: i18n("addresses.streetType"),
                        value: street.streetType,
                    },
                    {
                        id: "streetTypeFull",
                        type: "text",
                        title: i18n("addresses.streetTypeFull"),
                        placeholder: i18n("addresses.streetTypeFull"),
                        value: street.streetTypeFull,
                    },
                    {
                        id: "street",
                        type: "text",
                        title: i18n("addresses.street"),
                        placeholder: i18n("addresses.street"),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        value: street.street,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteStreet(streetId, parseInt(street.cityId), parseInt(street.settlementId));
                    } else {
                        modules.addresses.doModifyStreet(streetId, parseInt(result.cityId), parseInt(result.settlementId), result.streetUuid, result.streetWithType, result.streetType, result.streetTypeFull, result.street, parseInt(street.cityId), parseInt(street.settlementId));
                    }
                },
            });
        } else {
            error(i18n("addresses.streetNotFound"));
        }
    },

    modifyHouse: function (houseId) {
        let companies = [];

        function realModifyHouse() {
            let house = false;

            for (let i in modules.addresses.meta.houses) {
                if (modules.addresses.meta.houses[i].houseId == houseId) {
                    house = modules.addresses.meta.houses[i];
                    break;
                }
            }

            let settlements = [];

            settlements.push({
                id: "0",
                text: "-",
            });

            for (let i in modules.addresses.meta.settlements) {
                settlements.push({
                    id: modules.addresses.meta.settlements[i].settlementId,
                    text: modules.addresses.meta.settlements[i].settlementWithType,
                });
            }

            let streets = [];

            streets.push({
                id: "0",
                text: "-",
            });

            for (let i in modules.addresses.meta.streets) {
                streets.push({
                    id: modules.addresses.meta.streets[i].streetId,
                    text: modules.addresses.meta.streets[i].streetWithType,
                });
            }

            if (house) {
                cardForm({
                    title: i18n("addresses.editHouse"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    delete: i18n("addresses.deleteHouse"),
                    size: "lg",
                    fields: [
                        {
                            id: "houseId",
                            type: "text",
                            title: i18n("addresses.houseId"),
                            value: houseId,
                            readonly: true,
                        },
                        {
                            id: "settlementId",
                            type: "select2",
                            title: i18n("addresses.settlement"),
                            value: house.settlementId,
                            options: settlements,
                            select: (el, id, prefix) => {
                                $(`#${prefix}streetId`).val("0").trigger("change");
                            },
                            validate: (v, prefix) => {
                                return !!parseInt(v) || !!parseInt($(`#${prefix}streetId`).val());
                            },
                        },
                        {
                            id: "streetId",
                            type: "select2",
                            title: i18n("addresses.street"),
                            value: house.streetId,
                            options: streets,
                            select: (el, id, prefix) => {
                                $(`#${prefix}settlementId`).val("0").trigger("change");
                            },
                            validate: (v, prefix) => {
                                return !!parseInt(v) || !!parseInt($(`#${prefix}settlementId`).val());
                            },
                        },
                        {
                            id: "houseUuid",
                            type: "text",
                            title: i18n("addresses.houseUuid"),
                            placeholder: i18n("addresses.houseUuid"),
                            value: house.houseUuid,
                            validate: v => {
                                return !!v;
                            },
                        },
                        {
                            id: "houseType",
                            type: "text",
                            title: i18n("addresses.houseType"),
                            placeholder: i18n("addresses.houseType"),
                            value: house.houseType,
                        },
                        {
                            id: "houseTypeFull",
                            type: "text",
                            title: i18n("addresses.houseTypeFull"),
                            placeholder: i18n("addresses.houseTypeFull"),
                            value: house.houseTypeFull,
                        },
                        {
                            id: "houseFull",
                            type: "text",
                            title: i18n("addresses.houseFull"),
                            placeholder: i18n("addresses.houseFull"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            value: house.houseFull,
                        },
                        {
                            id: "house",
                            type: "text",
                            title: i18n("addresses.house"),
                            placeholder: i18n("addresses.house"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            value: house.house,
                        },
                        {
                            id: "companyId",
                            hidden: !companies.length,
                            type: "select2",
                            title: i18n("companies.company"),
                            options: companies,
                            value: house.companyId,
                        },
                    ],
                    callback: function (result) {
                        if (result.delete === "yes") {
                            modules.addresses.deleteHouse(houseId, parseInt(house.settlementId), parseInt(house.streetId));
                        } else {
                            if (!companies.length) {
                                result.companyId = house.companyId;
                            }
                            modules.addresses.doModifyHouse(houseId, parseInt(result.settlementId), parseInt(result.streetId), result.houseUuid, result.houseType, result.houseTypeFull, result.houseFull, result.house, parseInt(house.settlementId), parseInt(house.streetId), parseInt(result.companyId));
                        }
                    },
                });
            } else {
                error(i18n("addresses.houseNotFound"));
            }
        }

        if (AVAIL("companies", "companies") && modules.companies) {
            GET("companies", "companies", false, true).
            fail(FAIL).
            done(result => {
                companies.push({
                    id: "0",
                    text: "-",
                });

                for (let i in result.companies) {
                    companies.push({
                        id: result.companies[i].companyId,
                        text: "[" + result.companies[i].uid + "] " + i18n("companies.type" + result.companies[i].type) + " " + result.companies[i].name,
                    });
                }

                realModifyHouse();
            }).
            always(loadingDone);
        } else {
            realModifyHouse();
        }
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
                    validate: v => {
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
                    validate: v => {
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: timezonesOptions(),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddRegion(result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region, result.timezone);
            },
        });
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
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "areaWithType",
                    type: "text",
                    title: i18n("addresses.areaWithType"),
                    placeholder: i18n("addresses.areaWithType"),
                    validate: v => {
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: timezonesOptions(),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddArea(regionId, result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area, result.timezone);
            },
        });
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
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "cityWithType",
                    type: "text",
                    title: i18n("addresses.cityWithType"),
                    placeholder: i18n("addresses.cityWithType"),
                    validate: v => {
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: timezonesOptions(),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddCity(regionId, areaId, result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city, result.timezone);
            },
        });
    },

    addSettlement: function (areaId, cityId) {
        cardForm({
            title: i18n("addresses.addSettlement"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "settlementUuid",
                    type: "text",
                    title: i18n("addresses.settlementUuid"),
                    placeholder: i18n("addresses.settlementUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}settlementUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "settlementWithType",
                    type: "text",
                    title: i18n("addresses.settlementWithType"),
                    placeholder: i18n("addresses.settlementWithType"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "settlementType",
                    type: "text",
                    title: i18n("addresses.settlementType"),
                    placeholder: i18n("addresses.settlementType"),
                },
                {
                    id: "settlementTypeFull",
                    type: "text",
                    title: i18n("addresses.settlementTypeFull"),
                    placeholder: i18n("addresses.settlementTypeFull"),
                },
                {
                    id: "settlement",
                    type: "text",
                    title: i18n("addresses.settlement"),
                    placeholder: i18n("addresses.settlement"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddSettlement(areaId, cityId, result.settlementUuid, result.settlementWithType, result.settlementType, result.settlementTypeFull, result.settlement);
            },
        });
    },

    addStreet: function (cityId, settlementId) {
        cardForm({
            title: i18n("addresses.addStreet"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "streetUuid",
                    type: "text",
                    title: i18n("addresses.streetUuid"),
                    placeholder: i18n("addresses.streetUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}streetUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "streetWithType",
                    type: "text",
                    title: i18n("addresses.streetWithType"),
                    placeholder: i18n("addresses.streetWithType"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "streetType",
                    type: "text",
                    title: i18n("addresses.streetType"),
                    placeholder: i18n("addresses.streetType"),
                },
                {
                    id: "streetTypeFull",
                    type: "text",
                    title: i18n("addresses.streetTypeFull"),
                    placeholder: i18n("addresses.streetTypeFull"),
                },
                {
                    id: "street",
                    type: "text",
                    title: i18n("addresses.street"),
                    placeholder: i18n("addresses.street"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddStreet(cityId, settlementId, result.streetUuid, result.streetWithType, result.streetType, result.streetTypeFull, result.street);
            },
        });
    },

    addHouse: function (settlementId, streetId) {
        let companies = [];

        function realAddHouse() {
            cardForm({
                title: i18n("addresses.addHouse"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                size: "lg",
                fields: [
                    {
                        id: "houseUuid",
                        type: "text",
                        title: i18n("addresses.houseUuid"),
                        placeholder: i18n("addresses.houseUuid"),
                        button: {
                            class: "fas fa-magic",
                            click: prefix => {
                                $(`#${prefix}houseUuid`).val(guid());
                            },
                        },
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "houseType",
                        type: "text",
                        title: i18n("addresses.houseType"),
                        placeholder: i18n("addresses.houseType"),
                    },
                    {
                        id: "houseTypeFull",
                        type: "text",
                        title: i18n("addresses.houseTypeFull"),
                        placeholder: i18n("addresses.houseTypeFull"),
                    },
                    {
                        id: "houseFull",
                        type: "text",
                        title: i18n("addresses.houseFull"),
                        placeholder: i18n("addresses.houseFull"),
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "house",
                        type: "text",
                        title: i18n("addresses.house"),
                        placeholder: i18n("addresses.house"),
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "companyId",
                        hidden: companies.length <= 1,
                        type: "select2",
                        title: i18n("companies.company"),
                        options: companies,
                    },
                ],
                callback: function (result) {
                    modules.addresses.doAddHouse(settlementId, streetId, result.houseUuid, result.houseType, result.houseTypeFull, result.houseFull, result.house, (companies.length > 1 && result.companyId) ? result.companyId : "0");
                },
            });
        }

        if (AVAIL("companies", "companies") && modules.companies) {
            GET("companies", "companies", false, true).
            fail(FAIL).
            done(result => {
                companies.push({
                    id: "0",
                    text: "-",
                });

                for (let i in result.companies) {
                    companies.push({
                        id: result.companies[i].companyId,
                        text: "[" + result.companies[i].uid + "] " + i18n("companies.type" + result.companies[i].type) + " " + result.companies[i].name,
                    });
                }

                realAddHouse();
            }).
            always(loadingDone);
        } else {
            realAddHouse();
        }
    },

    renderCities: function (target, regionId, areaId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.cities"),
                button: {
                    caption: i18n("addresses.addCity"),
                    click: () => {
                        modules.addresses.addCity(regionId, areaId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyCity,
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

                for (let i in modules.addresses.meta.cities) {
                    if ((regionId && modules.addresses.meta.cities[i].regionId == regionId && !modules.addresses.meta.cities[i].areaId) || (areaId && modules.addresses.meta.cities[i].areaId == areaId && !modules.addresses.meta.cities[i].regionId)) {
                        rows.push({
                            uid: modules.addresses.meta.cities[i].cityId,
                            cols: [
                                {
                                    data: modules.addresses.meta.cities[i].cityId,
                                },
                                {
                                    data: modules.addresses.meta.cities[i].cityWithType,
                                    nowrap: true,
                                    click: "#addresses&show=city&cityId=" + modules.addresses.meta.cities[i].cityId,
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderSettlements: function (target, areaId, cityId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.settlements"),
                button: {
                    caption: i18n("addresses.addSettlement"),
                    click: () => {
                        modules.addresses.addSettlement(areaId, cityId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifySettlement,
            columns: [
                {
                    title: i18n("addresses.settlementId"),
                },
                {
                    title: i18n("addresses.settlement"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.settlements) {
                    if ((areaId && modules.addresses.meta.settlements[i].areaId == areaId && !modules.addresses.meta.settlements[i].cityId) || (cityId && modules.addresses.meta.settlements[i].cityId == cityId && !modules.addresses.meta.settlements[i].areaId)) {
                        rows.push({
                            uid: modules.addresses.meta.settlements[i].settlementId,
                            cols: [
                                {
                                    data: modules.addresses.meta.settlements[i].settlementId,
                                },
                                {
                                    data: modules.addresses.meta.settlements[i].settlementWithType,
                                    nowrap: true,
                                    click: "#addresses&show=settlement&settlementId=" + modules.addresses.meta.settlements[i].settlementId,
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderStreets: function (target, cityId, settlementId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.streets"),
                button: {
                    caption: i18n("addresses.addStreet"),
                    click: () => {
                        modules.addresses.addStreet(cityId, settlementId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyStreet,
            columns: [
                {
                    title: i18n("addresses.streetId"),
                },
                {
                    title: i18n("addresses.street"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.streets) {
                    if ((cityId && modules.addresses.meta.streets[i].cityId == cityId && !modules.addresses.meta.streets[i].settlementId) || (settlementId && modules.addresses.meta.streets[i].settlementId == settlementId && !modules.addresses.meta.streets[i].cityId)) {
                        rows.push({
                            uid: modules.addresses.meta.streets[i].streetId,
                            cols: [
                                {
                                    data: modules.addresses.meta.streets[i].streetId,
                                },
                                {
                                    data: modules.addresses.meta.streets[i].streetWithType,
                                    nowrap: true,
                                    click: "#addresses&show=street&streetId=" + modules.addresses.meta.streets[i].streetId,
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderHouses: function (target, settlementId, streetId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.houses"),
                button: {
                    caption: i18n("addresses.addHouse"),
                    click: () => {
                        modules.addresses.addHouse(settlementId, streetId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyHouse,
            columns: [
                {
                    title: i18n("addresses.houseId"),
                },
                {
                    title: i18n("addresses.houseFull"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];
                let houses = {};

                for (let i in modules.addresses.meta.houses) {
                    if ((settlementId && modules.addresses.meta.houses[i].settlementId == settlementId && !modules.addresses.meta.houses[i].streetId) || (streetId && modules.addresses.meta.houses[i].streetId == streetId && !modules.addresses.meta.houses[i].settlementId)) {
                        houses[modules.addresses.meta.houses[i].houseId] = modules.addresses.meta.houses[i].houseFull;
                        rows.push({
                            uid: modules.addresses.meta.houses[i].houseId,
                            cols: [
                                {
                                    data: modules.addresses.meta.houses[i].houseId,
                                },
                                {
                                    data: modules.addresses.meta.houses[i].houseFull,
                                    nowrap: true,
                                    click: "#addresses.houses&houseId=" + modules.addresses.meta.houses[i].houseId,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-key",
                                        title: i18n("addresses.objectKeys", i18n("addresses.keysType4")),
                                        click: houseId => {
                                            window.location.href = "?#addresses.keys&query=" + houseId + "&by=4&backStr=" + encodeURIComponent(houses[houseId]) + "&back=" + encodeURIComponent(hashParse("hash"));
                                        },
                                    },
                                ]
                            },
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderRegions: function () {
        loadingStart();
        QUERY("addresses", "addresses", {
            include: "regions",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.regions"),
                    button: {
                        caption: i18n("addresses.addRegion"),
                        click: modules.addresses.addRegion,
                    },
                    filter: true,
                },
                edit: modules.addresses.modifyRegion,
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

                    for (let i in modules.addresses.meta.regions) {
                        rows.push({
                            uid: modules.addresses.meta.regions[i].regionId,
                            cols: [
                                {
                                    data: modules.addresses.meta.regions[i].regionId,
                                },
                                {
                                    data: modules.addresses.meta.regions[i].regionWithType,
                                    nowrap: true,
                                    click: "#addresses&show=region&regionId=" + modules.addresses.meta.regions[i].regionId,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderRegion: function (regionId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            regionId: regionId,
            include: "regions,areas,cities",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let region = false;

            for (let i in modules.addresses.meta.regions) {
                if (modules.addresses.meta.regions[i].regionId == regionId) {
                    region = modules.addresses.meta.regions[i];
                    break;
                }
            }
            if (!region) {
                page404();
                return;
            }

            subTop(modules.addresses.path("region", regionId));

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.areas"),
                    button: {
                        caption: i18n("addresses.addArea"),
                        click: () => {
                            modules.addresses.addArea(regionId);
                        },
                    },
                    filter: true,
                },
                edit: modules.addresses.modifyArea,
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

                    for (let i in modules.addresses.meta.areas) {
                        if (modules.addresses.meta.areas[i].regionId == regionId) {
                            rows.push({
                                uid: modules.addresses.meta.areas[i].areaId,
                                cols: [
                                    {
                                        data: modules.addresses.meta.areas[i].areaId,
                                    },
                                    {
                                        data: modules.addresses.meta.areas[i].areaWithType,
                                        nowrap: true,
                                        click: "#addresses&show=area&areaId=" + modules.addresses.meta.areas[i].areaId,
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            });
            modules.addresses.renderCities("#altForm", regionId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderArea: function (areaId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            areaId: areaId,
            include: "regions,areas,cities,settlements",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let area = false;

            for (let i in modules.addresses.meta.areas) {
                if (modules.addresses.meta.areas[i].areaId == areaId) {
                    area = modules.addresses.meta.areas[i];
                    break;
                }
            }

            if (!area) {
                page404();
                return;
            }

            subTop(modules.addresses.path("area", areaId));

            modules.addresses.renderCities("#mainForm", false, areaId);
            modules.addresses.renderSettlements("#altForm", areaId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderCity: function (cityId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            cityId: cityId,
            include: "regions,areas,cities,streets,settlements",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.cities) {
                if (modules.addresses.meta.cities[i].cityId == cityId) {
                    f = true;
                    break;
                }
            }
            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("city", cityId));

            modules.addresses.renderStreets("#mainForm", cityId, false);
            modules.addresses.renderSettlements("#altForm", false, cityId);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderSettlement: function (settlementId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            settlementId: settlementId,
            include: "regions,areas,cities,settlements,streets,houses",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.settlements) {
                if (modules.addresses.meta.settlements[i].settlementId == settlementId) {
                    f = true;
                    break;
                }
            }
            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("settlement", settlementId));

            modules.addresses.renderStreets("#mainForm", false, settlementId);
            modules.addresses.renderHouses("#altForm", settlementId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderStreet: function (streetId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            streetId: streetId,
            include: "regions,areas,cities,settlements,streets,houses",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.streets) {
                if (modules.addresses.meta.streets[i].streetId == streetId) {
                    f = true;
                }
            }

            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("street", streetId));

            modules.addresses.renderHouses("#mainForm", false, streetId);

            loadingDone();
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    topMenu: function (wizards) {
        let top = '';

        if (AVAIL("geo", "suggestions")) {
            top += `
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="javascript:void(0)" class="addHouseMagic nav-link nav-item-back-hover text-dark">${i18n("addresses.addHouse")}</a>
                </li>
            `;
        }

        if (wizards) {
            for (let i in wizards) {
                top += `
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="javascript:void(0)" class="houseWizard${i} nav-link nav-item-back-hover text-dark">${wizards[i].title}</a>
                    </li>
                `;
            }
        }

        $("#leftTopDynamic").html(top);
        $(".addHouseMagic").off("click").on("click", modules.addresses.houses.houseMagic);

        if (wizards) {
            for (let i in wizards) {
                $(".houseWizard" + i).off("click").on("click", wizards[i].click);
            }
        }
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.addresses");

        if (!params.show) {
            params.show = "regions";
        }

        modules.addresses.topMenu();

        switch (params.show) {
            case "region":
                modules.addresses.renderRegion(params.regionId);
                break;
            case "area":
                modules.addresses.renderArea(params.areaId);
                break;
            case "city":
                modules.addresses.renderCity(params.cityId);
                break;
            case "settlement":
                modules.addresses.renderSettlement(params.settlementId);
                break;
            case "street":
                modules.addresses.renderStreet(params.streetId);
                break;
            case "regions":
                subTop();
                modules.addresses.renderRegions();
                break;
            default:
                page404();
                break;
        }
    },
}).init();