({
    init: function () {
        moduleLoaded("addresses.cms", this);
    },

    renderEntranceCMS: function (houseId, entranceId, entrance) {
        GET("houses", "cms", entranceId, true).
        fail(FAIL).
        fail(() => {
            pageError();
        }).
        done(response => {
            let cms_layout = response.cms;

            modules.addresses.houses.loadHouse(houseId, entrance, () => {
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
                                modules.addresses.cms.renderEntranceCMS(houseId, entranceId);
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

        $("#altForm").hide();

        modules.addresses.cms.renderEntranceCMS(params.houseId, params.entranceId, params.entrance);
    },
}).init();