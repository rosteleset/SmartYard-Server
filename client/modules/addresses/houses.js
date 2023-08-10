({
    init: function () {
        moduleLoaded("addresses.houses", this);
    },

    houseId: 0,
    settlementId: 0,
    streetId: 0,

    houseMagic: function () {
        cardForm({
            title: i18n("addresses.address"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            apply: i18n("add"),
            fields: [
                {
                    id: "address",
                    type: "select2",
                    title: i18n("addresses.address"),
                    placeholder: i18n("addresses.address"),
                    ajax: {
                        delay: 1000,
                        transport: function (params, success) {
                            if (params.data.term) {
                                QUERY("geo", "suggestions", {
                                    search: params.data.term,
                                }).
                                then(success).
                                fail(response => {
                                    FAIL(response);
                                    success({
                                        suggestions: [],
                                    });
                                });
                            } else {
                                success({
                                    suggestions: [],
                                });
                            }
                        },
                        processResults: function (data) {
                            let suggestions = [];
                            for (let i in data.suggestions) {
                                if (parseInt(data.suggestions[i].data.fias_level) === 8 || (parseInt(data.suggestions[i].data.fias_level) === -1 && data.suggestions[i].data.house)) {
                                    suggestions.push({
                                        id: data.suggestions[i].data.house_fias_id,
                                        text: data.suggestions[i].value,
                                    });
                                }
                            }
                            return {
                                results: suggestions,
                            };
                        },
                    },
                    validate: v => {
                        return !!v;
                    }
                },
            ],
            callback: function (result) {
                if (result && result.address) {
                    loadingStart();
                    POST("addresses", "house", false, {
                        magic: result.address,
                    }).
                    done(result => {
                        GET("addresses", "house", result.houseId).
                        done(result => {
                            message(i18n("addresses.houseWasAdded"));
                            if (result && result.house && (result.house.streetId || result.house.settlementId)) {
                                let [ route, params, hash ] = hashParse();
                                if (result.house.streetId) {
                                    if (route == "addresses" && params["show"] == "street" && params["streetId"] == result.house.streetId) {
                                        modules.addresses.renderStreet(result.house.streetId);
                                    } else {
                                        location.href = "?#addresses&show=street&streetId=" + result.house.streetId;
                                    }
                                } else {
                                    if (route == "addresses" && params["show"] == "settlement" && params["streetId"] == result.house.settlementId) {
                                        modules.addresses.renderSettlement(result.house.settlementId);
                                    } else {
                                        location.href = "?#addresses&show=settlement&settlementId=" + result.house.settlementId;
                                    }
                                }
                            } else {
                                error(i18n("errors.unknown"));
                                loadingDone();
                            }
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                }
            },
        }).show();
    },

    cmses: function (domophoneId, selected) {
        let c = [];

        c.push({
            id: "0",
            text: i18n("no"),
        });

        for (let id in modules.addresses.houses.meta.cmses) {
            if (domophoneId && modules.addresses.houses.meta.domophoneModels[domophoneId] && modules.addresses.houses.meta.domophoneModels[domophoneId].cmses.indexOf(id.split(".json")[0]) >= 0) {
                c.push({
                    id: id,
                    text: modules.addresses.houses.meta.cmses[id].title,
                    selected: selected === id,
                })
            }
        }

        return c;
    },

    outputs: function (domophoneModel, selected) {
        let o = [];

        for (let i = 0; i < 32; i++) {
            if (domophoneModel && modules.addresses.houses.meta.domophoneModels[domophoneModel] && i < parseInt(modules.addresses.houses.meta.domophoneModels[domophoneModel].outputs)) {
                o.push({
                    id: i.toString(),
                    text: i?i18n("addresses.domophoneOutputSecondary", i):i18n("addresses.domophoneOutputPrimary"),
                    selected: parseInt(selected) === i,
                });
            }
        }

        return o;
    },

    domophoneIdSelect: (el, id, prefix) => {
        $(`#${prefix}cms`).html("").select2({
            data: modules.addresses.houses.cmses(modules.addresses.houses.meta.domophoneModelsById[el.val()]),
            language: lang["_code"],
        });

        let h = "";

        let o = modules.addresses.houses.outputs(modules.addresses.houses.meta.domophoneModelsById[el.val()]);
        for (let i in o) {
            h += `<option value="${o[i].id}" ${o[i].selected?"selected":""}>${o[i].text}</option>`;
        }

        $("#" + prefix + "domophoneOutput").html(h);

        modules.addresses.houses.outputsSelect(el, id, prefix);
    },

    outputsSelect: function (el, id, prefix) {
        if (parseInt($("#" + prefix + "domophoneOutput").val()) > 0) {
            $("#" + prefix + "cms").parent().parent().parent().hide();
        } else {
            $("#" + prefix + "cms").parent().parent().parent().show();
        }

        modules.addresses.houses.cmsSelect(el, id, prefix);
    },
    
    cmsSelect: (el, id, prefix) => {
        if (parseInt($("#" + prefix + "cms").val()) !== 0 && $("#" + prefix + "cms:visible").length) {
            $("#" + prefix + "cmsType").parent().parent().parent().show();
            $("#" + prefix + "cmsLevels").parent().parent().show();
            $("#" + prefix + "shared").parent().parent().parent().hide();
        } else {
            $("#" + prefix + "cmsType").parent().parent().parent().hide();
            $("#" + prefix + "cmsLevels").parent().parent().hide();
            if (parseInt($("#" + prefix + "domophoneOutput").val())) {
                $("#" + prefix + "shared").parent().parent().parent().hide();
            } else {
                $("#" + prefix + "shared").parent().parent().parent().show();
            }
        }

        modules.addresses.houses.sharedSelect(el, id, prefix, true);
    },

    sharedSelect: (el, id, prefix, cascade) => {
        if (parseInt($("#" + prefix + "shared").val()) && $("#" + prefix + "shared:visible").length) {
            $("#" + prefix + "cms").parent().parent().parent().hide();
            $("#" + prefix + "prefix").parent().parent().show();
        } else {
            if (parseInt($("#" + prefix + "domophoneOutput").val())) {
                $("#" + prefix + "cms").parent().parent().parent().hide();
            } else {
                $("#" + prefix + "cms").parent().parent().parent().show();
            }
            $("#" + prefix + "prefix").parent().parent().hide();
        }

        if (!cascade) {
            modules.addresses.houses.cmsSelect(el, id, prefix);
        }
    },

    doAddEntrance: function (house) {
        loadingStart();
        POST("houses", "entrance", false, house).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.entranceWasAdded"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(house.houseId);
        });
    },

    doCreateEntrance: function (entrance) {
        loadingStart();
        POST("houses", "entrance", false, entrance).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.entranceWasCreated"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(entrance.houseId);
        });
    },

    doAddFlat: function (flat) {
        loadingStart();
        POST("houses", "flat", false, flat).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.flatWasAdded"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(flat.houseId);
        });
    },

    doAddCamera: function (camera) {
        loadingStart();
        POST("houses", "cameras", false, camera).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasAdded"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(camera.houseId);
        });
    },

    doModifyEntrance: function (entrance) {
        loadingStart();
        PUT("houses", "entrance", entrance.entranceId, entrance).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.entranceWasChanged"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(entrance.houseId);
        });
    },

    doModifyFlat: function (flat) {
        loadingStart();
        PUT("houses", "flat", flat.flatId, flat).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.flatWasChanged"));
        }).
        always(() => {
            if (flat.houseId) {
                modules.addresses.houses.renderHouse(flat.houseId);
            }
        });
    },

    doDeleteEntrance: function (entranceId, complete, houseId) {
        loadingStart();
        if (complete) {
            DELETE("houses", "entrance", entranceId).
            fail(FAIL).
            done(() => {
                message(i18n("addresses.entranceWasDeleted"));
            }).
            always(() => {
                modules.addresses.houses.renderHouse(houseId);
            });
        } else {
            DELETE("houses", "entrance", entranceId, {
                houseId
            }).
            fail(FAIL).
            done(() => {
                message(i18n("addresses.entranceWasDeleted"));
            }).
            always(() => {
                modules.addresses.houses.renderHouse(houseId);
            });
        }
    },

    doDeleteFlat: function (flatId, houseId) {
        loadingStart();
        DELETE("houses", "flat", flatId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.flatWasDeleted"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(houseId);
        });
    },

    doDeleteCamera: function (cameraId, houseId) {
        loadingStart();
        DELETE("houses", "cameras", false, { from: "house", cameraId, houseId }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cameraWasDeleted"));
        }).
        always(() => {
            modules.addresses.houses.renderHouse(houseId);
        });
    },

    addEntrance: function (houseId) {
        mYesNo(i18n("addresses.useExistingEntranceQuestion"), i18n("addresses.addEntrance"), () => {
            loadingStart();
            GET("cameras", "cameras").
            done(response => {
                modules.addresses.houses.meta.cameras = response.cameras;

                let cameras = [];

                cameras.push({
                    id: "0",
                    text: i18n("no"),
                })

                for (let i in response.cameras.cameras) {
                    let url;
                    try {
                        url = new URL(response.cameras.cameras[i].url);
                    } catch (e) {
                        url = {
                            host: response.cameras.cameras[i].url,
                        }
                    }
                    let comment = response.cameras.cameras[i].comment;
                    cameras.push({
                        id: response.cameras.cameras[i].cameraId,
                        text: comment?(comment + ' [' + url.host + ']'):url.host,
                    })
                }

                GET("houses", "domophones").
                done(response => {
                    modules.addresses.houses.meta.domophones = response.domophones;
                    modules.addresses.houses.meta.domophoneModelsById = {};

                    let domophones = [];
                    let first = false;

                    for (let i in response.domophones.domophones) {
                        if (first === false) first = response.domophones.domophones[i].domophoneId;
                        modules.addresses.houses.meta.domophoneModelsById[response.domophones.domophones[i].domophoneId] = response.domophones.domophones[i].model;
                        let url;
                        try {
                            url = new URL(response.domophones.domophones[i].url);
                        } catch (e) {
                            url = {
                                host: response.domophones.domophones[i].url,
                            }
                        }
                        let comment = response.domophones.domophones[i].comment;
                        domophones.push({
                            id: response.domophones.domophones[i].domophoneId,
                            text: comment?(comment + ' [' + url.host + ']'):url.host,
                        })
                    }

                    cardForm({
                        title: i18n("addresses.addEntrance"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        apply: i18n("add"),
                        size: "lg",
                        fields: [
                            {
                                id: "entranceType",
                                type: "select",
                                title: i18n("addresses.entranceType"),
                                options: [
                                    {
                                        id: "entrance",
                                        text: i18n("addresses.entranceTypeEntranceFull"),
                                    },
                                    {
                                        id: "wicket",
                                        text: i18n("addresses.entranceTypeWicketFull"),
                                    },
                                    {
                                        id: "gate",
                                        text: i18n("addresses.entranceTypeGateFull"),
                                    },
                                    {
                                        id: "barrier",
                                        text: i18n("addresses.entranceTypeBarrierFull"),
                                    }
                                ]
                            },
                            {
                                id: "entrance",
                                type: "text",
                                title: i18n("addresses.entrance"),
                                placeholder: i18n("addresses.entrance"),
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                }
                            },
                            {
                                id: "geo",
                                type: "text",
                                title: i18n("addresses.geo"),
                                placeholder: "0.0,0.0",
                                hint: i18n("addresses.lat") + "," + i18n("addresses.lon").toLowerCase(),
                                value: "0.0,0.0",
                                validate: v => {
                                    const regex = new RegExp('^[+-]?((\\d+\\.?\\d*)|(\\.\\d+)),[+-]?((\\d+\\.?\\d*)|(\\.\\d+))$', 'gm');

                                    return regex.exec(v) !== null;
                                },
                            },
                            {
                                id: "callerId",
                                type: "text",
                                title: i18n("addresses.callerId"),
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                },
                            },
                            {
                                id: "cameraId",
                                type: "select2",
                                title: i18n("addresses.cameraId"),
                                options: cameras,
                            },
                            {
                                id: "domophoneId",
                                type: "select2",
                                title: i18n("addresses.domophone"),
                                options: domophones,
                                validate: v => {
                                    return parseInt(v) > 0;
                                },
                                select: modules.addresses.houses.domophoneIdSelect,
                            },
                            {
                                id: "domophoneOutput",
                                type: "select",
                                title: i18n("addresses.domophoneOutput"),
                                placeholder: i18n("addresses.domophoneOutput"),
                                options: modules.addresses.houses.outputs(modules.addresses.houses.meta.domophoneModelsById[first]),
                                select: modules.addresses.houses.outputsSelect,
                            },
                            {
                                id: "cms",
                                type: "select2",
                                title: i18n("addresses.cms"),
                                placeholder: i18n("addresses.cms"),
                                options: modules.addresses.houses.cmses(modules.addresses.houses.meta.domophoneModelsById[first]),
                                select: modules.addresses.houses.cmsSelect,
                            },
                            {
                                id: "cmsType",
                                type: "select",
                                title: i18n("addresses.cmsType"),
                                hidden: true,
                                options: [
                                    {
                                        id: "1",
                                        text: i18n("addresses.cmsA"),
                                    },
                                    {
                                        id: "2",
                                        text: i18n("addresses.cmsAV"),
                                    },
                                ]
                            },
                            {
                                id: "cmsLevels",
                                type: "text",
                                title: i18n("addresses.cmsLevels"),
                                placeholder: i18n("addresses.cmsLevelsOrder"),
                                hidden: true,
                            },
                            {
                                id: "shared",
                                type: "select",
                                title: i18n("addresses.shared"),
                                select: modules.addresses.houses.sharedSelect,
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
                                id: "plog",
                                type: "yesno",
                                title: i18n("addresses.plog"),
                            },
                            {
                                id: "prefix",
                                type: "text",
                                title: i18n("addresses.prefix"),
                                placeholder: i18n("addresses.prefix"),
                                value: "0",
                                hidden: true,
                                validate: (v, prefix) => {
                                    return !parseInt($("#" + prefix + "shared").val()) || parseInt(v) >= 1;
                                },
                            },
                        ],
                        callback: result => {
                            let g = result.geo.split(",");
                            result.lat = g[0];
                            result.lon = g[1];
                            if (parseInt(result.domophoneOutput) > 0) {
                                result.cms = 0;
                                result.shared = 0;
                            }
                            if (parseInt(result.cms) !== 0) {
                                result.shared = 0;
                            }
                            if (parseInt(result.shared) !== 0) {
                                result.cms = 0;
                            }
                            result.houseId = houseId;
                            modules.addresses.houses.doCreateEntrance(result);
                        },
                    });

                    loadingDone();
                }).
                fail(FAIL).
                fail(loadingDone);
            }).
            fail(FAIL).
            fail(loadingDone);
        }, () => {
            loadingStart();
            GET("houses", "sharedEntrances", houseId, true).
            done(response => {

                let entrances = [];

                entrances.push({
                    id: 0,
                    text: "нет",
                });

                for (let j in response.entrances) {
                    let house = "";

                    if (modules && modules.addresses && modules.addresses.meta && modules.addresses.meta.houses) {
                        for (let i in modules.addresses.meta.houses) {
                            if (modules.addresses.meta.houses[i].houseId == response.entrances[j].houseId) {
                                house = modules.addresses.meta.houses[i].houseFull;
                            }
                        }
                    }

                    if (!house) {
                        house = "#" + houseId;
                    }

                    entrances.push({
                        id: response.entrances[j].entranceId,
                        text: house + ", " + i18n("addresses.entranceType" + response.entrances[j].entranceType.substring(0, 1).toUpperCase() + response.entrances[j].entranceType.substring(1) + "Full").toLowerCase() + " " + response.entrances[j].entrance,
                    });
                }

                cardForm({
                    title: i18n("addresses.addEntrance"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("add"),
                    fields: [
                        {
                            id: "entranceId",
                            type: "select2",
                            title: i18n("addresses.entrance"),
                            options: entrances,
                            validate: v => {
                                return parseInt(v) > 0;
                            },
                        },
                        {
                            id: "prefix",
                            type: "text",
                            title: i18n("addresses.prefix"),
                            placeholder: i18n("addresses.prefix"),
                            value: "0",
                            validate: v => {
                                return parseInt(v) > 0;
                            },
                        },
                    ],
                    callback: result => {
                        if (parseInt(result.entranceId)) {
                            result.houseId = houseId;
                            modules.addresses.houses.doAddEntrance(result);
                        }
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }, i18n("addresses.addNewEntrance"), i18n("addresses.useExistingEntrance"));
    },

    addFlat: function (houseId) {
        let entrances = [];
        let prefx = md5(guid());

        for (let i in modules.addresses.houses.meta.entrances) {
            if (parseInt(modules.addresses.houses.meta.entrances[i].domophoneOutput) === 0 && parseInt(modules.addresses.houses.meta.entrances[i].shared) === 0) {
                let inputs = `<div class="row mt-2 ${prefx}" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" style="display: none;">`;
                inputs += `
                    <div class="col">
                        <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartment")}">
                    </div>
                `;
                if (modules.addresses.houses.meta.entrances[i].cms.toString() !== "0") {
                    inputs += `                        
                        <div class="col">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartmentLevels")}">
                        </div>
                    `;
                }
                inputs += `</div>`;
                entrances.push({
                    id: modules.addresses.houses.meta.entrances[i].entranceId,
                    text: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules.addresses.houses.meta.entrances[i].entrance + inputs,
                });
            } else {
                entrances.push({
                    id: modules.addresses.houses.meta.entrances[i].entranceId,
                    text: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules.addresses.houses.meta.entrances[i].entrance,
                });
            }
        }

        cardForm({
            title: i18n("addresses.addFlat"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "floor",
                    type: "text",
                    title: i18n("addresses.floor"),
                    placeholder: i18n("addresses.floor"),
                },
                {
                    id: "flat",
                    type: "text",
                    title: i18n("addresses.flat"),
                    placeholder: i18n("addresses.flat"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "code",
                    type: "text",
                    title: i18n("addresses.addCode"),
                    placeholder: i18n("addresses.addCode"),
                    value: md5(guid()),
                },
                {
                    id: "entrances",
                    type: "multiselect",
                    title: i18n("addresses.entrances"),
                    hidden: entrances.length <= 0,
                    options: entrances,
                },
                {
                    id: "manualBlock",
                    type: "select",
                    title: i18n("addresses.manualBlock"),
                    placeholder: i18n("addresses.manualBlock"),
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
                    id: "adminBlock",
                    type: "select",
                    title: i18n("addresses.adminBlock"),
                    placeholder: i18n("addresses.adminBlock"),
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
                    title: i18n("addresses.openCode"),
                    placeholder: i18n("addresses.openCode"),
                },
                {
                    id: "plog",
                    type: "select",
                    title: i18n("addresses.plog"),
                    placeholder: i18n("addresses.plog"),
                    options: [
                        {
                            id: "0",
                            text: i18n("addresses.plogNone"),
                        },
                        {
                            id: "1",
                            text: i18n("addresses.plogAll"),
                        },
                        {
                            id: "2",
                            text: i18n("addresses.plogOwner"),
                        },
                        {
                            id: "3",
                            text: i18n("addresses.adminDisabled"),
                        },
                    ],
                    value: 1,
                },
                {
                    id: "autoOpen",
                    type: "text",
                    title: i18n("addresses.autoOpen"),
                    placeholder: date("Y-m-d H:i"),
                },
                {
                    id: "whiteRabbit",
                    type: "select",
                    title: i18n("addresses.whiteRabbit"),
                    placeholder: i18n("addresses.whiteRabbit"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("addresses.1m"),
                        },
                        {
                            id: "2",
                            text: i18n("addresses.2m"),
                        },
                        {
                            id: "3",
                            text: i18n("addresses.3m"),
                        },
                        {
                            id: "5",
                            text: i18n("addresses.5m"),
                        },
                        {
                            id: "7",
                            text: i18n("addresses.7m"),
                        },
                        {
                            id: "10",
                            text: i18n("addresses.10m"),
                        },
                    ]
                },
                {
                    id: "sipEnabled",
                    type: "select",
                    title: i18n("addresses.sipEnabled"),
                    placeholder: i18n("addresses.sipEnabled"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("addresses.sip"),
                        },
                        {
                            id: "2",
                            text: i18n("addresses.webRtc"),
                        },
                    ],
                    select: (el, id, prefix) => {
                        if (parseInt(el.val()) > 0) {
                            $("#" + prefix + "sipPassword").parent().parent().parent().show();
                        } else {
                            $("#" + prefix + "sipPassword").parent().parent().parent().hide();
                        }
                    },
                },
                {
                    id: "sipPassword",
                    type: "text",
                    title: i18n("addresses.sipPassword"),
                    placeholder: i18n("addresses.sipPassword"),
                    hidden: true,
                    validate: (v, prefix) => {
                        if (parseInt($("#" + prefix + "sipEnabled").val())) {
                            return $.trim(v).length >= 8 && $.trim(v).length <= 16;
                        } else {
                            return $.trim(v).length === 0 || ($.trim(v).length >= 8 && $.trim(v).length <= 16);
                        }
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
                    if ($(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).length) {
                        apartmentsAndLevels[entrances[i].id] = {
                            apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                            apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                        }
                    }
                }
                result.houseId = houseId;
                result.apartmentsAndLevels = apartmentsAndLevels;
                modules.addresses.houses.doAddFlat(result);
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
        loadingStart();
        GET("cameras", "cameras").
        done(response => {
            modules.addresses.houses.meta.cameras = response.cameras;

            let cameras = [];

            cameras.push({
                id: "0",
                text: i18n("no"),
            });

            for (let i in response.cameras.cameras) {
                let url;
                try {
                    url = new URL(response.cameras.cameras[i].url);
                } catch (e) {
                    url = {
                        host: response.cameras.cameras[i].url,
                    }
                }
                let comment = response.cameras.cameras[i].comment;
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text: comment?(comment + ' [' + url.host + ']'):url.host,
                })
            }

            GET("houses", "domophones").
            done(response => {
                modules.addresses.houses.meta.domophones = response.domophones;
                modules.addresses.houses.meta.domophoneModelsById = {};

                let domophones = [];

                for (let i in response.domophones.domophones) {
                    modules.addresses.houses.meta.domophoneModelsById[response.domophones.domophones[i].domophoneId] = response.domophones.domophones[i].model;
                    let url;
                    try {
                        url = new URL(response.domophones.domophones[i].url);
                    } catch (e) {
                        url = {
                            host: response.domophones.domophones[i].url,
                        }
                    }
                    let comment = response.domophones.domophones[i].comment;
                    domophones.push({
                        id: response.domophones.domophones[i].domophoneId,
                        text: comment?(comment + ' [' + url.host + ']'):url.host,
                    })
                }

                let entrance = false;

                for (let i in modules.addresses.houses.meta.entrances) {
                    if (modules.addresses.houses.meta.entrances[i].entranceId == entranceId) {
                        entrance = modules.addresses.houses.meta.entrances[i];
                        break;
                    }
                }

                if (entrance) {
                    cardForm({
                        title: i18n("addresses.editEntrance"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        apply: i18n("edit"),
                        delete: i18n("addresses.deleteEntrance"),
                        size: "lg",
                        fields: [
                            {
                                id: "entranceId",
                                type: "text",
                                title: i18n("addresses.entranceId"),
                                value: entranceId,
                                readonly: true,
                            },
                            {
                                id: "entranceType",
                                type: "select",
                                title: i18n("addresses.entranceType"),
                                options: [
                                    {
                                        id: "entrance",
                                        text: i18n("addresses.entranceTypeEntranceFull"),
                                    },
                                    {
                                        id: "wicket",
                                        text: i18n("addresses.entranceTypeWicketFull"),
                                    },
                                    {
                                        id: "gate",
                                        text: i18n("addresses.entranceTypeGateFull"),
                                    },
                                    {
                                        id: "barrier",
                                        text: i18n("addresses.entranceTypeBarrierFull"),
                                    }
                                ],
                                value: entrance.entranceType,
                            },
                            {
                                id: "entrance",
                                type: "text",
                                title: i18n("addresses.entrance"),
                                placeholder: i18n("addresses.entrance"),
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                },
                                value: entrance.entrance,
                            },
                            {
                                id: "geo",
                                type: "text",
                                title: i18n("addresses.geo"),
                                placeholder: "0.0,0.0",
                                hint: i18n("addresses.lat") + "," + i18n("addresses.lon").toLowerCase(),
                                value: entrance.lat + "," + entrance.lon,
                                validate: v => {
                                    const regex = new RegExp('^[+-]?((\\d+\\.?\\d*)|(\\.\\d+)),[+-]?((\\d+\\.?\\d*)|(\\.\\d+))$', 'gm');

                                    return regex.exec(v) !== null;
                                },
                            },
                            {
                                id: "callerId",
                                type: "text",
                                title: i18n("addresses.callerId"),
                                value: entrance.callerId,
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                },
                            },
                            {
                                id: "cameraId",
                                type: "select2",
                                title: i18n("addresses.cameraId"),
                                value: entrance.cameraId,
                                options: cameras,
                            },
                            {
                                id: "domophoneId",
                                type: "select2",
                                title: i18n("addresses.domophone"),
                                value: entrance.domophoneId,
                                options: domophones,
                                select: modules.addresses.houses.domophoneIdSelect,
                            },
                            {
                                id: "domophoneOutput",
                                type: "select",
                                title: i18n("addresses.domophoneOutput"),
                                placeholder: i18n("addresses.domophoneOutput"),
                                options: modules.addresses.houses.outputs(modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId], entrance.domophoneOutput),
                                select: modules.addresses.houses.outputsSelect,
                            },
                            {
                                id: "cms",
                                type: "select2",
                                title: i18n("addresses.cms"),
                                placeholder: i18n("addresses.cms"),
                                options: modules.addresses.houses.cmses(modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId]),
                                hidden: parseInt(entrance.domophoneOutput) > 0,
                                value: entrance.cms,
                                select: modules.addresses.houses.cmsSelect,
                            },
                            {
                                id: "cmsType",
                                type: "select",
                                title: i18n("addresses.cmsType"),
                                value: entrance.cmsType,
                                hidden: parseInt(entrance.domophoneOutput) > 0 || parseInt(entrance.cms) === 0,
                                options: [
                                    {
                                        id: "1",
                                        text: i18n("addresses.cmsA"),
                                    },
                                    {
                                        id: "2",
                                        text: i18n("addresses.cmsAV"),
                                    },
                                ]
                            },
                            {
                                id: "cmsLevels",
                                type: "text",
                                title: i18n("addresses.cmsLevels"),
                                placeholder: i18n("addresses.cmsLevelsOrder"),
                                value: entrance.cmsLevels,
                                hidden: parseInt(entrance.domophoneOutput) > 0 || parseInt(entrance.cms) === 0,
                            },
                            {
                                id: "shared",
                                type: "select",
                                title: i18n("addresses.shared"),
                                hidden: parseInt(entrance.domophoneOutput) > 0 || parseInt(entrance.cms) !== 0,
                                value: entrance.shared.toString(),
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
                                select: (el, id, prefix) => {
                                    if (parseInt(el.val())) {
                                        $("#" + prefix + "prefix").parent().parent().show();
                                    } else {
                                        $("#" + prefix + "prefix").parent().parent().hide();
                                    }
                                },
                            },
                            {
                                id: "plog",
                                type: "select",
                                title: i18n("addresses.plog"),
                                value: entrance.plog,
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
                            },
                            {
                                id: "prefix",
                                type: "text",
                                title: i18n("addresses.prefix"),
                                placeholder: i18n("addresses.prefix"),
                                value: entrance.prefix?entrance.prefix.toString():"0",
                                hidden: !parseInt(entrance.shared) || parseInt(entrance.domophoneOutput) > 0 || parseInt(entrance.cms) !== 0,
                                validate: (v, prefix) => {
                                    return !parseInt($("#" + prefix + "shared").val()) || parseInt(v) >= 1;
                                },
                            },
                        ],
                        callback: result => {
                            if (result.delete === "yes") {
                                modules.addresses.houses.deleteEntrance(entranceId, parseInt(entrance.shared), houseId);
                            } else {
                                let g = result.geo.split(",");
                                result.lat = g[0];
                                result.lon = g[1];
                                if (parseInt(result.domophoneOutput) > 0) {
                                    result.cms = 0;
                                    result.shared = 0;
                                }
                                if (parseInt(result.cms) !== 0) {
                                    result.shared = 0;
                                }
                                if (parseInt(result.shared) !== 0) {
                                    result.cms = 0;
                                }
                                result.entranceId = entranceId;
                                result.houseId = houseId;
                                modules.addresses.houses.doModifyEntrance(result);
                            }
                        },
                    });
                } else {
                    error(i18n("addresses.entranceNotFound"));
                }
                loadingDone();
            }).
            fail(FAIL).
            fail(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    modifyFlat: function (flatId, houseId) {
        let flat = false;

        for (let i in modules.addresses.houses.meta.flats) {
            if (modules.addresses.houses.meta.flats[i].flatId == flatId) {
                flat = modules.addresses.houses.meta.flats[i];
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

            for (let i in modules.addresses.houses.meta.entrances) {
                if (parseInt(modules.addresses.houses.meta.entrances[i].domophoneOutput) === 0 && parseInt(modules.addresses.houses.meta.entrances[i].shared) === 0) {
                    let inputs = `<div class="row mt-2 ${prefx}" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" style="display: none;">`;
                    inputs += `
                        <div class="col">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartment")}" value="${entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId] ? entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId].apartment : ""}">
                        </div>
                    `;
                    if (modules.addresses.houses.meta.entrances[i].cms.toString() !== "0") {
                        inputs += `
                            <div class="col">
                                <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartmentLevels")}" value="${entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId] ? entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId].apartmentLevels : ""}">
                            </div>
                        `;
                    }
                    inputs += `</div>`;
                    entrances.push({
                        id: modules.addresses.houses.meta.entrances[i].entranceId,
                        text: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules.addresses.houses.meta.entrances[i].entrance + inputs,
                    });
                } else {
                    entrances.push({
                        id: modules.addresses.houses.meta.entrances[i].entranceId,
                        text: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules.addresses.houses.meta.entrances[i].entrance,
                    });
                }
            }

            cardForm({
                title: i18n("addresses.editFlat"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: houseId?i18n("addresses.deleteFlat"):false,
                apply: i18n("edit"),
                size: "lg",
                fields: [
                    {
                        id: "flatId",
                        type: "text",
                        title: i18n("addresses.flatId"),
                        value: flatId,
                        readonly: true,
                    },
                    {
                        id: "floor",
                        type: "text",
                        title: i18n("addresses.floor"),
                        placeholder: i18n("addresses.floor"),
                        value: flat.floor,
                    },
                    {
                        id: "flat",
                        type: "text",
                        title: i18n("addresses.flat"),
                        placeholder: i18n("addresses.flat"),
                        value: flat.flat,
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "code",
                        type: "text",
                        title: i18n("addresses.addCode"),
                        placeholder: i18n("addresses.addCode"),
                        value: flat.code,
                    },
                    {
                        id: "plog",
                        type: "select",
                        title: i18n("addresses.plog"),
                        placeholder: i18n("addresses.plog"),
                        options: [
                            {
                                id: "0",
                                text: i18n("addresses.plogNone"),
                            },
                            {
                                id: "1",
                                text: i18n("addresses.plogAll"),
                            },
                            {
                                id: "2",
                                text: i18n("addresses.plogOwner"),
                            },
                            {
                                id: "3",
                                text: i18n("addresses.adminDisabled"),
                            },
                        ],
                        value: flat.plog,
                    },
                    {
                        id: "entrances",
                        type: "multiselect",
                        title: i18n("addresses.entrances"),
                        hidden: entrances.length <= 0,
                        options: entrances,
                        value: entrances_selected,
                    },
                    {
                        id: "manualBlock",
                        type: "select",
                        title: i18n("addresses.manualBlock"),
                        placeholder: i18n("addresses.manualBlock"),
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
                        id: "adminBlock",
                        type: "select",
                        title: i18n("addresses.adminBlock"),
                        placeholder: i18n("addresses.adminBlock"),
                        value: flat.adminBlock,
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
                        title: i18n("addresses.openCode"),
                        placeholder: i18n("addresses.openCode"),
                        value: flat.openCode,
                    },
                    {
                        id: "autoOpen",
                        type: "text",
                        title: i18n("addresses.autoOpen"),
                        placeholder: date("Y-m-d H:i"),
                        value: date("Y-m-d H:i", flat.autoOpen),
                    },
                    {
                        id: "whiteRabbit",
                        type: "select",
                        title: i18n("addresses.whiteRabbit"),
                        placeholder: i18n("addresses.whiteRabbit"),
                        value: flat.whiteRabbit,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("addresses.1m"),
                            },
                            {
                                id: "2",
                                text: i18n("addresses.2m"),
                            },
                            {
                                id: "3",
                                text: i18n("addresses.3m"),
                            },
                            {
                                id: "5",
                                text: i18n("addresses.5m"),
                            },
                            {
                                id: "7",
                                text: i18n("addresses.7m"),
                            },
                            {
                                id: "10",
                                text: i18n("addresses.10m"),
                            },
                        ]
                    },
                    {
                        id: "cmsEnabled",
                        type: "select",
                        title: i18n("addresses.cmsEnabled"),
                        placeholder: i18n("addresses.cmsEnabled"),
                        value: flat.cmsEnabled,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("yes"),
                            },
                        ],
                    },
                    {
                        id: "sipEnabled",
                        type: "select",
                        title: i18n("addresses.sipEnabled"),
                        placeholder: i18n("addresses.sipEnabled"),
                        value: flat.sipEnabled,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("addresses.sip"),
                            },
                            {
                                id: "2",
                                text: i18n("addresses.webRtc"),
                            },
                        ],
                        select: (el, id, prefix) => {
                            if (parseInt(el.val()) > 0) {
                                $("#" + prefix + "sipPassword").parent().parent().parent().show();
                            } else {
                                $("#" + prefix + "sipPassword").parent().parent().parent().hide();
                            }
                        },
                    },
                    {
                        id: "sipPassword",
                        type: "text",
                        title: i18n("addresses.sipPassword"),
                        placeholder: i18n("addresses.sipPassword"),
                        value: flat.sipPassword,
                        hidden: !parseInt(flat.sipEnabled),
                        validate: (v, prefix) => {
                            if (parseInt($("#" + prefix + "sipEnabled").val())) {
                                return $.trim(v).length >= 8 && $.trim(v).length <= 16;
                            } else {
                                return $.trim(v).length === 0 || ($.trim(v).length >= 8 && $.trim(v).length <= 16);
                            }
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
                        if ($(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).length) {
                            apartmentsAndLevels[entrances[i].id] = {
                                apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                                apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                            }
                        }
                    }
                    if (result.delete === "yes") {
                        modules.addresses.houses.deleteFlat(flatId, houseId);
                    } else {
                        result.flatId = flatId;
                        result.apartmentsAndLevels = apartmentsAndLevels;
                        result.houseId = houseId;
                        modules.addresses.houses.doModifyFlat(result);
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
            error(i18n("addresses.flatNotFound"));
        }
    },

    deleteEntrance: function (entranceId, shared, houseId) {
        if (shared) {
            mYesNo(i18n("addresses.completelyDeleteEntrance", entranceId), i18n("addresses.deleteEntrance"), () => {
                modules.addresses.houses.doDeleteEntrance(entranceId, true, houseId);
            }, () => {
                modules.addresses.houses.doDeleteEntrance(entranceId, false, houseId);
            }, i18n("addresses.deleteEntranceCompletely"), i18n("addresses.deleteEntranceLink"));
        } else {
            mConfirm(i18n("addresses.confirmDeleteEntrance", entranceId), i18n("confirm"), `danger:${i18n("addresses.deleteEntrance")}`, () => {
                modules.addresses.houses.doDeleteEntrance(entranceId, true, houseId);
            });
        }
    },

    deleteFlat: function (flatId, houseId) {
        mConfirm(i18n("addresses.confirmDeleteFlat", flatId), i18n("confirm"), `danger:${i18n("addresses.deleteFlat")}`, () => {
            modules.addresses.houses.doDeleteFlat(flatId, houseId);
        });
    },

    loadHouse: function(houseId, callback) {
        modules.addresses.houses.houseId = 0;
        modules.addresses.houses.settlementId = 0;
        modules.addresses.houses.streetId = 0;

        QUERY("addresses", "addresses", {
            houseId: houseId,
        }).
        done(modules.addresses.addresses).
        fail(FAILPAGE).
        done(() => {
            if (modules && modules.addresses && modules.addresses.meta && modules.addresses.meta.houses) {
                let f = false;
                for (let i in modules.addresses.meta.houses) {
                    if (modules.addresses.meta.houses[i].houseId == houseId) {
                        if (!modules.addresses.houses.meta) {
                            modules.addresses.houses.meta = {};
                        }
                        modules.addresses.houses.meta.house = modules.addresses.meta.houses[i];
                        modules.addresses.houses.houseId = houseId;
                        modules.addresses.houses.settlementId = modules.addresses.meta.houses[i].settlementId?modules.addresses.meta.houses[i].settlementId:0;
                        modules.addresses.houses.streetId = modules.addresses.meta.houses[i].streetId?modules.addresses.meta.houses[i].streetId:0;
                        subTop(modules.addresses.path((modules.addresses.meta.houses[i].settlementId?"settlement":"street"), modules.addresses.meta.houses[i].settlementId?modules.addresses.meta.houses[i].settlementId:modules.addresses.meta.houses[i].streetId) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + modules.addresses.houses.meta.house.houseFull);
                        f = true;
                    }
                }
                if (!f) {
                    subTop("#" + houseId);
                }
            }

            GET("houses", "house", houseId, true).
            fail(FAILPAGE).
            done(response => {
                if (!modules.addresses.houses.meta) {
                    modules.addresses.houses.meta = {};
                }

                modules.addresses.houses.meta.entrances = response["house"].entrances;
                modules.addresses.houses.meta.flats = response["house"].flats;
                modules.addresses.houses.meta.cameras = response["house"].cameras;
                modules.addresses.houses.meta.domophoneModels = response["house"].domophoneModels;
                modules.addresses.houses.meta.cmses = response["house"].cmses;

                if (modules.addresses.houses.meta.house && modules.addresses.houses.meta.house.houseFull) {
                    document.title = i18n("windowTitle") + " :: " + i18n("addresses.house") + " :: " + modules.addresses.houses.meta.house.houseFull;
                }

                callback();
            });
        });
    },

    renderHouse: function (houseId) {
        modules.addresses.houses.loadHouse(houseId, () => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.flats"),
                    button: {
                        caption: i18n("addresses.addFlat"),
                        click: () => {
                            modules.addresses.houses.addFlat(houseId);
                        },
                    },
                },
                edit: flatId => {
                    modules.addresses.houses.modifyFlat(flatId, houseId);
                },
                columns: [
                    {
                        title: i18n("addresses.flatId"),
                    },
                    {
                        title: i18n("addresses.floor"),
                    },
                    {
                        title: i18n("addresses.flat"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.houses.meta.flats) {
                        rows.push({
                            uid: modules.addresses.houses.meta.flats[i].flatId,
                            cols: [
                                {
                                    data: modules.addresses.houses.meta.flats[i].flatId,
                                },
                                {
                                    data: modules.addresses.houses.meta.flats[i].floor?modules.addresses.houses.meta.flats[i].floor:"-",
                                },
                                {
                                    data: modules.addresses.houses.meta.flats[i].flat,
                                    nowrap: true,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-house-user",
                                        title: i18n("addresses.subscribersKeysAndCameras"),
                                        click: flatId => {
                                            for (let i in modules.addresses.houses.meta.flats) {
                                                if (modules.addresses.houses.meta.flats[i].flatId == flatId) {
                                                    location.href = "?#addresses.subscribers&flatId=" + flatId + "&houseId=" + houseId + "&flat=" + encodeURIComponent(modules.addresses.houses.meta.flats[i].flat) + "&settlementId=" + modules.addresses.houses.settlementId + "&streetId=" + modules.addresses.houses.streetId;
                                                }
                                            }
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-mobile-alt",
                                        title: i18n("addresses.mobileCall"),
                                        click: flatId => {
                                            if (modules.asterisk && modules.asterisk.ready && !modules.asterisk.currentSession) {
                                                let n = 5000000000 + parseInt(flatId);
                                                modules.asterisk.call(n);
                                                message(i18n("asterisk.dialing", n), i18n("asterisk.outgoingCall"), 5);
                                            } else {
                                                error(i18n("asterisk.dialFail"), i18n("asterisk.outgoingCall"), 5);
                                            }
                                        },
                                        disabled: !(modules.asterisk && modules.asterisk.ready && !modules.asterisk.currentSession),
                                    },
                                    {
                                        icon: "fas fa-home",
                                        title: i18n("addresses.flatCall"),
                                        click: flatId => {
                                            let n = 3000000000 + parseInt(flatId);
                                            if (modules.asterisk && modules.asterisk.ready && !modules.asterisk.currentSession) {
                                                modules.asterisk.call(n);
                                                message(i18n("asterisk.dialing", n), i18n("asterisk.outgoingCall"), 5);
                                            } else {
                                                error(i18n("asterisk.dialFail"), i18n("asterisk.outgoingCall"), 5);
                                            }
                                        },
                                        disabled: !(modules.asterisk && modules.asterisk.ready && !modules.asterisk.currentSession),
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            }).show();

            cardTable({
                target: "#altForm",
                title: {
                    caption: i18n("addresses.entrances"),
                    button: {
                        caption: i18n("addresses.addEntrance"),
                        click: () => {
                            modules.addresses.houses.addEntrance(houseId);
                        },
                    },
                },
                edit: entranceId => {
                    modules.addresses.houses.modifyEntrance(entranceId, houseId);
                },
                columns: [
                    {
                        title: i18n("addresses.entranceId"),
                    },
                    {
                        title: i18n("addresses.entranceType"),
                    },
                    {
                        title: i18n("addresses.entrance"),
                    },
                    {
                        title: i18n("addresses.shared"),
                    },
                    {
                        title: i18n("addresses.prefix"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];
                    let entrances = {};

                    for (let i in modules.addresses.houses.meta.entrances) {
                        entrances[modules.addresses.houses.meta.entrances[i].entranceId] = modules.addresses.houses.meta.entrances[i];
                        rows.push({
                            uid: modules.addresses.houses.meta.entrances[i].entranceId,
                            cols: [
                                {
                                    data: modules.addresses.houses.meta.entrances[i].entranceId,
                                },
                                {
                                    data: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full"),
                                },
                                {
                                    data: modules.addresses.houses.meta.entrances[i].entrance,
                                    nowrap: true,
                                },
                                {
                                    data: parseInt(modules.addresses.houses.meta.entrances[i].shared)?i18n("yes"):i18n("no"),
                                },
                                {
                                    data: parseInt(modules.addresses.houses.meta.entrances[i].shared)?modules.addresses.houses.meta.entrances[i].prefix:"-",
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-door-open",
                                        title: i18n("addresses.domophone"),
                                        disabled: ! modules.addresses.houses.meta.entrances[i].domophoneId,
                                        click: entranceId => {
                                            location.href = "?#addresses.domophones&domophoneId=" + entrances[entranceId].domophoneId;
                                        },
                                    },
                                    {
                                        icon: "fas fa-video",
                                        title: i18n("addresses.camera"),
                                        disabled: ! modules.addresses.houses.meta.entrances[i].cameraId,
                                        click: entranceId => {
                                            location.href = "?#addresses.cameras&cameraId=" + entrances[entranceId].cameraId;
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-phone-volume",
                                        title: i18n("addresses.cms"),
                                        disabled: modules.addresses.houses.meta.entrances[i].cms.toString() === "0",
                                        click: entranceId => {
                                            location.href = "?#addresses.houses&show=cms&houseId=" + houseId + "&entranceId=" + entrances[entranceId].entranceId;
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-key",
                                        title: i18n("addresses.keys"),
                                        click: entranceId => {
                                            // ?
                                        },
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            });

            if (modules.addresses.houses.meta.cameras === false) {
                $("#altForm").show();
            } else {
                cardTable({
                    target: "#altForm",
                    mode: "append",
                    title: {
                        caption: i18n("addresses.cameras"),
                        button: {
                            caption: i18n("addresses.addCamera"),
                            click: () => {
                                modules.addresses.houses.addCamera(houseId);
                            },
                        },
                    },
                    columns: [
                        {
                            title: i18n("addresses.cameraIdList"),
                        },
                        {
                            title: i18n("addresses.url"),
                        },
                        {
                            title: i18n("addresses.cameraName"),
                        },
                        {
                            title: i18n("addresses.comments"),
                            fullWidth: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];
                        for (let i in modules.addresses.houses.meta.cameras) {
                            rows.push({
                                uid: modules.addresses.houses.meta.cameras[i].cameraId,
                                cols: [
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].cameraId?modules.addresses.houses.meta.cameras[i].cameraId:i18n("addresses.deleted"),
                                        click: modules.addresses.houses.meta.cameras[i].cameraId?("#addresses.cameras&filter=" + modules.addresses.houses.meta.cameras[i].cameraId):false,
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].url?modules.addresses.houses.meta.cameras[i].url:"",
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].name?modules.addresses.houses.meta.cameras[i].name:"",
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].comment?modules.addresses.houses.meta.cameras[i].comment:"",
                                        nowrap: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-trash-alt",
                                            title: i18n("addresses.deleteCamera"),
                                            class: "text-danger",
                                            disabled: !modules.addresses.houses.meta.cameras[i].cameraId,
                                            click: cameraId => {
                                                mConfirm(i18n("addresses.confirmDeleteCamera", cameraId), i18n("confirm"), `danger:${i18n("addresses.deleteCamera")}`, () => {
                                                    modules.addresses.houses.doDeleteCamera(cameraId, houseId);
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
            }

            loadingDone();
        });
    },

    addCamera: function (houseId) {
        GET("cameras", "cameras", false, true).
        done(response => {
            modules.addresses.cameras.meta = response.cameras;
            let cameras = [];

            cameras.push({
                id: "0",
                text: i18n("no"),
            })

            for (let i in response.cameras.cameras) {
                let url;
                try {
                    url = new URL(response.cameras.cameras[i].url);
                } catch (e) {
                    url = {
                        host: response.cameras.cameras[i].url,
                    }
                }
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text:  url.host + " [" + response.cameras.cameras[i].name + "]",
                })
            }

            cardForm({
                title: i18n("addresses.addCamera"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                size: "lg",
                fields: [
                    {
                        id: "cameraId",
                        type: "select2",
                        title: i18n("addresses.cameraId"),
                        options: cameras,
                    },
                ],
                callback: result => {
                    result.houseId = houseId;
                    modules.addresses.houses.doAddCamera(result);
                },
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    renderEntranceCMS: function (houseId, entranceId) {
        GET("houses", "cms", entranceId, true).
        fail(FAIL).
        fail(() => {
            pageError();
        }).
        done(response => {
            let cms_layout = response.cms;

            modules.addresses.houses.loadHouse(houseId, () => {
                let entrance = false;

                for (let i in modules.addresses.houses.meta.entrances) {
                    if (modules.addresses.houses.meta.entrances[i].entranceId == entranceId) {
                        entrance = modules.addresses.houses.meta.entrances[i];
                        break;
                    }
                }

                if (entrance) {
                    let cms = modules.addresses.houses.meta.cmses[entrance.cms];

                    if (cms) {
                        let h = `<div class="card mt-2">`;

                        h += `<div class="card-body table-responsive p-0">`;

                        let cmsi = 0;

                        for (let i in cms.cms) {
                            h += `<hr class="hr-text ml-3" data-content="${i}">`;
                            h += `<table class="table table-hover ml-3" style="width: 0%;">`;

                            let maxX = 0;
                            for (let j in cms.cms[i]) {
                                maxX = Math.max(maxX, cms.cms[i][j]);
                            }

                            h += `<thead>`;

                            h += `<th>&nbsp;</th>`;

                            for (let j = 0; j < maxX; j++) {
                                h += `<th>${i18n("addresses.cmsD")}${j + cms.dozen_start}</th>`;
                            }
                            h += `</thead>`;

                            h += `<tbody>`;

                            for (let j in cms.cms[i]) {
                                h += `<tr>`;
                                h += `<td>${i18n("addresses.cmsU")}${parseInt(j)}</td>`;
                                for (let k = 0; k < cms.cms[i][j]; k++) {
                                    h += `<td>`;
                                    h += `<input class="cmsa form-control form-control-sm pl-1 pr-1" data-cms="${cmsi}" data-dozen="${k}" data-unit="${j}" type="text" style="width: 40px; font-size: 75%; height: calc(1.5rem + 2px);" value="">`
                                    h += `</td>`;
                                }
                                for (let k = cms.cms[i][j]; k < maxX; k++) {
                                    h += `<td>&nbsp;</td>`;
                                }
                                h += `</tr>`;
                            }

                            h += `</tbody>`;
                            h += `</table>`;

                            cmsi++;
                        }

                        h += `<button id="entranceCmsSubmit" type="submit" class="btn btn-primary modalFormOk ml-3 mb-2 mt-2">${i18n("apply")}</button>`;

                        h += `</div>`;
                        h += `</div>`;

                        $("#mainForm").html(h);

                        for (let i in cms_layout) {
                            $(`.cmsa[data-cms='${cms_layout[i].cms}'][data-dozen='${cms_layout[i].dozen}'][data-unit='${cms_layout[i].unit}']`).val(cms_layout[i].apartment);
                        }

                        $("#entranceCmsSubmit").off("click").on("click", () => {
                            let cmses = [];

                            $(".cmsa").each(function () {
                                let cms = $(this).attr("data-cms");
                                let dozen = $(this).attr("data-dozen");
                                let unit = $(this).attr("data-unit");
                                let apartment = parseInt($(this).val());
                                if (cms && dozen && unit && apartment) {
                                    cmses.push({
                                        cms,
                                        dozen,
                                        unit,
                                        apartment,
                                    });
                                }
                            });

                            loadingStart();

                            PUT("houses", "cms", entranceId, {
                                cms: cmses,
                            }).
                            done(() => {
                                modules.addresses.houses.renderEntranceCMS(houseId, entranceId);
                            }).
                            fail(FAIL).
                            fail(loadingDone);
                        });

                        loadingDone();
                    } else {
                        pageError(i18n("addresses.unknownOrInvalidCms"));
                    }
                } else {
                    pageError(i18n("addresses.entranceNotFound"));
                }
            });
        });
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("addresses.house");

        modules.addresses.topMenu();

        if (params.show === "cms" && parseInt(params.entranceId) > 0) {
            $("#altForm").hide();
            $("#subTop").html("");

            modules.addresses.houses.renderEntranceCMS(params.houseId, params.entranceId);
        } else {
            modules.addresses.houses.renderHouse(params.houseId);
        }
    },
}).init();