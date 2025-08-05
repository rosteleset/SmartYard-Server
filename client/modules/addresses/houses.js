({
    init: function () {
        moduleLoaded("addresses.houses", this);
    },

    customFieldsConfiguration: {},
    houseId: "0",
    settlementId: "0",
    streetId: "0",
    pathNodes: {},
    map: false,
    fiases: {},
    marker: false,

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
                                let [ route, params ] = hashParse();
                                if (result.house.streetId) {
                                    if (route == "addresses" && params["show"] == "street" && params["streetId"] == result.house.streetId) {
                                        modules.addresses.renderStreet(result.house.streetId);
                                    } else {
                                        window.location.href = "?#addresses&show=street&streetId=" + result.house.streetId;
                                    }
                                } else {
                                    if (route == "addresses" && params["show"] == "settlement" && params["streetId"] == result.house.settlementId) {
                                        modules.addresses.renderSettlement(result.house.settlementId);
                                    } else {
                                        window.location.href = "?#addresses&show=settlement&settlementId=" + result.house.settlementId;
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
        });
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

        o.push({
            id: "0",
            text: i18n("addresses.domophoneOutputPrimary"),
            selected: parseInt(selected) === 0,
        });

        for (let i = 1; i < 32; i++) {
            if (domophoneModel && modules.addresses.houses.meta.domophoneModels[domophoneModel] && i < parseInt(modules.addresses.houses.meta.domophoneModels[domophoneModel].outputs)) {
                o.push({
                    id: i.toString(),
                    text: i18n("addresses.domophoneOutputSecondary", i),
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
            h += `<option value="${o[i].id}" ${o[i].selected ? "selected" : ""}>${o[i].text}</option>`;
        }

        $("#" + prefix + "domophoneOutput").html(h);
    },

    outputsSelect: function (el, id, prefix) {
        if (parseInt($("#" + prefix + "domophoneOutput").val()) > 0) {
            $("#" + prefix + "cms-container").attr("data-form-runtime-hide", "1").hide();
        } else {
            $("#" + prefix + "cms-container").attr("data-form-runtime-hide", "0").show();
        }

        modules.addresses.houses.cmsSelect(el, id, prefix);
    },

    cmsSelect: (el, id, prefix) => {
        if (parseInt($("#" + prefix + "cms").val()) !== 0 && $("#" + prefix + "cms:visible").length) {
            $("#" + prefix + "cmsType-container").attr("data-form-runtime-hide", "0").show();
            $("#" + prefix + "cmsLevels-container").attr("data-form-runtime-hide", "0").show();
        } else {
            $("#" + prefix + "cmsType-container").attr("data-form-runtime-hide", "1").hide();
            $("#" + prefix + "cmsLevels-container").attr("data-form-runtime-hide", "1").hide();
        }
    },

    sharedSelect: (el, id, prefix, cascade) => {
        if (parseInt($("#" + prefix + "shared").val()) && $("#" + prefix + "shared:visible").length) {
            $("#" + prefix + "prefix-container").attr("data-form-runtime-hide", "0").show();
        } else {
            $("#" + prefix + "prefix-container").attr("data-form-runtime-hide", "1").hide();
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
        return PUT("houses", "flat", flat.flatId, flat).
        fail(FAIL).
        done((result, code, response) => {
            message(i18n("addresses.flatWasChanged"));
            if (response.getResponseHeader("x-last-error")) {
                warning(i18n("errors." + response.getResponseHeader("x-last-error")));
            }
        }).
        always(() => {
            if (flat.houseId) {
                modules.addresses.houses.renderHouse(flat.houseId);
            } else {
                loadingDone();
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
                    let comments = $.trim(response.cameras.cameras[i].comments);
                    let name = $.trim(response.cameras.cameras[i].name);
                    let text = "";
                    if (name && comments) {
                        text = name + " (" + comments + ") [" + url.host + "]";
                    } else
                    if (name && !comments) {
                        text = name + " [" + url.host + "]";
                    } else
                    if (!name && comments) {
                        text = comments + " [" + url.host + "]";
                    } else {
                        text = url.host;
                    }
                    cameras.push({
                        id: response.cameras.cameras[i].cameraId,
                        text: text,
                    });
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
                        let comments = $.trim(response.domophones.domophones[i].comments);
                        let name = $.trim(response.domophones.domophones[i].name);
                        let text = "";
                        if (name && comments) {
                            text = name + " (" + comments + ") [" + url.host + "]";
                        } else
                        if (name && !comments) {
                            text = name + " [" + url.host + "]";
                        } else
                        if (!name && comments) {
                            text = comments + " [" + url.host + "]";
                        } else {
                            text = url.host;
                        }
                        domophones.push({
                            id: response.domophones.domophones[i].domophoneId,
                            text: text,
                        });
                    }

                    if (!domophones.length) {
                        domophones.push({
                            id: "0",
                            text: i18n("no"),
                        })
                    }

                    let pathFirst = true;
                    let treeName;

                    switch (config.camTree) {
                        case "perHouse":
                            treeName = "house" + houseId;
                            break;

                        default:
                            treeName = "houses";
                            break;
                    }

                    function path(node, cb) {
                        let nodeId;

                        nodeId = (node.id === "#") ? treeName : node.id;

                        QUERYID("houses", "path", nodeId, pathFirst ? {
                            withParents: true,
                            tree: treeName,
                        } : null).
                        done(result => {
                            if (result && result.tree) {
                                cb(result.tree);
                            } else {
                                cb([]);
                            }
                        }).
                        fail(FAIL).
                        fail(() => {
                            cb([]);
                        });

                        pathFirst = false;
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
                                ],
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "entrance",
                                type: "text",
                                title: i18n("addresses.entrance"),
                                placeholder: i18n("addresses.entrance"),
                                validate: v => {
                                    return $.trim(v) !== "";
                                },
                                tab: i18n("addresses.primary"),
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
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "callerId",
                                type: "text",
                                title: i18n("addresses.callerId"),
                                validate: v => {
                                    return $.trim(v) !== "";
                                },
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "cameraId",
                                type: "select2",
                                title: i18n("addresses.cameraId"),
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId1",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "1",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId2",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "2",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId3",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "3",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId4",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "4",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId5",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "5",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId6",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "6",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "altCameraId7",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "7",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                            },
                            {
                                id: "domophoneId",
                                type: "select2",
                                title: i18n("domophone"),
                                options: domophones,
                                validate: v => {
                                    return parseInt(v) > 0;
                                },
                                select: modules.addresses.houses.domophoneIdSelect,
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "domophoneOutput",
                                type: "select",
                                title: i18n("addresses.domophoneOutput"),
                                placeholder: i18n("addresses.domophoneOutput"),
                                options: modules.addresses.houses.outputs(modules.addresses.houses.meta.domophoneModelsById[first]),
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "cms",
                                type: "select2",
                                title: i18n("addresses.cms"),
                                placeholder: i18n("addresses.cms"),
                                options: modules.addresses.houses.cmses(modules.addresses.houses.meta.domophoneModelsById[first]),
                                select: modules.addresses.houses.cmsSelect,
                                tab: i18n("addresses.primary"),
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
                                ],
                                tab: i18n("addresses.primary"),
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
                                ],
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "plog",
                                type: "yesno",
                                title: i18n("addresses.plog"),
                                tab: i18n("addresses.primary"),
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
                                tab: i18n("addresses.primary"),
                            },
                            {
                                id: "path",
                                type: "jstree",
                                title: false,
                                tab: i18n("addresses.path"),
                                data: path,

                                addRoot: function (instance) {
                                    POST("houses", "path", treeName, {
                                        text: i18n("addresses.newNode"),
                                    }).done(result => {
                                        if (result && result.nodeId) {
                                            let node = {
                                                id: result.nodeId,
                                                text: i18n("addresses.newNode"),
                                            };
                                            instance.jstree().create_node("#", node, 'last', newNode => {
                                                setTimeout(() => {
                                                    instance.jstree().deselect_all();
                                                    instance.jstree().select_node(newNode);
                                                    instance.jstree().edit(newNode);
                                                }, 100);
                                            });
                                        }
                                    }).fail(FAIL);
                                },

                                add: function (instance) {
                                    let parent = instance.jstree().get_selected();
                                    parent = parent.length ? parent[0] : "#";
                                    POST("houses", "path", (parent === "#") ? treeName : parent, {
                                        text: i18n("addresses.newNode"),
                                    }).done(result => {
                                        if (result && result.nodeId) {
                                            let node = {
                                                id: result.nodeId,
                                                text: i18n("addresses.newNode"),
                                            };
                                            modules.addresses.houses.pathNodes[result.nodeId] = i18n("addresses.newNode");
                                            instance.jstree().create_node(parent, node, 'last', newNode => {
                                                setTimeout(() => {
                                                    instance.jstree().deselect_all();
                                                    instance.jstree().select_node(newNode);
                                                    instance.jstree().edit(newNode);
                                                }, 100);
                                            });
                                        }
                                    }).fail(FAIL);
                                },

                                rename: function (instance) {
                                    let node = instance.jstree().get_selected();
                                    if (node && node.length) {
                                        node = instance.jstree().get_node(node[0]);
                                        modules.addresses.houses.pathNodes[node.id] = node.text;
                                        setTimeout(() => {
                                            instance.jstree().edit(node);
                                        }, 100);
                                    }
                                },

                                renamed: function (e, data) {
                                    if (data && data.obj && data.obj.id && data.text && data.text != modules.addresses.houses.pathNodes[data.obj.id]) {
                                        PUT("houses", "path", data.obj.id, {
                                            text: data.text,
                                        }).
                                        done(() => {
                                            modules.addresses.houses.pathNodes[data.obj.id] = data.text;
                                        }).
                                        fail(FAIL);
                                    }
                                },

                                delete: function (instance) {
                                    let node = instance.jstree().get_selected();
                                    if (node && node.length) {
                                        node = instance.jstree().get_node(node[0]);
                                        mConfirm(i18n("addresses.confirmDeleteNode", escapeHTML(node.text)), i18n("confirm"), `danger:${i18n("addresses.deleteNode")}`, () => {
                                            DELETE("houses", "path", node.id).
                                            done(() => {
                                                instance.jstree().delete_node(node.id);
                                            }).
                                            fail(FAIL);
                                        });
                                    }
                                },

                                search: function (instance, str) {
                                    if (str) {
                                        QUERYID("houses", "path", treeName, { search: str }).
                                        done(result => {
                                            instance.jstree().settings.core.data = result.tree;
                                            instance.jstree().refresh();
                                            setTimeout(() => {
                                                instance.jstree().search(str);
                                                instance.jstree().settings.core.data = path;
                                            }, 100);
                                        }).
                                        fail(FAIL);
                                    } else {
                                        instance.jstree().clear_search();

                                        QUERYID("houses", "path", entrance.path ? entrance.path : treeName, {
                                            withParents: true,
                                            tree: treeName,
                                        }).
                                        done(result => {
                                            if (result && result.tree) {
                                                instance.jstree().settings.core.data = result.tree;
                                            } else {
                                                instance.jstree().settings.core.data = [];
                                            }
                                            instance.jstree().refresh();
                                        }).
                                        fail(FAIL);

                                        setTimeout(() => {
                                            instance.jstree().select_node(entrance.path);
                                            instance.jstree().settings.core.data = path;
                                        }, 100);
                                    }
                                }
                            },
                            {
                                id: "geoSuggestion",
                                type: "select2",
                                title: false,
                                placeholder: i18n("addresses.address"),
                                tab: i18n("addresses.map"),
                                hidden: !AVAIL("geo", "suggestions") || !modules.map,
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
                                                modules.addresses.houses.fiases[data.suggestions[i].data.house_fias_id] = data.suggestions[i].data;
                                            }
                                        }
                                        return {
                                            results: suggestions,
                                        };
                                    },
                                },
                            },
                            {
                                id: "geoMap",
                                type: "empty",
                                title: false,
                                placeholder: i18n("search"),
                                tab: i18n("addresses.map"),
                                noHover: true,
                            },
                        ],
                        done: function (prefix) {
                            $("#" + prefix + "geoSuggestion").off("change").on("change", e => {
                                let fias = $("#" + prefix + "geoSuggestion").val();
                                if (modules.addresses.houses.fiases[fias] && modules.addresses.houses.fiases[fias].geo_lat && modules.addresses.houses.fiases[fias].geo_lon) {
                                    modules.addresses.houses.map.setView([modules.addresses.houses.fiases[fias].geo_lat, modules.addresses.houses.fiases[fias].geo_lon], 18);
                                    modules.addresses.houses.marker.setLatLng([modules.addresses.houses.fiases[fias].geo_lat, modules.addresses.houses.fiases[fias].geo_lon]).update();
                                    $("#" + prefix + "geo").val(modules.addresses.houses.marker.getLatLng().lat + "," + modules.addresses.houses.marker.getLatLng().lng);
                                }
                            });

                            $("#" + prefix + "geoMap").css("height", "400px");

                            modules.addresses.houses.map = L.map(prefix + "geoMap");

                            if (config.map && config.map.crs) {
                                switch (config.map.crs) {
                                    case "EPSG3395":
                                        modules.addresses.houses.map.options.crs = L.CRS.EPSG3395;
                                        break;
                                    case "EPSG3857":
                                        modules.addresses.houses.map.options.crs = L.CRS.EPSG3857;
                                        break;
                                }
                            }

                            let
                                lat = (config.map && config.map.default && config.map.default.lat) ? config.map.default.lat : 51.505,
                                lon = (config.map && config.map.default && config.map.default.lon) ? config.map.default.lon : -0.09,
                                zoom = (config.map && config.map.default && config.map.default.zoom) ? config.map.default.zoom : 13
                            ;

                            L.tileLayer((config.map && config.map.tile) ? config.map.tile : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                minZoom: (config.map && config.map.min) ? config.map.min : 4,
                                maxZoom: (config.map && config.map.max) ? config.map.max : 18,
                            }).addTo(modules.addresses.houses.map);

                            modules.addresses.houses.map.setView([lat, lon], zoom);
                            modules.addresses.houses.marker = L.marker([lat, lon], { draggable: true }).addTo(modules.addresses.houses.map);

                            modules.addresses.houses.map.addControl(new L.Control.Fullscreen({
                                title: {
                                    'false': i18n("fullscreen"),
                                    'true': i18n("exitFullscreen"),
                                }
                            }));

                            modules.addresses.houses.marker.on('dragend', () => {
                                $("#" + prefix + "geo").val(modules.addresses.houses.marker.getLatLng().lat + "," + modules.addresses.houses.marker.getLatLng().lng);
                            });

                            if (config.map && config.map.hideAttribution) {
                                $(".leaflet-control-attribution").hide();
                            }
                        },
                        tabActivate: function (prefix, tab) {
                            if (tab == i18n("addresses.map")) {
                                console.log(1);
                                modules.addresses.houses.map.invalidateSize();
                            }
                        },
                        callback: result => {
                            let g = result.geo.split(",");
                            result.lat = g[0];
                            result.lon = g[1];
                            if (!parseInt(result.shared)) {
                                result.prefix = 0;
                            }
                            result.houseId = houseId;
                            result.altCamerasIds = [];
                            result.altCamerasIds[1] = result.altCameraId1;
                            result.altCamerasIds[2] = result.altCameraId2;
                            result.altCamerasIds[3] = result.altCameraId3;
                            result.altCamerasIds[4] = result.altCameraId4;
                            result.altCamerasIds[5] = result.altCameraId5;
                            result.altCamerasIds[6] = result.altCameraId6;
                            result.altCamerasIds[7] = result.altCameraId7;
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
                    id: "0",
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

        function realAddFlat(houseId) {
            let entrances = [];
            let prefx = md5(guid());

            for (let i in modules.addresses.houses.meta.entrances) {
                if (parseInt(modules.addresses.houses.meta.entrances[i].domophoneOutput) === 0 && parseInt(modules.addresses.houses.meta.entrances[i].shared) === 0) {
                    let inputs = `<div class="row mt-2 ${prefx}" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" style="display: none; margin-right: 0px!important;">`;
                    inputs += `
                        <div class="col" style="padding-right: 0px!important;">
                            <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartment")}">
                        </div>
                    `;
                    if (modules.addresses.houses.meta.entrances[i].cms.toString() !== "0") {
                        inputs += `
                            <div class="col" style="padding-right: 0px!important;">
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

            let fields = [
                {
                    id: "floor",
                    type: "text",
                    title: i18n("addresses.floor"),
                    tab: i18n("addresses.primary"),
                    placeholder: i18n("addresses.floor"),
                },
                {
                    id: "flat",
                    type: "text",
                    title: i18n("addresses.flat"),
                    tab: i18n("addresses.primary"),
                    placeholder: i18n("addresses.flat"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "code",
                    type: "text",
                    title: i18n("addresses.addCode"),
                    tab: i18n("addresses.primary"),
                    placeholder: i18n("addresses.addCode"),
                    value: md5(guid()),
                },
                {
                    id: "entrances",
                    type: "multiselect",
                    title: i18n("addresses.entrances"),
                    tab: i18n("addresses.primary"),
                    hidden: entrances.length <= 0,
                    options: entrances,
                    allButtons: false,
                },
                {
                    id: "manualBlock",
                    type: "select",
                    title: i18n("addresses.manualBlock"),
                    tab: i18n("addresses.primary"),
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
                    tab: i18n("addresses.primary"),
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
                    tab: i18n("addresses.primary"),
                    placeholder: i18n("addresses.openCodeTemplate"),
                    validate: v => {
                        return ((parseInt(v) >= 10001 && parseInt(v) <= 99999) || v === '') ? true : i18n("addresses.openCodeError");
                    },
                    button: {
                        "class": "fas fa-magic",
                        click: prefix => {
                            $("#" + prefix + "openCode").val(Math.floor(Math.random() * (99999 - 10001) + 10001));
                        }
                    },
                },
                {
                    id: "plog",
                    type: "select",
                    title: i18n("addresses.plog"),
                    tab: i18n("addresses.primary"),
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
                    type: "datetime-local",
                    sec: true,
                    title: i18n("addresses.autoOpen"),
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "whiteRabbit",
                    type: "select",
                    title: i18n("addresses.whiteRabbit"),
                    tab: i18n("addresses.primary"),
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
                    tab: i18n("addresses.primary"),
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
                            $("#" + prefix + "sipPassword-container").attr("data-form-runtime-hide", "0").show();
                        } else {
                            $("#" + prefix + "sipPassword-container").attr("data-form-runtime-hide", "1").hide();
                        }
                    },
                },
                {
                    id: "sipPassword",
                    type: "text",
                    title: i18n("addresses.sipPassword"),
                    tab: i18n("addresses.primary"),
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
            ];

            if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                for (let i in modules.addresses.houses.customFieldsConfiguration.flat) {
                    let cf;
                    if (parseInt(modules.addresses.houses.customFieldsConfiguration.flat[i].add)) {
                        switch (modules.addresses.houses.customFieldsConfiguration.flat[i].type) {
                            case "text":
                                cf = {};
                                cf.id = "_cf_" + modules.addresses.houses.customFieldsConfiguration.flat[i].field;
                                cf.type = modules.addresses.houses.customFieldsConfiguration.flat[i].editor;
                                cf.title = modules.addresses.houses.customFieldsConfiguration.flat[i].fieldDisplay;
                                cf.placeholder = modules.addresses.houses.customFieldsConfiguration.flat[i].fieldDisplay;
                                cf.tab = i18n("customFields");
                                if (modules.addresses.houses.customFieldsConfiguration.flat[i].magicIcon && modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction && modules.custom && modules.custom[modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction]) {
                                    cf.button = {};
                                    cf.button.click = modules.custom[modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction];
                                    cf.button.class = modules.addresses.houses.customFieldsConfiguration.flat[i].magicIcon;
                                    if (modules.addresses.houses.customFieldsConfiguration.flat[i].magicHint) {
                                        cf.button.hint = modules.addresses.houses.customFieldsConfiguration.flat[i].magicHint;
                                    }
                                }
                                fields.push(cf);
                                break;
                        }
                    }
                }
            }

            loadingDone();

            cardForm({
                title: i18n("addresses.addFlat"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("add"),
                size: "lg",
                fields: fields,
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
        }


        if (AVAIL("houses", "customFieldsConfiguration")) {
            loadingStart();
            GET("houses", "customFieldsConfiguration").done(r => {
                modules.addresses.houses.customFieldsConfiguration = r.customFieldsConfiguration;
                realAddFlat(houseId);
            }).fail(FAIL);
        } else {
            realAddFlat(houseId);
        }
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
                let comments = $.trim(response.cameras.cameras[i].comments);
                let name = $.trim(response.cameras.cameras[i].name);
                let text = "";
                if (name && comments) {
                    text = name + " (" + comments + ") [" + url.host + "]";
                } else
                if (name && !comments) {
                    text = name + " [" + url.host + "]";
                } else
                if (!name && comments) {
                    text = comments + " [" + url.host + "]";
                } else {
                    text = url.host;
                }
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text: text,
                });
            }

            GET("houses", "domophones").
            done(response => {
                modules.addresses.houses.meta.domophones = response.domophones;
                modules.addresses.houses.meta.domophoneModelsById = {};

                let entrance = false;

                for (let i in modules.addresses.houses.meta.entrances) {
                    if (modules.addresses.houses.meta.entrances[i].entranceId == entranceId) {
                        entrance = modules.addresses.houses.meta.entrances[i];
                        break;
                    }
                }

                if (entrance) {
                    let domophones = [];

                    for (let i in response.domophones.domophones) {
                        modules.addresses.houses.meta.domophoneModelsById[response.domophones.domophones[i].domophoneId] = response.domophones.domophones[i].model;
                    }

                    if (!modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId]) {
                        domophones.push({
                            id: "0",
                            text: i18n("no"),
                        });
                    }

                    for (let i in response.domophones.domophones) {
                        let url;
                        try {
                            url = new URL(response.domophones.domophones[i].url);
                        } catch (e) {
                            url = {
                                host: response.domophones.domophones[i].url,
                            }
                        }
                        let comments = $.trim(response.domophones.domophones[i].comments);
                        let name = $.trim(response.domophones.domophones[i].name);
                        let text = "";
                        if (name && comments) {
                            text = name + " (" + comments + ") [" + url.host + "]";
                        } else
                        if (name && !comments) {
                            text = name + " [" + url.host + "]";
                        } else
                        if (!name && comments) {
                            text = comments + " [" + url.host + "]";
                        } else {
                            text = url.host;
                        }
                        domophones.push({
                            id: response.domophones.domophones[i].domophoneId,
                            text: text,
                        });
                    }

                    let pathFirst = true;
                    let treeName;

                    switch (config.camTree) {
                        case "perHouse":
                            treeName = "house" + houseId;
                            break;

                        default:
                            treeName = "houses";
                            break;
                    }

                    function path(node, cb) {
                        let nodeId;

                        if (pathFirst && entrance.path) {
                            nodeId = entrance.path;
                        } else {
                            nodeId = (node.id === "#") ? treeName : node.id;
                        }

                        QUERYID("houses", "path", nodeId, pathFirst ? {
                            withParents: true,
                            tree: treeName,
                        } : null).
                        done(result => {
                            if (result && result.tree) {
                                cb(result.tree);
                            } else {
                                cb([]);
                            }
                        }).
                        fail(FAIL).
                        fail(() => {
                            cb([]);
                        });

                        pathFirst = false;
                    }

                    cardForm({
                        title: i18n("addresses.editEntrance"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        apply: i18n("edit"),
                        delete: i18n("addresses.deleteEntrance"),
                        deleteTab: i18n("addresses.primary"),
                        size: "lg",
                        fields: [
                            {
                                id: "entranceId",
                                type: "text",
                                title: i18n("addresses.entranceId"),
                                tab: i18n("addresses.primary"),
                                value: entranceId,
                                readonly: true,
                            },
                            {
                                id: "entranceType",
                                type: "select",
                                title: i18n("addresses.entranceType"),
                                tab: i18n("addresses.primary"),
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
                                tab: i18n("addresses.primary"),
                                placeholder: i18n("addresses.entrance"),
                                validate: v => {
                                    return $.trim(v) !== "";
                                },
                                value: entrance.entrance,
                            },
                            {
                                id: "geo",
                                type: "text",
                                title: i18n("addresses.geo"),
                                tab: i18n("addresses.primary"),
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
                                tab: i18n("addresses.primary"),
                                value: entrance.callerId,
                                validate: v => {
                                    return $.trim(v) !== "";
                                },
                            },
                            {
                                id: "cameraId",
                                type: "select2",
                                title: i18n("addresses.cameraId"),
                                tab: i18n("addresses.cameras"),
                                value: entrance.cameraId,
                                options: cameras,
                            },
                            {
                                id: "altCameraId1",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "1",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId1,
                            },
                            {
                                id: "altCameraId2",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "2",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId2,
                            },
                            {
                                id: "altCameraId3",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "3",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId3,
                            },
                            {
                                id: "altCameraId4",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "4",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId4,
                            },
                            {
                                id: "altCameraId5",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "5",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId5,
                            },
                            {
                                id: "altCameraId6",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "6",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId6,
                            },
                            {
                                id: "altCameraId7",
                                type: "select2",
                                title: i18n("addresses.altCameraId") + "7",
                                options: cameras,
                                tab: i18n("addresses.cameras"),
                                value: entrance.altCameraId7,
                            },
                            {
                                id: "domophoneId",
                                type: "select2",
                                title: i18n("domophone"),
                                tab: i18n("addresses.primary"),
                                value: modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId] ? entrance.domophoneId : "0",
                                options: domophones,
                                select: modules.addresses.houses.domophoneIdSelect,
                                validate: v => {
                                    return parseInt(v) > 0;
                                },
                            },
                            {
                                id: "domophoneOutput",
                                type: "select",
                                title: i18n("addresses.domophoneOutput"),
                                tab: i18n("addresses.primary"),
                                placeholder: i18n("addresses.domophoneOutput"),
                                options: modules.addresses.houses.outputs(modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId], entrance.domophoneOutput),
                            },
                            {
                                id: "cms",
                                type: "select2",
                                title: i18n("addresses.cms"),
                                tab: i18n("addresses.primary"),
                                placeholder: i18n("addresses.cms"),
                                options: modules.addresses.houses.cmses(modules.addresses.houses.meta.domophoneModelsById[entrance.domophoneId]),
                                value: entrance.cms,
                                select: modules.addresses.houses.cmsSelect,
                            },
                            {
                                id: "cmsType",
                                type: "select",
                                title: i18n("addresses.cmsType"),
                                tab: i18n("addresses.primary"),
                                value: entrance.cmsType,
                                hidden: parseInt(entrance.cms) === 0,
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
                                tab: i18n("addresses.primary"),
                                placeholder: i18n("addresses.cmsLevelsOrder"),
                                value: entrance.cmsLevels,
                                hidden: parseInt(entrance.cms) === 0,
                            },
                            {
                                id: "shared",
                                type: "select",
                                title: i18n("addresses.shared"),
                                tab: i18n("addresses.primary"),
                                hidden: parseInt(entrance.cms) !== 0,
                                value: entrance.shared.toString(),
                                readonly: parseInt(entrance.installed) > 1,
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
                                        $("#" + prefix + "prefix-container").attr("data-form-runtime-hide", "0").show();
                                    } else {
                                        $("#" + prefix + "prefix-container").attr("data-form-runtime-hide", "1").hide();
                                    }
                                },
                            },
                            {
                                id: "plog",
                                type: "select",
                                title: i18n("addresses.plog"),
                                tab: i18n("addresses.primary"),
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
                                tab: i18n("addresses.primary"),
                                placeholder: i18n("addresses.prefix"),
                                value: entrance.prefix ? entrance.prefix.toString() : "0",
                                hidden: !parseInt(entrance.shared),
                                validate: (v, prefix) => {
                                    return !parseInt($("#" + prefix + "shared").val()) || parseInt(v) >= 1;
                                },
                            },
                            {
                                id: "path",
                                type: "jstree",
                                title: false,
                                tab: i18n("addresses.path"),
                                data: path,

                                addRoot: function (instance) {
                                    POST("houses", "path", treeName, {
                                        text: i18n("addresses.newNode"),
                                    }).done(result => {
                                        if (result && result.nodeId) {
                                            let node = {
                                                id: result.nodeId,
                                                text: i18n("addresses.newNode"),
                                            };
                                            instance.jstree().create_node("#", node, 'last', newNode => {
                                                setTimeout(() => {
                                                    instance.jstree().deselect_all();
                                                    instance.jstree().select_node(newNode);
                                                    instance.jstree().edit(newNode);
                                                }, 100);
                                            });
                                        }
                                    }).fail(FAIL);
                                },

                                add: function (instance) {
                                    let parent = instance.jstree().get_selected();
                                    parent = parent.length ? parent[0] : "#";
                                    POST("houses", "path", (parent === "#") ? treeName : parent, {
                                        text: i18n("addresses.newNode"),
                                    }).done(result => {
                                        if (result && result.nodeId) {
                                            let node = {
                                                id: result.nodeId,
                                                text: i18n("addresses.newNode"),
                                            };
                                            modules.addresses.houses.pathNodes[result.nodeId] = i18n("addresses.newNode");
                                            instance.jstree().create_node(parent, node, 'last', newNode => {
                                                setTimeout(() => {
                                                    instance.jstree().deselect_all();
                                                    instance.jstree().select_node(newNode);
                                                    instance.jstree().edit(newNode);
                                                }, 100);
                                            });
                                        }
                                    }).fail(FAIL);
                                },

                                rename: function (instance) {
                                    let node = instance.jstree().get_selected();
                                    if (node && node.length) {
                                        node = instance.jstree().get_node(node[0]);
                                        modules.addresses.houses.pathNodes[node.id] = node.text;
                                        setTimeout(() => {
                                            instance.jstree().edit(node);
                                        }, 100);
                                    }
                                },

                                renamed: function (e, data) {
                                    if (data && data.obj && data.obj.id && data.text && data.text != modules.addresses.houses.pathNodes[data.obj.id]) {
                                        PUT("houses", "path", data.obj.id, {
                                            text: data.text,
                                        }).
                                        done(() => {
                                            modules.addresses.houses.pathNodes[data.obj.id] = data.text;
                                        }).
                                        fail(FAIL);
                                    }
                                },

                                delete: function (instance) {
                                    let node = instance.jstree().get_selected();
                                    if (node && node.length) {
                                        node = instance.jstree().get_node(node[0]);
                                        mConfirm(i18n("addresses.confirmDeleteNode", escapeHTML(node.text)), i18n("confirm"), `danger:${i18n("addresses.deleteNode")}`, () => {
                                            DELETE("houses", "path", node.id).
                                            done(() => {
                                                instance.jstree().delete_node(node.id);
                                            }).
                                            fail(FAIL);
                                        });
                                    }
                                },

                                search: function (instance, str) {
                                    if (str) {
                                        QUERYID("houses", "path", treeName, { search: str }).
                                        done(result => {
                                            instance.jstree().settings.core.data = result.tree;
                                            instance.jstree().refresh();
                                            setTimeout(() => {
                                                instance.jstree().search(str);
                                                instance.jstree().settings.core.data = path;
                                            }, 100);
                                        }).
                                        fail(FAIL);
                                    } else {
                                        instance.jstree().clear_search();

                                        QUERYID("houses", "path", entrance.path ? entrance.path : treeName, {
                                            withParents: true,
                                            tree: treeName,
                                        }).
                                        done(result => {
                                            if (result && result.tree) {
                                                instance.jstree().settings.core.data = result.tree;
                                            } else {
                                                instance.jstree().settings.core.data = [];
                                            }
                                            instance.jstree().refresh();
                                        }).
                                        fail(FAIL);

                                        setTimeout(() => {
                                            instance.jstree().select_node(entrance.path);
                                            instance.jstree().settings.core.data = path;
                                        }, 100);
                                    }
                                }
                            },
                            {
                                id: "geoSuggestion",
                                type: "select2",
                                title: false,
                                placeholder: i18n("addresses.address"),
                                tab: i18n("addresses.map"),
                                hidden: !AVAIL("geo", "suggestions") || !modules.map,
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
                                                modules.addresses.houses.fiases[data.suggestions[i].data.house_fias_id] = data.suggestions[i].data;
                                            }
                                        }
                                        return {
                                            results: suggestions,
                                        };
                                    },
                                },
                            },
                            {
                                id: "geoMap",
                                type: "empty",
                                title: false,
                                placeholder: i18n("search"),
                                tab: i18n("addresses.map"),
                                noHover: true,
                            },
                        ],
                        done: function (prefix) {
                            $("#" + prefix + "geoSuggestion").off("change").on("change", e => {
                                let fias = $("#" + prefix + "geoSuggestion").val();
                                if (modules.addresses.houses.fiases[fias] && modules.addresses.houses.fiases[fias].geo_lat && modules.addresses.houses.fiases[fias].geo_lon) {
                                    modules.addresses.houses.map.setView([modules.addresses.houses.fiases[fias].geo_lat, modules.addresses.houses.fiases[fias].geo_lon], 18);
                                    modules.addresses.houses.marker.setLatLng([modules.addresses.houses.fiases[fias].geo_lat, modules.addresses.houses.fiases[fias].geo_lon]).update();
                                    $("#" + prefix + "geo").val(modules.addresses.houses.marker.getLatLng().lat + "," + modules.addresses.houses.marker.getLatLng().lng);
                                }
                            });

                            $("#" + prefix + "geoMap").css("height", "400px");

                            modules.addresses.houses.map = L.map(prefix + "geoMap");

                            if (config.map && config.map.crs) {
                                switch (config.map.crs) {
                                    case "EPSG3395":
                                        modules.addresses.houses.map.options.crs = L.CRS.EPSG3395;
                                        break;
                                    case "EPSG3857":
                                        modules.addresses.houses.map.options.crs = L.CRS.EPSG3857;
                                        break;
                                }
                            }

                            let
                                lat = (config.map && config.map.default && config.map.default.lat) ? config.map.default.lat : 51.505,
                                lon = (config.map && config.map.default && config.map.default.lon) ? config.map.default.lon : -0.09,
                                zoom = (config.map && config.map.default && config.map.default.zoom) ? config.map.default.zoom : 13
                            ;

                            if (parseFloat(entrance.lat) && parseFloat(entrance.lon)) {
                                lat = entrance.lat;
                                lon = entrance.lon;
                                zoom = 18;
                            }

                            L.tileLayer((config.map && config.map.tile) ? config.map.tile : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                minZoom: (config.map && config.map.min) ? config.map.min : 4,
                                maxZoom: (config.map && config.map.max) ? config.map.max : 18,
                            }).addTo(modules.addresses.houses.map);

                            modules.addresses.houses.map.setView([lat, lon], zoom);
                            modules.addresses.houses.marker = L.marker([lat, lon], { draggable: true }).addTo(modules.addresses.houses.map);

                            modules.addresses.houses.map.addControl(new L.Control.Fullscreen({
                                title: {
                                    'false': i18n("fullscreen"),
                                    'true': i18n("exitFullscreen"),
                                }
                            }));

                            modules.addresses.houses.marker.on('dragend', () => {
                                $("#" + prefix + "geo").val(modules.addresses.houses.marker.getLatLng().lat + "," + modules.addresses.houses.marker.getLatLng().lng);
                            });

                            if (config.map && config.map.hideAttribution) {
                                $(".leaflet-control-attribution").hide();
                            }
                        },
                        tabActivate: function (prefix, tab) {
                            if (tab == i18n("addresses.map")) {
                                modules.addresses.houses.map.invalidateSize();
                            }
                        },
                        callback: result => {
                            if (result.delete === "yes") {
                                modules.addresses.houses.deleteEntrance(entranceId, parseInt(entrance.shared) && parseInt(entrance.installed) > 1, houseId);
                            } else {
                                let g = result.geo.split(",");
                                result.lat = g[0];
                                result.lon = g[1];
                                if (!parseInt(result.shared)) {
                                    result.prefix = 0;
                                }
                                result.entranceId = entranceId;
                                result.houseId = houseId;
                                result.altCamerasIds = [];
                                result.altCamerasIds[1] = result.altCameraId1;
                                result.altCamerasIds[2] = result.altCameraId2;
                                result.altCamerasIds[3] = result.altCameraId3;
                                result.altCamerasIds[4] = result.altCameraId4;
                                result.altCamerasIds[5] = result.altCameraId5;
                                result.altCamerasIds[6] = result.altCameraId6;
                                result.altCamerasIds[7] = result.altCameraId7;
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

    modifyCamera: function (cameraId, houseId) {
        loadingStart();
        modules.addresses.houses.loadHouse(houseId, () => {

            let pathFirst = true;
            let treeName;

            switch (config.camTree) {
                case "perHouse":
                    treeName = "house" + houseId;
                    break;

                default:
                    treeName = "houses";
                    break;
            }

            let camera;

            for (let i in modules.addresses.houses.meta.cameras) {
                if (modules.addresses.houses.meta.cameras[i].cameraId == cameraId) {
                    camera = modules.addresses.houses.meta.cameras[i];
                }
            }

            function path(node, cb) {
                let nodeId;

                if (pathFirst && camera.path) {
                    nodeId = camera.path;
                } else {
                    nodeId = (node.id === "#") ? treeName : node.id;
                }

                QUERYID("houses", "path", nodeId, pathFirst ? {
                    withParents: true,
                    tree: treeName,
                } : null).
                done(result => {
                    if (result && result.tree) {
                        cb(result.tree);
                    } else {
                        cb([]);
                    }
                }).
                fail(FAIL).
                fail(() => {
                    cb([]);
                });

                pathFirst = false;
            }

            cardForm({
                title: i18n("addresses.path"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                size: "lg",
                fields: [
                    {
                        id: "path",
                        type: "jstree",
                        title: false,
                        tab: i18n("addresses.path"),
                        data: path,

                        addRoot: function (instance) {
                            POST("houses", "path", treeName, {
                                text: i18n("addresses.newNode"),
                            }).done(result => {
                                if (result && result.nodeId) {
                                    let node = {
                                        id: result.nodeId,
                                        text: i18n("addresses.newNode"),
                                    };
                                    instance.jstree().create_node("#", node, 'last', newNode => {
                                        setTimeout(() => {
                                            instance.jstree().deselect_all();
                                            instance.jstree().select_node(newNode);
                                            instance.jstree().edit(newNode);
                                        }, 100);
                                    });
                                }
                            }).fail(FAIL);
                        },

                        add: function (instance) {
                            let parent = instance.jstree().get_selected();
                            parent = parent.length ? parent[0] : "#";
                            POST("houses", "path", (parent === "#") ? treeName : parent, {
                                text: i18n("addresses.newNode"),
                            }).done(result => {
                                if (result && result.nodeId) {
                                    let node = {
                                        id: result.nodeId,
                                        text: i18n("addresses.newNode"),
                                    };
                                    modules.addresses.houses.pathNodes[result.nodeId] = i18n("addresses.newNode");
                                    instance.jstree().create_node(parent, node, 'last', newNode => {
                                        setTimeout(() => {
                                            instance.jstree().deselect_all();
                                            instance.jstree().select_node(newNode);
                                            instance.jstree().edit(newNode);
                                        }, 100);
                                    });
                                }
                            }).fail(FAIL);
                        },

                        rename: function (instance) {
                            let node = instance.jstree().get_selected();
                            if (node && node.length) {
                                node = instance.jstree().get_node(node[0]);
                                modules.addresses.houses.pathNodes[node.id] = node.text;
                                setTimeout(() => {
                                    instance.jstree().edit(node);
                                }, 100);
                            }
                        },

                        renamed: function (e, data) {
                            if (data && data.obj && data.obj.id && data.text && data.text != modules.addresses.houses.pathNodes[data.obj.id]) {
                                PUT("houses", "path", data.obj.id, {
                                    text: data.text,
                                }).
                                done(() => {
                                    modules.addresses.houses.pathNodes[data.obj.id] = data.text;
                                }).
                                fail(FAIL);
                            }
                        },

                        delete: function (instance) {
                            let node = instance.jstree().get_selected();
                            if (node && node.length) {
                                node = instance.jstree().get_node(node[0]);
                                mConfirm(i18n("addresses.confirmDeleteNode", escapeHTML(node.text)), i18n("confirm"), `danger:${i18n("addresses.deleteNode")}`, () => {
                                    DELETE("houses", "path", node.id).
                                    done(() => {
                                        instance.jstree().delete_node(node.id);
                                    }).
                                    fail(FAIL);
                                });
                            }
                        },

                        search: function (instance, str) {
                            if (str) {
                                QUERYID("houses", "path", treeName, { search: str }).
                                done(result => {
                                    instance.jstree().settings.core.data = result.tree;
                                    instance.jstree().refresh();
                                    setTimeout(() => {
                                        instance.jstree().search(str);
                                        instance.jstree().settings.core.data = path;
                                    }, 100);
                                }).
                                fail(FAIL);
                            } else {
                                instance.jstree().clear_search();

                                QUERYID("houses", "path", camera.path ? camera.path : treeName, {
                                    withParents: true,
                                    tree: treeName,
                                }).
                                done(result => {
                                    if (result && result.tree) {
                                        instance.jstree().settings.core.data = result.tree;
                                    } else {
                                        instance.jstree().settings.core.data = [];
                                    }
                                    instance.jstree().refresh();
                                }).
                                fail(FAIL);

                                setTimeout(() => {
                                    instance.jstree().select_node(camera.path);
                                    instance.jstree().settings.core.data = path;
                                }, 100);
                            }
                        }
                    }
                ],
                callback: result => {
                    PUT("houses", "cameras", false, {
                        houseId: houseId,
                        cameraId: cameraId,
                        path: result.path,
                    }).
                    fail(FAIL).
                    done(() => {
                        message(i18n("addresses.cameraWasChanged"));
                    }).
                    always(() => {
                        modules.addresses.houses.renderHouse(houseId);
                    });
                },
            });
            loadingDone();
        });
    },

    modifyFlat: function (flatId, houseId, canDelete) {

        function realModifyFlat(flatId, houseId, canDelete, customFields) {
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
                        let inputs = `<div class="row mt-2 ${prefx}" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" style="display: none; margin-right: 0px!important;">`;
                        inputs += `
                            <div class="col" style="padding-right: 0px!important;">
                                <input type="text" class="form-control form-control-sm ${prefx}-apartment" data-entrance-id="${modules.addresses.houses.meta.entrances[i].entranceId}" placeholder="${i18n("addresses.apartment")}" value="${entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId] ? entrances_settings[modules.addresses.houses.meta.entrances[i].entranceId].apartment : ""}">
                            </div>
                        `;
                        if (modules.addresses.houses.meta.entrances[i].cms.toString() !== "0") {
                            inputs += `
                                <div class="col" style="padding-right: 0px!important;">
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

                let fields = [
                    {
                        id: "flatId",
                        type: "text",
                        title: i18n("addresses.flatId"),
                        value: flatId,
                        readonly: true,
                        tab: i18n("addresses.primary"),
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
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "contract",
                        type: "text",
                        title: i18n("addresses.contract"),
                        placeholder: i18n("addresses.contract"),
                        value: flat.contract,
                    },
                    {
                        id: "login",
                        type: "text",
                        title: i18n("addresses.login"),
                        placeholder: i18n("addresses.login"),
                        value: flat.login,
                    },
                    {
                        id: "password",
                        type: "text",
                        title: i18n("addresses.password"),
                        placeholder: i18n("addresses.password"),
                        value: flat.password,
                    },
                    {
                        id: "code",
                        type: "text",
                        title: i18n("addresses.addCode"),
                        placeholder: i18n("addresses.addCode"),
                        value: flat.code,
                        tab: i18n("addresses.primary"),
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
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "entrances",
                        type: "multiselect",
                        title: i18n("addresses.entrances"),
                        hidden: entrances.length <= 0,
                        options: entrances,
                        value: entrances_selected,
                        allButtons: false,
                        tab: i18n("addresses.primary"),
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
                        ],
                        tab: i18n("addresses.primary"),
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
                        ],
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "autoBlock",
                        type: "select",
                        title: i18n("addresses.autoBlock"),
                        placeholder: i18n("addresses.autoBlock"),
                        value: flat.autoBlock,
                        readonly: true,
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
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "openCode",
                        type: "text",
                        title: i18n("addresses.openCode"),
                        placeholder: i18n("addresses.openCodeTemplate"),
                        value: flat.openCode,
                        validate: v => {
                            return ((parseInt(v) >= 10001 && parseInt(v) <= 99999) || v === '') ? true : i18n("addresses.openCodeError");
                        },
                        tab: i18n("addresses.primary"),
                        button: {
                            "class": "fas fa-magic",
                            click: prefix => {
                                $("#" + prefix + "openCode").val(Math.floor(Math.random() * (99999 - 10001) + 10001));
                            }
                        },
                    },
                    {
                        id: "autoOpen",
                        type: "datetime-local",
                        sec: true,
                        title: i18n("addresses.autoOpen"),
                        value: flat.autoOpen,
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
                        ],
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
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "sipEnabled",
                        type: "select",
                        title: i18n("addresses.sipEnabled"),
                        placeholder: i18n("addresses.sipEnabled"),
                        value: flat.sipEnabled,
                        hint: parseInt(4000000000) + parseInt(flatId),
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
                                $("#" + prefix + "sipPassword-container").attr("data-form-runtime-hide", "0").show();
                            } else {
                                $("#" + prefix + "sipPassword-container").attr("data-form-runtime-hide", "1").hide();
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
                    {
                        id: "cars",
                        type: "area",
                        title: false,
                        noHover: true,
                        placeholder: i18n("addresses.carsNumbers"),
                        value: flat.cars,
                        tab: i18n("addresses.cars"),
                    },
                    {
                        id: "subscribersLimit",
                        type: "text",
                        title: i18n("addresses.subscribersLimit"),
                        placeholder: i18n("addresses.subscribersLimit"),
                        validate: a => {
                            return !!(!a || parseInt(a) > 0);
                        },
                        value: (parseInt(flat.subscribersLimit) > 0) ? flat.subscribersLimit : '',
                    },
                ];

                if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                    for (let i in modules.addresses.houses.customFieldsConfiguration.flat) {
                        let cf;
                        if (parseInt(modules.addresses.houses.customFieldsConfiguration.flat[i].modify)) {
                            switch (modules.addresses.houses.customFieldsConfiguration.flat[i].type) {
                                case "text":
                                case "button":
                                    cf = {};
                                    cf.id = "_cf_" + modules.addresses.houses.customFieldsConfiguration.flat[i].field;
                                    cf.type = modules.addresses.houses.customFieldsConfiguration.flat[i].editor;
                                    cf.title = modules.addresses.houses.customFieldsConfiguration.flat[i].fieldDisplay;
                                    cf.placeholder = modules.addresses.houses.customFieldsConfiguration.flat[i].fieldDisplay;
                                    cf.tab = modules.addresses.houses.customFieldsConfiguration.flat[i].tab ? modules.addresses.houses.customFieldsConfiguration.flat[i].tab : i18n("customFields");
                                    cf.value = customFields[modules.addresses.houses.customFieldsConfiguration.flat[i].field] ? customFields[modules.addresses.houses.customFieldsConfiguration.flat[i].field] : "";
                                    if (modules.addresses.houses.customFieldsConfiguration.flat[i].magicIcon && modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction && modules.custom && modules.custom[modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction]) {
                                        cf.button = {};
                                        cf.button.click = modules.custom[modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction];
                                        cf.button.class = modules.addresses.houses.customFieldsConfiguration.flat[i].magicClass;
                                        if (modules.addresses.houses.customFieldsConfiguration.flat[i].magicHint) {
                                            cf.button.hint = modules.addresses.houses.customFieldsConfiguration.flat[i].magicHint;
                                        }
                                    }
                                    if (modules.addresses.houses.customFieldsConfiguration.flat[i].type == "button") {
                                        cf.button = {};
                                        cf.button.hint = modules.addresses.houses.customFieldsConfiguration.flat[i].magicHint;
                                        cf.button.click = modules.custom[modules.addresses.houses.customFieldsConfiguration.flat[i].magicFunction];
                                        cf.button.class = modules.addresses.houses.customFieldsConfiguration.flat[i].magicClass;
                                    }
                                    fields.push(cf);
                                    break;
                            }
                        }
                    }
                }

                loadingDone();

                cardForm({
                    title: i18n("addresses.editFlat"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    delete: canDelete ? (houseId ? i18n("addresses.deleteFlat") : false) : false,
                    deleteTab: canDelete ? (houseId ? i18n("addresses.primary") : false) : false,
                    apply: i18n("edit"),
                    size: "lg",
                    fields: fields,
                    callback: result => {
                        let cf = {};
                        if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                            for (let i in modules.addresses.houses.customFieldsConfiguration.flat) {
                                switch (modules.addresses.houses.customFieldsConfiguration.flat[i].type) {
                                    case "text":
                                        let id = "_cf_" + modules.addresses.houses.customFieldsConfiguration.flat[i].field;
                                        cf[id.substring(4)] = result[id];
                                        delete result[id];
                                        break;
                                }
                            }
                        }
                        delete result.autoBlock;
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
                            modules.addresses.houses.doModifyFlat(result, true).done(() => {
                                if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                                    PUT("houses", "customFields", "flat", {
                                        id: flatId,
                                        customFields: cf,
                                    }).
                                    fail(FAIL);
                                }
                            });
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
        }

        if (AVAIL("houses", "customFieldsConfiguration")) {
            loadingStart();
            GET("houses", "customFieldsConfiguration").done(r => {
                modules.addresses.houses.customFieldsConfiguration = r.customFieldsConfiguration;
                if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                    QUERYID("houses", "customFields", "flat", { id: flatId }, true).done(r => {
                        realModifyFlat(flatId, houseId, canDelete, r.customFields);
                    }).fail(FAIL);
                } else {
                    realModifyFlat(flatId, houseId, canDelete);
                }
            }).fail(FAIL);
        } else {
            if (modules.addresses.houses.customFieldsConfiguration && modules.addresses.houses.customFieldsConfiguration.flat) {
                loadingStart();
                QUERYID("houses", "customFields", "flat", { id: flatId }, true).done(r => {
                    realModifyFlat(flatId, houseId, canDelete, r.customFields);
                }).fail(FAIL);
            } else {
                realModifyFlat(flatId, houseId, canDelete);
            }
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
        modules.addresses.houses.houseId = "0";
        modules.addresses.houses.settlementId = "0";
        modules.addresses.houses.streetId = "0";

        function favorite() {
            let f = false;
            for (let i in modules.addresses.favorites) {
                if (modules.addresses.favorites[i].object == "house" && modules.addresses.favorites[i].id == houseId) {
                    f = true;
                    break;
                }
            }
            if (f) {
                return `<span style='position: absolute; right: 0px;' class='mr-3' onclick='modules.addresses.toggleFavorite("house", ${houseId})'><i class='fas fa-fw fa-bookmark text-primary pointer addressFavoriteIcon' data-object='house' data-object-id='${houseId}'></i></span>`;
            } else {
                return `<span style='position: absolute; right: 0px;' class='mr-3' onclick='modules.addresses.toggleFavorite("house", ${houseId})'><i class='far fa-fw fa-bookmark text-primary pointer addressFavoriteIcon' data-object='house' data-object-id='${houseId}'></i></span>`;
            }
        }

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
                        subTop(
                            modules.addresses.path(
                                modules.addresses.meta.houses[i].settlementId ? "settlement" : "street",
                                modules.addresses.meta.houses[i].settlementId ? modules.addresses.meta.houses[i].settlementId : modules.addresses.meta.houses[i].streetId,
                                true
                            ) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + modules.addresses.houses.meta.house.houseFull + favorite()
                        );
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

    autoconfigureDomophone: function (domophoneId, firstTime) {
        loadingStart();
        POST("houses", "autoconfigure", domophoneId, {
            object: "domophone",
            firstTime: firstTime ? "1" : "0",
        }).
        then(() => {
            message(i18n("addresses.taskQueued"));
        }).
        fail(FAIL).
        always(loadingDone);
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
                    modules.addresses.houses.modifyFlat(flatId, houseId, true);
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
                    },
                    {
                        title: i18n("addresses.contract"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    modules.addresses.houses.meta.flats.sort((a, b) => {
                        let d = parseInt(a.flat) - parseInt(b.flat);
                        if (d) {
                            return d;
                        }
                        if (a.flat > b.flat) {
                            return 1;
                        }
                        if (a.flat < b.flat) {
                            return -1
                        }
                        return 0;
                    });

                    for (let i in modules.addresses.houses.meta.flats) {
                        rows.push({
                            uid: modules.addresses.houses.meta.flats[i].flatId,
                            class: (parseInt(modules.addresses.houses.meta.flats[i].manualBlock) || parseInt(modules.addresses.houses.meta.flats[i].autoBlock) || parseInt(modules.addresses.houses.meta.flats[i].adminBlock)) ? "text-secondary" : "",
                            cols: [
                                {
                                    data: modules.addresses.houses.meta.flats[i].flatId,
                                },
                                {
                                    data: modules.addresses.houses.meta.flats[i].floor ? modules.addresses.houses.meta.flats[i].floor : "-",
                                },
                                {
                                    data: modules.addresses.houses.meta.flats[i].flat,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.houses.meta.flats[i].contract ? modules.addresses.houses.meta.flats[i].contract : "-",
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
                                                    window.location.href = "?#addresses.subscribers&flatId=" + flatId + "&houseId=" + houseId + "&flat=" + encodeURIComponent(modules.addresses.houses.meta.flats[i].flat) + "&settlementId=" + modules.addresses.houses.settlementId + "&streetId=" + modules.addresses.houses.streetId;
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

                    modules.addresses.houses.meta.entrances.sort((a, b) => {
                        let et = {
                            "entrance": 0,
                            "wicket": 1,
                            "gate": 2,
                            "barrier": 3,
                        }

                        if (et[a.entranceType] > et[b.entranceType]) {
                            return 1;
                        }
                        if (et[a.entranceType] < et[b.entranceType]) {
                            return -1
                        }
                        let d = parseInt(a.entrance) - parseInt(b.entrance);
                        if (d) {
                            return d;
                        }
                        if (a.entrance > b.entrance) {
                            return 1;
                        }
                        if (a.entrance < b.entrance) {
                            return -1
                        }
                        return 0;
                    });

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
                                        title: i18n("domophone"),
                                        disabled: ! modules.addresses.houses.meta.entrances[i].domophoneId,
                                        click: entranceId => {
                                            window.location.href = "?#addresses.domophones&domophoneId=" + entrances[entranceId].domophoneId;
                                        },
                                    },
                                    {
                                        icon: "fas fa-video",
                                        title: i18n("camera"),
                                        disabled: ! modules.addresses.houses.meta.entrances[i].cameraId,
                                        click: entranceId => {
                                            window.location.href = "?#addresses.cameras&cameraId=" + entrances[entranceId].cameraId;
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-wrench",
                                        title: i18n("addresses.autoconfigureDomophone"),
                                        click: entranceId => {
                                            modules.addresses.houses.autoconfigureDomophone(entrances[entranceId].domophoneId, false);
                                        },
                                    },
                                    {
                                        icon: "fas fa-tools",
                                        title: i18n("addresses.autoconfigureDomophoneFirstTime"),
                                        click: entranceId => {
                                            modules.addresses.houses.autoconfigureDomophone(entrances[entranceId].domophoneId, true);
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-phone-volume",
                                        title: i18n("addresses.cms"),
                                        disabled: !modules.addresses.houses.meta.entrances[i].cms || modules.addresses.houses.meta.entrances[i].cms.toString() === "0",
                                        click: entranceId => {
                                            window.location.href = "?#addresses.houses&show=cms&houseId=" + houseId + "&entranceId=" + entrances[entranceId].entranceId;
                                        },
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-key",
                                        title: i18n("addresses.keys"),
                                        click: entranceId => {
                                            window.location.href = "?#addresses.keys&query=" + entranceId + "&by=3&backStr=" + encodeURIComponent(modules.addresses.houses.meta.house.houseFull + ", " + entrances[entranceId].entrance) + "&back=" + encodeURIComponent(hashParse("hash"));
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
                    edit: cameraId => {
                        modules.addresses.houses.modifyCamera(cameraId, houseId);
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
                                        data: modules.addresses.houses.meta.cameras[i].cameraId ? modules.addresses.houses.meta.cameras[i].cameraId : i18n("addresses.deleted"),
                                        click: modules.addresses.houses.meta.cameras[i].cameraId ? ("#addresses.cameras&filter=" + modules.addresses.houses.meta.cameras[i].cameraId) : false,
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].url?modules.addresses.houses.meta.cameras[i].url:"",
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].name?modules.addresses.houses.meta.cameras[i].name:"",
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.addresses.houses.meta.cameras[i].comments ? modules.addresses.houses.meta.cameras[i].comments : "",
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
            modules.addresses.houses.meta = response.cameras;
            let cameras = [];

            for (let i in response.cameras.cameras) {
                let url;
                try {
                    url = new URL(response.cameras.cameras[i].url);
                } catch (e) {
                    url = {
                        host: response.cameras.cameras[i].url,
                    }
                }
                let comments = $.trim(response.cameras.cameras[i].comments);
                let name = $.trim(response.cameras.cameras[i].name);
                let text = "";
                if (name && comments) {
                    text = name + " (" + comments + ") [" + url.host + "]";
                } else
                if (name && !comments) {
                    text = name + " [" + url.host + "]";
                } else
                if (!name && comments) {
                    text = comments + " [" + url.host + "]";
                } else {
                    text = url.host;
                }
                cameras.push({
                    id: response.cameras.cameras[i].cameraId,
                    text: text,
                });
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
                        validate: v => {
                            return parseInt(v) > 0;
                        },
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
                            h += `<hr class="hr-text-white-large ml-3" data-content="${i}">`;
                            h += `<table class="table table-hover ml-3" style="width: 0%;">`;

                            let maxX = 0;
                            for (let j in cms.cms[i]) {
                                maxX = Math.max(maxX, cms.cms[i][j]);
                            }

                            h += `<thead>`;

                            h += `<th><button type="button" class="btn btn-primary btn-xs cms-magic" data-cms="${cmsi}" title="${i18n("addresses.cmsMagic")}"><i class="fa-fw fas fa-magic"></i></button></th>`;

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

                        h += `<button id="entranceCmsSubmit" class="btn btn-primary ml-3 mb-2 mt-2">${i18n("apply")}</button>`;

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
                                message(i18n("addresses.changesWasSaved"));
                                modules.addresses.houses.renderEntranceCMS(houseId, entranceId);
                            }).
                            fail(FAIL).
                            fail(loadingDone);
                        });

                        $(".cms-magic").off("click").on("click", function () {
                            let cms = $(this).attr("data-cms");

                            cardForm({
                                title: i18n("addresses.cmsMagic"),
                                footer: true,
                                borderless: true,
                                topApply: true,
                                apply: i18n("addresses.fill"),
                                fields: [
                                    {
                                        id: "dozenFirst",
                                        value: $(`.cmsa[data-cms='${cms}']:first`).attr("data-dozen"),
                                        title: i18n("addresses.dozenFirst"),
                                        validate: v => {
                                            return parseInt(v) >= 0;
                                        },
                                    },
                                    {
                                        id: "unitFirst",
                                        value: parseInt($(`.cmsa[data-cms='${cms}']:first`).attr("data-unit")) ? parseInt($(`.cmsa[data-cms='${cms}']:first`).attr("data-unit")) : parseInt($(`.cmsa[data-cms='${cms}']:first`).attr("data-unit")) + 1,
                                        title: i18n("addresses.unitFirst"),
                                        validate: v => {
                                            return parseInt(v) >= 0;
                                        },
                                    },
                                    {
                                        id: "apartmentFirst",
                                        title: i18n("addresses.apartmentFirst"),
                                        value: "1",
                                        validate: v => {
                                            return parseInt(v) > 0;
                                        },
                                    },
                                    {
                                        id: "apartmentFillCount",
                                        title: i18n("addresses.apartmentFillCount"),
                                        value: $(`.cmsa[data-cms='${cms}']`).length,
                                        validate: v => {
                                            return parseInt(v) > 0;
                                        },
                                    },
                                    {
                                        id: "clearFirst",
                                        title: i18n("addresses.cmsClearFirst"),
                                        type: "tristate",
                                        state0: i18n("no"),
                                        state1: i18n("addresses.thisMatrix"),
                                        state2: i18n("addresses.allMatrix"),
                                    }
                                ],
                                callback: result => {
                                    if (parseInt(result.clearFirst)) {
                                        if (parseInt(result.clearFirst) == 1) {
                                            $(`.cmsa[data-cms='${cms}']`).val("");
                                        } else {
                                            $(`.cmsa`).val("");
                                        }
                                    }
                                    let d = result.dozenFirst;
                                    let u = result.unitFirst;
                                    let a = result.apartmentFirst;
                                    let i = 0;
                                    let e = 0;
                                    while (i < result.apartmentFillCount) {
                                        let n = $(`.cmsa[data-cms='${cms}'][data-dozen='${d}'][data-unit='${u}']`);
                                        if (n.length) {
                                            n.val(a);
                                            i++;
                                            a++;
                                            u++;
                                            e = 0;
                                        } else {
                                            d++;
                                            if (!$(`.cmsa[data-cms='${cms}'][data-dozen='${d}']`).length) {
                                                d = $(`.cmsa[data-cms='${cms}']:first`).attr("data-dozen");
                                            }
                                            u = parseInt($(`.cmsa[data-cms='${cms}'][data-dozen='${d}']:first`).attr("data-unit"));
                                            e++;
                                            if (e > result.apartmentFillCount) {
                                                break;
                                            }
                                        }
                                    }
                                },
                            });
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

        if (params.show === "cms" && parseInt(params.entranceId) > 0) {
            $("#altForm").hide();

            subTop();

            modules.addresses.houses.renderEntranceCMS(params.houseId, params.entranceId);
        } else {
            modules.addresses.topMenu([{
                title: i18n("addresses.addFlatsWizard"),
                click: () => {
                    let entrances = [];

                    for (let i in modules.addresses.houses.meta.entrances) {
                        entrances.push({
                            id: modules.addresses.houses.meta.entrances[i].entranceId,
                            text: i18n("addresses.entranceType" + modules.addresses.houses.meta.entrances[i].entranceType.substring(0, 1).toUpperCase() + modules.addresses.houses.meta.entrances[i].entranceType.substring(1) + "Full") + " " + modules.addresses.houses.meta.entrances[i].entrance,
                        });
                    }

                    cardForm({
                        title: i18n("addresses.addFlats"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        size: "lg",
                        apply: i18n("add"),
                        fields: [
                            {
                                id: "firstFloor",
                                value: "1",
                                title: i18n("addresses.firstFloor"),
                                validate: v => {
                                    return parseInt(v) >= 0;
                                },
                            },
                            {
                                id: "firstFlat",
                                value: "1",
                                title: i18n("addresses.firstFlat"),
                                validate: v => {
                                    return parseInt(v) >= 0;
                                },
                            },
                            {
                                id: "flatsByFloor",
                                title: i18n("addresses.flatsByFloor"),
                                value: "1",
                                validate: v => {
                                    return parseInt(v) > 0;
                                },
                            },
                            {
                                id: "totalFlats",
                                title: i18n("addresses.totalFlats"),
                                value: "1",
                                validate: v => {
                                    return parseInt(v) > 0;
                                },
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
                                id: "openCode",
                                type: "noyes",
                                title: i18n("addresses.openCode"),
                            },
                            {
                                id: "autoOpen",
                                type: "datetime-local",
                                sec: true,
                                title: i18n("addresses.autoOpen"),
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
                        ],
                        callback: result => {
                            let flats = [];
                            let floor = parseInt(result.firstFloor);
                            let flatsByFloor = 0;
                            for (let f = parseInt(result.firstFlat); f < parseInt(result.firstFlat) + parseInt(result.totalFlats); f++) {
                                flats.push({
                                    houseId: params.houseId,
                                    floor: floor,
                                    flat: f,
                                    code: md5(guid()),
                                    entrances: result.entrances,
                                    apartmentsAndLevels: false,
                                    manualBlock: result.manualBlock,
                                    adminBlock: result.adminBlock,
                                    openCode: parseInt(result.openCode) ? "!" : "00000",
                                    plog: result.plog,
                                    autoOpen: result.autoOpen,
                                    whiteRabbit: result.whiteRabbit,
                                    sipEnabled: 0,
                                    sipPassword: "",
                                });
                                flatsByFloor++;
                                if (flatsByFloor >= parseInt(result.flatsByFloor)) {
                                    flatsByFloor = 0;
                                    floor++;
                                }
                            }
                            let flatsAdded = 0;
                            let flat = flats.shift();
                            if (flat) {
                                loadingStart();
                                (function a(flat) {
                                    POST("houses", "flat", false, flat).
                                    done(() => {
                                        flatsAdded++;
                                    }).
                                    always(() => {
                                        flat = flats.shift();
                                        if (flat) {
                                            a(flat);
                                        } else {
                                            message(i18n("addresses.flatsWasAdded", flatsAdded));
                                            modules.addresses.houses.renderHouse(params.houseId);
                                        }
                                    });
                                })(flat);
                            }
                        },
                    });
                }
            },
            {
                title: i18n("addresses.broadcast"),
                click: function () {
                    loadingStart();
                    QUERY("subscribers", "subscribers", {
                        by: "houseId",
                        query: params.houseId
                    }, true).
                    fail(FAIL).
                    done(result => {
                        loadingDone();
                        if (result && result.subscribers && result.subscribers.length) {
                            cardForm({
                                title: i18n("addresses.messageSend"),
                                footer: true,
                                borderless: true,
                                topApply: true,
                                apply: "addresses.doMessageSend",
                                size: "lg",
                                fields: [
                                    {
                                        id: "title",
                                        type: "text",
                                        title: i18n("addresses.messageTitle"),
                                        placeholder: i18n("addresses.messageTitle"),
                                        validate: v => {
                                            return $.trim(v) !== "";
                                        }
                                    },
                                    {
                                        id: "body",
                                        type: "area",
                                        title: i18n("addresses.messageBody"),
                                        placeholder: i18n("addresses.messageBody"),
                                        validate: v => {
                                            return $.trim(v) !== "";
                                        }
                                    },
                                    {
                                        id: "action",
                                        type: "select2",
                                        title: i18n("addresses.messageAction"),
                                        options: [
                                            {
                                                value: "inbox",
                                                text: i18n("addresses.messageActionInbox"),
                                            },
                                            {
                                                value: "money",
                                                text: i18n("addresses.messageActionBalancePlus"),
                                            },
                                        ]
                                    },
                                ],
                                callback: msg => {
                                    let n = 0;
                                    (function send(r) {
                                        let subscriber = result.subscribers.pop();
                                        if (r && r.sent) {
                                            n += r.sent.count;
                                        }
                                        if (subscriber) {
                                            POST("inbox", "message", subscriber.subscriberId, msg).
                                            fail(FAIL).
                                            done(send);
                                        } else {
                                            message(i18n("addresses.messagesSent", n));
                                        }
                                    })();
                                },
                            });
                        } else {
                            warning(i18n("addresses.noSubscribersFond"));
                        }
                    });
                },
            }]);

            modules.addresses.houses.renderHouse(params.houseId);
        }
    },
}).init();