({
    init: function () {
        leftSide("fas fa-fw fa-home", i18n("houses.houses"), "#houses", false, true);

        $(".sidebar .nav-item a[href='#houses']").on("click", function (event) {
            event.stopPropagation();
            return false;
        });

        moduleLoaded("houses", this);
    },

    cmses: function (domophoneId, selected) {
        let c = [];

        c.push({
            id: "0",
            text: "нет",
        })

        for (let id in modules["houses"].meta.cmses) {
            if (domophoneId && modules["houses"].meta.models[domophoneId] && modules["houses"].meta.models[domophoneId].cmses.indexOf(id.split(".json")[0]) >= 0) {
                c.push({
                    id: id,
                    text: modules["houses"].meta.cmses[id].title,
                    selected: selected === id,
                })
            }
        }

        return c;
    },

    outputs: function (domophoneId, selected) {
        let o = [];

/* TODO
        for (let i = 0; i < 32; i++) {
            if (domophoneId && modules["houses"].meta.models[domophoneId] && i < parseInt(modules["houses"].meta.models[domophoneId].outputs)) {
                o.push({
                    id: i,
                    text: i?i18n("houses.domophoneOutputSecondary", i):i18n("houses.domophoneOutputPrimary"),
                    selected: selected === i,
                });
            }
        }
*/

        for (let i = 0; i < 4; i++) {
            o.push({
                id: i,
                text: i?i18n("houses.domophoneOutputSecondary", i):i18n("houses.domophoneOutputPrimary"),
                selected: selected === i,
            });
        }

        return o;
    },

    doAddEntrance: function (houseId, entranceId, prefix) {
        loadingStart();
        POST("houses", "entrance", false, {
            houseId,
            entranceId,
            prefix,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("houses.entranceWasAdded"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
        });
    },

    doCreateEntrance: function (houseId, entranceType, entrance, lat, lon, shared, prefix, domophoneId, domophoneOutput, cms, cmsType, cameraId, cmsLevels, locksDisabled) {
        loadingStart();
        POST("houses", "entrance", false, {
            houseId,
            entranceType,
            entrance,
            lat,
            lon,
            shared,
            prefix,
            domophoneId,
            domophoneOutput,
            cms,
            cmsType,
            cameraId,
            cmsLevels,
            locksDisabled,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("houses.entranceWasCreated"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
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
            message(i18n("houses.flatWasAdded"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
        });
    },

    doModifyEntrance: function (entranceId, houseId, entranceType, entrance, lat, lon, shared, prefix, domophoneId, domophoneOutput, cms, cmsType, cameraId, cmsLevels, locksDisabled) {
        loadingStart();
        PUT("houses", "entrance", entranceId, {
            houseId,
            entranceType,
            entrance,
            lat,
            lon,
            shared,
            prefix,
            domophoneId,
            domophoneOutput,
            cms,
            cmsType,
            cameraId,
            cmsLevels,
            locksDisabled,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("houses.entranceWasChanged"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
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
            message(i18n("houses.flatWasChanged"));
        }).
        always(() => {
            if (houseId) {
                modules["houses"].renderHouse(houseId);
            }
        });
    },

    doDeleteEntrance: function (entranceId, complete, houseId) {
        loadingStart();
        if (complete) {
            DELETE("houses", "entrance", entranceId).
            fail(FAIL).
            done(() => {
                message(i18n("houses.entranceWasDeleted"));
            }).
            always(() => {
                modules["houses"].renderHouse(houseId);
            });
        } else {
            DELETE("houses", "entrance", entranceId, {
                houseId
            }).
            fail(FAIL).
            done(() => {
                message(i18n("houses.entranceWasDeleted"));
            }).
            always(() => {
                modules["houses"].renderHouse(houseId);
            });
        }
    },

    doDeleteFlat: function (flatId, houseId) {
        loadingStart();
        DELETE("houses", "flat", flatId).
        fail(FAIL).
        done(() => {
            message(i18n("houses.flatWasDeleted"));
        }).
        always(() => {
            modules["houses"].renderHouse(houseId);
        });
    },

    addEntrance: function (houseId) {
        mYesNo(i18n("houses.useExistingEntranceQuestion"), i18n("houses.addEntrance"), () => {
            loadingStart();
            GET("domophones", "domophones").
            done(response => {
                console.log(response);

                let first = false;
                let models = {};

                let domophones = [];

                for (let i in response.domophones.domophones) {
                    if (!first) {
                        first = response.domophones.domophones[i].domophoneId;
                    }
                    models[response.domophones.domophones[i].domophoneId] = response.domophones.domophones[i].model;
                    domophones.push({
                        id: response.domophones.domophones[i].domophoneId,
                        text: response.domophones.domophones[i].callerId + (response.domophones.domophones[i].comment?(" (" + response.domophones.domophones[i].comment + ")"):"") + " [" + response.domophones.domophones[i].ip + "]",
                    })
                }

                cardForm({
                    title: i18n("houses.addEntrance"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("add"),
                    size: "lg",
                    fields: [
                        {
                            id: "entranceType",
                            type: "select",
                            title: i18n("houses.entranceType"),
                            options: [
                                {
                                    id: "entrance",
                                    text: i18n("houses.entranceTypeEntranceFull"),
                                },
                                {
                                    id: "wicket",
                                    text: i18n("houses.entranceTypeWicketFull"),
                                },
                                {
                                    id: "gate",
                                    text: i18n("houses.entranceTypeGateFull"),
                                },
                                {
                                    id: "barrier",
                                    text: i18n("houses.entranceTypeBarrierFull"),
                                }
                            ]
                        },
                        {
                            id: "entrance",
                            type: "text",
                            title: i18n("houses.entrance"),
                            placeholder: i18n("houses.entrance"),
                            validate: (v) => {
                                return $.trim(v) !== "";
                            }
                        },
                        {
                            id: "lon",
                            type: "text",
                            title: i18n("houses.lon"),
                            placeholder: i18n("houses.lon"),
                        },
                        {
                            id: "lat",
                            type: "text",
                            title: i18n("houses.lat"),
                            placeholder: i18n("houses.lat"),
                        },
                        {
                            id: "cameraId",
                            type: "text",
                            title: i18n("houses.cameraId"),
                            placeholder: i18n("houses.cameraId"),
                        },
                        {
                            id: "domophoneId",
                            type: "select2",
                            title: i18n("houses.domophoneId"),
                            options: domophones,
                            validate: v => {
                                return parseInt(v) > 0;
                            },
                            select: (el, id, prefix) => {
                                $(`#${prefix}cms`).html("").select2({
                                    data: modules["houses"].cmses(models[el.val()]),
                                    language: lang["_code"],
                                });
/* TODO
                                let h = "";
                                let o = modules["houses"].outputs(models[el.val()]);
                                for (let i in o) {
                                    h += `<option value="${o[i].id}" ${o[i].selected?"selected":""}>${o[i].text}</option>`;
                                }
                                $("#" + prefix + "domophoneOutput").html(h);
*/
                            }
                        },
                        {
                            id: "domophoneOutput",
                            type: "select",
                            title: i18n("houses.domophoneOutput"),
                            placeholder: i18n("houses.domophoneOutput"),
                            options: modules["houses"].outputs(models[first]),
                            select: (el, id, prefix) => {
                                if (parseInt(el.val()) > 0) {
                                    $("#" + prefix + "cms").parent().parent().parent().hide();
                                    $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                    $("#" + prefix + "shared").parent().parent().parent().hide();
                                    $("#" + prefix + "prefix").parent().parent().hide();
                                    $("#" + prefix + "cmsLevels").parent().parent().hide();
                                    $("#" + prefix + "locksDisabled").parent().parent().parent().hide();
                                } else {
                                    $("#" + prefix + "cms").parent().parent().parent().show();
                                    $("#" + prefix + "shared").parent().parent().parent().show();
                                    $("#" + prefix + "locksDisabled").parent().parent().parent().show();
                                    if (parseInt($("#" + prefix + "cms").val()) !== 0) {
                                        $("#" + prefix + "cmsType").parent().parent().parent().show();
                                        $("#" + prefix + "cmsLevels").parent().parent().show();
                                    } else {
                                        $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                        $("#" + prefix + "cmsLevels").parent().parent().hide();
                                    }
                                    if (parseInt($("#" + prefix + "shared").val())) {
                                        $("#" + prefix + "prefix").parent().parent().show();
                                    } else {
                                        $("#" + prefix + "prefix").parent().parent().hide();
                                    }
                                }
                            },
                        },
                        {
                            id: "cms",
                            type: "select2",
                            title: i18n("domophones.cms"),
                            placeholder: i18n("domophones.cms"),
                            options: modules["houses"].cmses(models[first]),
                            select: (el, id, prefix) => {
                                if (parseInt(el.val()) === 0) {
                                    $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                    $("#" + prefix + "cmsLevels").parent().parent().hide();
                                } else {
                                    $("#" + prefix + "cmsType").parent().parent().parent().show();
                                    $("#" + prefix + "cmsLevels").parent().parent().show();
                                }
                            },
                        },
                        {
                            id: "cmsType",
                            type: "select",
                            title: i18n("houses.cmsType"),
                            hidden: true,
                            options: [
                                {
                                    id: "1",
                                    text: i18n("houses.cmsA"),
                                },
                                {
                                    id: "2",
                                    text: i18n("houses.cmsAV"),
                                },
                            ]
                        },
                        {
                            id: "cmsLevels",
                            type: "text",
                            title: i18n("houses.cmsLevels"),
                            hidden: true,
                        },
                        {
                            id: "locksDisabled",
                            type: "yesno",
                            title: i18n("houses.locksDisabled"),
                            value: 0,
                        },
                        {
                            id: "shared",
                            type: "select",
                            title: i18n("houses.shared"),
                            select: (el, id, prefix) => {
                                if (parseInt(el.val())) {
                                    $("#" + prefix + "prefix").parent().parent().show();
                                } else {
                                    $("#" + prefix + "prefix").parent().parent().hide();
                                }
                            },
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
                            id: "prefix",
                            type: "text",
                            title: i18n("houses.prefix"),
                            placeholder: i18n("houses.prefix"),
                            value: "0",
                            hidden: true,
                            validate: (v, prefix) => {
                                return !parseInt($("#" + prefix + "shared").val()) || parseInt(v) >= 1;
                            },
                        },
                    ],
                    callback: result => {
                        if (parseInt(result.domophoneOutput) > 0) {
                            result.cms = 0;
                            result.shared = 0;
                        }
                        if (!result.shared) {
                            result.prefix = 0;
                        }
                        if (!result.cms) {
                            result.cmsType = 0;
                        }
                        modules["houses"].doCreateEntrance(houseId, result.entranceType, result.entrance, result.lat, result.lon, result.shared, result.prefix, result.domophoneId, result.domophoneOutput, result.cms, result.cmsType, result.cameraId, result.cmsLevels, result.locksDisabled);
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }, () => {
            loadingStart();
            GET("houses", "sharedEntrances", houseId, true).
            done(response => {
                console.log(response);

                let entrances = [];

                entrances.push({
                    id: 0,
                    text: "нет",
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
                        text: house + ", " + i18n("houses.entranceType" + response.entrances[j].entranceType.substring(0, 1).toUpperCase() + response.entrances[j].entranceType.substring(1) + "Full").toLowerCase() + " " + response.entrances[j].entrance,
                    });
                }

                cardForm({
                    title: i18n("houses.addEntrance"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("add"),
                    fields: [
                        {
                            id: "entranceId",
                            type: "select2",
                            title: i18n("houses.entrance"),
                            options: entrances,
                            validate: v => {
                                return parseInt(v) > 0;
                            },
                        },
                        {
                            id: "prefix",
                            type: "text",
                            title: i18n("houses.prefix"),
                            placeholder: i18n("houses.prefix"),
                            value: "0",
                            validate: v => {
                                return parseInt(v) > 0;
                            },
                        },
                    ],
                    callback: result => {
                        if (parseInt(result.entranceId)) {
                            modules["houses"].doAddEntrance(houseId, result.entranceId, result.prefix);
                        }
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }, i18n("houses.addNewEntrance"), i18n("houses.useExistingEntrance"));
    },

    addFlat: function (houseId) {
        let entrances = [];
        let prefx = md5(guid());

        for (let i in modules["houses"].meta.entrances) {
            if (parseInt(modules["houses"].meta.entrances[i].domophoneOutput) === 0) {
                let inputs = `
                    <div class="row mt-2 ${prefx}" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" style="display: none;">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" placeholder="${i18n("houses.apartment")}">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" placeholder="${i18n("houses.apartmentLevels")}">
                        </div>
                    </div>
                `;
                console.log(modules["houses"].meta.entrances[i]);
                if (parseInt(modules["houses"].meta.entrances[i].cms) !== 0) {
                    entrances.push({
                        id: modules["houses"].meta.entrances[i].entranceId,
                        text: i18n("houses.entranceType" + modules["houses"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["houses"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["houses"].meta.entrances[i].entrance + inputs,
                    });
                } else {
                    entrances.push({
                        id: modules["houses"].meta.entrances[i].entranceId,
                        text: i18n("houses.entranceType" + modules["houses"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["houses"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["houses"].meta.entrances[i].entrance,
                    });
                }
            }
        }

        cardForm({
            title: i18n("houses.addFlat"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "floor",
                    type: "text",
                    title: i18n("houses.floor"),
                    placeholder: i18n("houses.floor"),
                },
                {
                    id: "flat",
                    type: "text",
                    title: i18n("houses.flat"),
                    placeholder: i18n("houses.flat"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "entrances",
                    type: "multiselect",
                    title: i18n("houses.entrances"),
                    hidden: entrances.length <= 0,
                    options: entrances,
                },
                {
                    id: "manualBlock",
                    type: "select",
                    title: i18n("houses.manualBlock"),
                    placeholder: i18n("houses.manualBlock"),
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
                    title: i18n("houses.openCode"),
                    placeholder: i18n("houses.openCode"),
                },
                {
                    id: "autoOpen",
                    type: "text",
                    title: i18n("houses.autoOpen"),
                    placeholder: date("Y-m-d H:i"),
                },
                {
                    id: "whiteRabbit",
                    type: "select",
                    title: i18n("houses.whiteRabbit"),
                    placeholder: i18n("houses.whiteRabbit"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("houses.1m"),
                        },
                        {
                            id: "2",
                            text: i18n("houses.2m"),
                        },
                        {
                            id: "3",
                            text: i18n("houses.3m"),
                        },
                        {
                            id: "5",
                            text: i18n("houses.5m"),
                        },
                        {
                            id: "7",
                            text: i18n("houses.7m"),
                        },
                        {
                            id: "10",
                            text: i18n("houses.10m"),
                        },
                    ]
                },
                {
                    id: "sipEnabled",
                    type: "select",
                    title: i18n("houses.sipEnabled"),
                    placeholder: i18n("houses.sipEnabled"),
                    options: [
                        {
                            id: "0",
                            text: i18n("no"),
                        },
                        {
                            id: "1",
                            text: i18n("houses.sip"),
                        },
                        {
                            id: "2",
                            text: i18n("houses.webRtc"),
                        },
                    ]
                },
                {
                    id: "sipPassword",
                    type: "text",
                    title: i18n("houses.sipPassword"),
                    placeholder: i18n("houses.sipPassword"),
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
                    if ($(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).length) {
                        apartmentsAndLevels[entrances[i].id] = {
                            apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                            apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                        }
                    }
                }
                modules["houses"].doAddFlat(houseId, result.floor, result.flat, result.entrances, apartmentsAndLevels, result.manualBlock, result.openCode, result.autoOpen, result.whiteRabbit, result.sipEnabled, result.sipPassword);
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
        GET("domophones", "domophones").
        done(response => {
            let domophones = [];
            let models = {};

            for (let i in response.domophones.domophones) {
                models[response.domophones.domophones[i].domophoneId] = response.domophones.domophones[i].model;
                domophones.push({
                    id: response.domophones.domophones[i].domophoneId,
                    text: response.domophones.domophones[i].callerId + (response.domophones.domophones[i].comment ? (" (" + response.domophones.domophones[i].comment + ")") : "") + " [" + response.domophones.domophones[i].ip + "]",
                })
            }

            let entrance = false;

            for (let i in modules["houses"].meta.entrances) {
                if (modules["houses"].meta.entrances[i].entranceId == entranceId) {
                    entrance = modules["houses"].meta.entrances[i];
                    break;
                }
            }

            if (entrance) {
                cardForm({
                    title: i18n("houses.editEntrance"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("edit"),
                    delete: i18n("houses.deleteEntrance"),
                    size: "lg",
                    fields: [
                        {
                            id: "entranceId",
                            type: "text",
                            title: i18n("houses.entranceId"),
                            value: entranceId,
                            readonly: true,
                        },
                        {
                            id: "entranceType",
                            type: "select",
                            title: i18n("houses.entranceType"),
                            options: [
                                {
                                    id: "entrance",
                                    text: i18n("houses.entranceTypeEntranceFull"),
                                },
                                {
                                    id: "wicket",
                                    text: i18n("houses.entranceTypeWicketFull"),
                                },
                                {
                                    id: "gate",
                                    text: i18n("houses.entranceTypeGateFull"),
                                },
                                {
                                    id: "barrier",
                                    text: i18n("houses.entranceTypeBarrierFull"),
                                }
                            ],
                            value: entrance.entranceType,
                        },
                        {
                            id: "entrance",
                            type: "text",
                            title: i18n("houses.entrance"),
                            placeholder: i18n("houses.entrance"),
                            validate: (v) => {
                                return $.trim(v) !== "";
                            },
                            value: entrance.entrance,
                        },
                        {
                            id: "lon",
                            type: "text",
                            title: i18n("houses.lon"),
                            placeholder: i18n("houses.lon"),
                            value: entrance.lon,
                        },
                        {
                            id: "lat",
                            type: "text",
                            title: i18n("houses.lat"),
                            placeholder: i18n("houses.lat"),
                            value: entrance.lat,
                        },
                        {
                            id: "cameraId",
                            type: "text",
                            title: i18n("houses.cameraId"),
                            placeholder: i18n("houses.cameraId"),
                            value: entrance.cameraId,
                        },
                        {
                            id: "domophoneId",
                            type: "select2",
                            title: i18n("houses.domophoneId"),
                            value: entrance.domophoneId,
                            options: domophones,
                            select: (el, id, prefix) => {
                                $(`#${prefix}cms`).html("").select2({
                                    data: modules["houses"].cmses(models[el.val()]),
                                    language: lang["_code"],
                                });
/* TODO
                                let h = "";
                                let o = modules["houses"].outputs(models[el.val()]);
                                for (let i in o) {
                                    h += `<option value="${o[i].id}" ${o[i].selected?"selected":""}>${o[i].text}</option>`;
                                }
                                $("#" + prefix + "domophoneOutput").html(h);
*/
                            }
                        },
                        {
                            id: "domophoneOutput",
                            type: "select",
                            title: i18n("houses.domophoneOutput"),
                            placeholder: i18n("houses.domophoneOutput"),
                            value: entrance.domophoneOutput,
                            options: modules["houses"].outputs(),
                            select: (el, id, prefix) => {
                                if (parseInt(el.val()) > 0) {
                                    $("#" + prefix + "cms").parent().parent().parent().hide();
                                    $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                    $("#" + prefix + "shared").parent().parent().parent().hide();
                                    $("#" + prefix + "prefix").parent().parent().hide();
                                    $("#" + prefix + "cmsLevels").parent().parent().hide();
                                    $("#" + prefix + "locksDisabled").parent().parent().parent().hide();
                                } else {
                                    $("#" + prefix + "cms").parent().parent().parent().show();
                                    $("#" + prefix + "shared").parent().parent().parent().show();
                                    $("#" + prefix + "locksDisabled").parent().parent().parent().show();
                                    if (parseInt($("#" + prefix + "cms").val()) !== 0) {
                                        $("#" + prefix + "cmsType").parent().parent().parent().show();
                                        $("#" + prefix + "cmsLevels").parent().parent().show();
                                    } else {
                                        $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                        $("#" + prefix + "cmsLevels").parent().parent().hide();
                                    }
                                    if (parseInt($("#" + prefix + "shared").val())) {
                                        $("#" + prefix + "prefix").parent().parent().show();
                                    } else {
                                        $("#" + prefix + "prefix").parent().parent().hide();
                                    }
                                }
                            },
                        },
                        {
                            id: "cms",
                            type: "select2",
                            title: i18n("domophones.cms"),
                            placeholder: i18n("domophones.cms"),
                            options: modules["houses"].cmses(models[entrance.domophoneId]),
                            hidden: parseInt(entrance.domophoneOutput) !== 0,
                            value: entrance.cms,
                            select: (el, id, prefix) => {
                                if (parseInt(el.val()) === 0) {
                                    $("#" + prefix + "cmsType").parent().parent().parent().hide();
                                    $("#" + prefix + "cmsLevels").parent().parent().hide();
                                } else {
                                    $("#" + prefix + "cmsType").parent().parent().parent().show();
                                    $("#" + prefix + "cmsLevels").parent().parent().show();
                                }
                            },
                        },
                        {
                            id: "cmsType",
                            type: "select",
                            title: i18n("houses.cmsType"),
                            value: entrance.cmsType,
                            hidden: parseInt(entrance.domophoneOutput) !== 0 || parseInt(entrance.cms) === 0,
                            options: [
                                {
                                    id: "1",
                                    text: i18n("houses.cmsA"),
                                },
                                {
                                    id: "2",
                                    text: i18n("houses.cmsAV"),
                                },
                            ]
                        },
                        {
                            id: "cmsLevels",
                            type: "text",
                            title: i18n("houses.cmsLevels"),
                            value: entrance.cmsLevels,
                            hidden: parseInt(entrance.domophoneOutput) !== 0 || parseInt(entrance.cms) === 0,
                        },
                        {
                            id: "locksDisabled",
                            type: "yesno",
                            title: i18n("houses.locksDisabled"),
                            value: entrance.locksDisabled,
                            hidden: parseInt(entrance.domophoneOutput) !== 0 || parseInt(entrance.cms) === 0,
                        },
                        {
                            id: "shared",
                            type: "select",
                            title: i18n("houses.shared"),
                            hidden: parseInt(entrance.domophoneOutput) !== 0,
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
                            id: "prefix",
                            type: "text",
                            title: i18n("houses.prefix"),
                            placeholder: i18n("houses.prefix"),
                            value: entrance.prefix?entrance.prefix.toString():"0",
                            hidden: !parseInt(entrance.shared) || parseInt(entrance.domophoneOutput) > 0,
                            validate: (v, prefix) => {
                                return !parseInt($("#" + prefix + "shared").val()) || parseInt(v) >= 1;
                            },
                        },
                    ],
                    callback: result => {
                        if (result.delete === "yes") {
                            modules["houses"].deleteEntrance(entranceId, parseInt(entrance.shared), houseId);
                        } else {
                            if (parseInt(result.domophoneOutput) > 0) {
                                result.cms = 0;
                                result.shared = 0;
                            }
                            if (parseInt(result.shared) === 0) {
                                result.prefix = 0;
                            }
                            if (parseInt(result.cms) === 0) {
                                result.cmsType = 0;
                            }
                            modules["houses"].doModifyEntrance(entranceId, houseId, result.entranceType, result.entrance, result.lat, result.lon, result.shared, result.prefix, result.domophoneId, result.domophoneOutput, result.cms, result.cmsType, result.cameraId, result.cmsLevels, result.locksDisabled);
                        }
                    },
                });
            } else {
                error(i18n("houses.entranceNotFound"));
            }
        }).
        fail(FAIL).
        always(loadingDone);
    },

    modifyFlat: function (flatId, houseId) {
        let flat = false;

        for (let i in modules["houses"].meta.flats) {
            if (modules["houses"].meta.flats[i].flatId == flatId) {
                flat = modules["houses"].meta.flats[i];
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

            for (let i in modules["houses"].meta.entrances) {
                let inputs = `
                    <div class="row mt-2 ${prefx}" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" style="display: none;">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" placeholder="${i18n("houses.apartment")}" value="${entrances_settings[modules["houses"].meta.entrances[i].entranceId]?entrances_settings[modules["houses"].meta.entrances[i].entranceId].apartment:""}">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartmentLevels" data-entrance-id="${modules["houses"].meta.entrances[i].entranceId}" placeholder="${i18n("houses.apartmentLevels")}" value="${entrances_settings[modules["houses"].meta.entrances[i].entranceId]?entrances_settings[modules["houses"].meta.entrances[i].entranceId].apartmentLevels:""}">
                        </div>
                    </div>
                `;
                if (parseInt(modules["houses"].meta.entrances[i].cmsType)) {
                    entrances.push({
                        id: modules["houses"].meta.entrances[i].entranceId,
                        text: i18n("houses.entranceType" + modules["houses"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["houses"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["houses"].meta.entrances[i].entrance + inputs,
                    });
                } else {
                    entrances.push({
                        id: modules["houses"].meta.entrances[i].entranceId,
                        text: i18n("houses.entranceType" + modules["houses"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["houses"].meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules["houses"].meta.entrances[i].entrance,
                    });
                }
            }

            cardForm({
                title: i18n("houses.editFlat"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: houseId?i18n("houses.deleteFlat"):false,
                apply: i18n("edit"),
                size: "lg",
                fields: [
                    {
                        id: "flatId",
                        type: "text",
                        title: i18n("houses.flatId"),
                        value: flatId,
                        readonly: true,
                    },
                    {
                        id: "floor",
                        type: "text",
                        title: i18n("houses.floor"),
                        placeholder: i18n("houses.floor"),
                        value: flat.floor,
                    },
                    {
                        id: "flat",
                        type: "text",
                        title: i18n("houses.flat"),
                        placeholder: i18n("houses.flat"),
                        value: flat.flat,
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "entrances",
                        type: "multiselect",
                        title: i18n("houses.entrances"),
                        hidden: entrances.length <= 0,
                        options: entrances,
                        value: entrances_selected,
                    },
                    {
                        id: "manualBlock",
                        type: "select",
                        title: i18n("houses.manualBlock"),
                        placeholder: i18n("houses.manualBlock"),
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
                        title: i18n("houses.openCode"),
                        placeholder: i18n("houses.openCode"),
                        value: flat.openCode,
                    },
                    {
                        id: "autoOpen",
                        type: "text",
                        title: i18n("houses.autoOpen"),
                        placeholder: date("Y-m-d H:i"),
                        value: date("Y-m-d H:i", strtotime(flat.autoOpen)),
                    },
                    {
                        id: "whiteRabbit",
                        type: "select",
                        title: i18n("houses.whiteRabbit"),
                        placeholder: i18n("houses.whiteRabbit"),
                        value: flat.whiteRabbit,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("houses.1m"),
                            },
                            {
                                id: "2",
                                text: i18n("houses.2m"),
                            },
                            {
                                id: "3",
                                text: i18n("houses.3m"),
                            },
                            {
                                id: "5",
                                text: i18n("houses.5m"),
                            },
                            {
                                id: "7",
                                text: i18n("houses.7m"),
                            },
                            {
                                id: "10",
                                text: i18n("houses.10m"),
                            },
                        ]
                    },
                    {
                        id: "sipEnabled",
                        type: "select",
                        title: i18n("houses.sipEnabled"),
                        placeholder: i18n("houses.sipEnabled"),
                        value: flat.sipEnabled,
                        options: [
                            {
                                id: "0",
                                text: i18n("no"),
                            },
                            {
                                id: "1",
                                text: i18n("houses.sip"),
                            },
                            {
                                id: "2",
                                text: i18n("houses.webRtc"),
                            },
                        ]
                    },
                    {
                        id: "sipPassword",
                        type: "text",
                        title: i18n("houses.sipPassword"),
                        placeholder: i18n("houses.sipPassword"),
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
                        if ($(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).length) {
                            apartmentsAndLevels[entrances[i].id] = {
                                apartment: $(`.${prefx}-apartment[data-entrance-id="${entrances[i].id}"]`).val(),
                                apartmentLevels: $(`.${prefx}-apartmentLevels[data-entrance-id="${entrances[i].id}"]`).val(),
                            }
                        }
                    }
                    if (result.delete === "yes") {
                        modules["houses"].deleteFlat(flatId, houseId);
                    } else {
                        modules["houses"].doModifyFlat(flatId, result.floor, result.flat, result.entrances, apartmentsAndLevels, result.manualBlock, result.openCode, result.autoOpen, result.whiteRabbit, result.sipEnabled, result.sipPassword, houseId);
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
            mYesNo(i18n("houses.completelyDeleteEntrance", entranceId), i18n("houses.deleteEntrance"), () => {
                modules["houses"].doDeleteEntrance(entranceId, true, houseId);
            }, () => {
                modules["houses"].doDeleteEntrance(entranceId, false, houseId);
            }, i18n("houses.deleteEntranceComletely"), i18n("houses.deleteEntranceLink"));
        } else {
            mConfirm(i18n("houses.confirmDeleteEntrance", entranceId), i18n("confirm"), `danger:${i18n("houses.deleteEntrance")}`, () => {
                modules["houses"].doDeleteEntrance(entranceId, true, houseId);
            });
        }
    },

    deleteFlat: function (flatId, houseId) {
        mConfirm(i18n("houses.confirmDeleteFlat", flatId), i18n("confirm"), `danger:${i18n("houses.deleteFlat")}`, () => {
            modules["houses"].doDeleteFlat(flatId, houseId);
        });
    },

    house: function (houseId) {

        function render() {
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
                edit: flatId => {
                    modules["houses"].modifyFlat(flatId, houseId);
                },
                columns: [
                    {
                        title: i18n("houses.flatId"),
                    },
                    {
                        title: i18n("houses.floor"),
                    },
                    {
                        title: i18n("houses.flat"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules["houses"].meta.flats) {
                        rows.push({
                            uid: modules["houses"].meta.flats[i].flatId,
                            cols: [
                                {
                                    data: modules["houses"].meta.flats[i].flatId,
                                },
                                {
                                    data: modules["houses"].meta.flats[i].floor?modules["houses"].meta.flats[i].floor:"-",
                                },
                                {
                                    data: modules["houses"].meta.flats[i].flat,
                                    nowrap: true,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-mobile-alt",
                                        title: i18n("subscribers.subscribers"),
                                        click: flatId => {
                                            // ?
                                        },
                                    },
                                    {
                                        icon: "fas fa-key",
                                        title: i18n("keys.keys"),
                                        click: flatId => {
                                            // ?
                                        },
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
                    caption: i18n("houses.entrances"),
                    button: {
                        caption: i18n("houses.addEntrance"),
                        click: () => {
                            modules["houses"].addEntrance(houseId);
                        },
                    },
                },
                edit: entranceId => {
                    modules["houses"].modifyEntrance(entranceId, houseId);
                },
                columns: [
                    {
                        title: i18n("houses.entranceId"),
                    },
                    {
                        title: i18n("houses.entranceType"),
                    },
                    {
                        title: i18n("houses.entrance"),
                    },
                    {
                        title: i18n("houses.shared"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];
                    let entrances = {};

                    for (let i in modules["houses"].meta.entrances) {
                        entrances[modules["houses"].meta.entrances[i].entranceId] = modules["houses"].meta.entrances[i];
                        rows.push({
                            uid: modules["houses"].meta.entrances[i].entranceId,
                            cols: [
                                {
                                    data: modules["houses"].meta.entrances[i].entranceId,
                                },
                                {
                                    data: i18n("houses.entranceType" + modules["houses"].meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules["houses"].meta.entrances[i].entranceType.substring(1) + "Full"),
                                },
                                {
                                    data: modules["houses"].meta.entrances[i].entrance,
                                    nowrap: true,
                                },
                                {
                                    data: parseInt(modules["houses"].meta.entrances[i].shared)?i18n("yes"):i18n("no"),
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-door-open",
                                        title: i18n("domophones.domophone"),
                                        disabled: ! modules["houses"].meta.entrances[i].domophoneId,
                                        click: entranceId => {
                                            location.href = "#domophones&domophoneId=" + entrances[entranceId].domophoneId;
                                        },
                                    },
                                    {
                                        icon: "fas fa-video",
                                        title: i18n("cameras.camera"),
                                        disabled: ! modules["houses"].meta.entrances[i].cameraId,
                                        click: entranceId => {
                                            location.href = "#cameras&cameraId=" + entrances[entranceId].cameraId;
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
                    if (!modules["houses"].meta) {
                        modules["houses"].meta = {};
                    }
                    modules["houses"].meta.house = modules["addresses"].meta.houses[i];
                    subTop(modules["houses"].meta.house.houseFull);
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
            if (!modules["houses"].meta) {
                modules["houses"].meta = {};
            }
            modules["houses"].meta.entrances = response["house"].entrances;
            modules["houses"].meta.flats = response["house"].flats;
            modules["houses"].meta.models = response["house"].models;
            modules["houses"].meta.cmses = response["house"].cmses;

            if (modules["houses"].meta.house && modules["houses"].meta.house.houseFull) {
                document.title = i18n("windowTitle") + " :: " + i18n("houses.house") + " :: " + modules["houses"].meta.house.houseFull;
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
                modules["houses"].house(houseId);
            });
        } else {
            modules["houses"].house(houseId);
        }

        loadingDone();
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("houses.house");

        modules["houses"].renderHouse(params.houseId);
    },
}).init();