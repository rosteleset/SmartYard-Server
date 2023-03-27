({
    currentSheet: false,
    cols: false,
    rows: false,
    colsMd5: false,
    rowsMd5: false,
    issues: {},
    issuesInSheet: {},
    sid: false,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("cs", "sheets")) {
                leftSide("fas fa-fw fa-table", i18n("cs.cs"), "#cs", "tt");
            }
        }

        if (AVAIL("cs", "sheet", "PUT")) {
            loadSubModules("cs", [
                "sheet",
            ], this);
        } else {
            moduleLoaded("cs", this);
        }

        modules.mqtt.subscribe("cs/cell", modules.cs.mqttCellMsg);
        modules.mqtt.subscribe("redis/expire", modules.cs.mqttRedisExpireMsg);
        modules.mqtt.subscribe("issue/changed", modules.cs.mqttIssueChanged);

        modules.cs.sid = md5(Math.random() + (new Date()).getTime() + $.cookie("_login"));

        setInterval(() => {
            $(".dataCell").each(function () {
                let cell = $(this);

                if (!modules.cs.cellAvailable(modules.cs.currentSheet.sheet.date, modules.cs.rowsMd5[cell.attr("data-row")])) {
                    modules.cs.clearCell(cell);
                    cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                    cell.removeClass("pointer");
                    cell.removeClass("dataCell");
                    cell.addClass(modules.cs.currentSheet.sheet.emptyClass);
                }
            });
        }, 1000);
    },

    mqttCellMsg: function (topic, payload) {
        cell = $(".dataCell[data-uid=" + payload.uid + "]");

        if (cell && cell.length == 1) {
            cell.removeClass("spinner-small");

            switch (payload["action"]) {
                case "claimed":
                    modules.cs.clearCell(cell);
                    cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.addClass(modules.cs.currentSheet.sheet.blockedClass);
                    cell.attr("data-login", payload.login);
                    console.log(payload);
                    if (payload.login == $.cookie("_login") && payload.sid == modules.cs.sid) {
                        mYesNo(i18n("cs.coordinateOrReserve"), i18n("cs.action"), () => {
                            modules.cs.coordinate(cell);
                        }, () => {
                            cell.addClass("spinner-small");
                            PUT("cs", "reserveCell", false, {
                                action: "reserve",
                                sheet: md5($("#csSheet").val()),
                                date: md5($("#csDate").val()),
                                col: cell.attr("data-col"),
                                row: cell.attr("data-row"),
                                uid: cell.attr("data-uid"),
                                sid: modules.cs.sid,
                                expire: 60 * 60 * 24 * 7,
                            }).
                            fail(FAIL).
                            fail(() => {
                                cell.removeClass("spinner-small");
                            });
                        }, i18n("cs.coordinate"), i18n("cs.reserve"));
                    }
                    break;
                
                case "reserved":
                    modules.cs.clearCell(cell);
                    cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                    cell.addClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.attr("data-login", payload.login);
                    break;

                case "released":
                    cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                    modules.cs.restoreCell(cell);
                    cell.attr("data-login", false);
                    break;
            }
        }
    },

    mqttRedisExpireMsg: function (topic, payload) {
        if (payload.key.substring(0, 5) == "cell_") {
            let cell = $(".dataCell[data-uid=" + payload.key.split("_")[5] + "]");
            if (cell && cell.length == 1) {
                cell.removeClass("spinner-small");
                cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                modules.cs.restoreCell(cell);
                cell.attr("data-login", false);
            }
        }
    },

    mqttIssueChanged: function (topic, payload) {
        if ($("#csSheet:visible").length) {
            if (modules.cs.issues[payload]) {
                modules.cs.renderCS(true);
            }
        }
    },

    clearCell: function (cell) {
        let col = cell.attr("data-col");
        let row = cell.attr("data-row");

        if (modules.cs && modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.specialRows.indexOf(modules.cs.rowsMd5[row]) >= 0) {
            for (let i in modules.cs.currentSheet.sheet.data) {
                if (col == md5(modules.cs.currentSheet.sheet.data[i].col)) {
                    for (let j in modules.cs.currentSheet.sheet.data[i].rows) {
                        if (row == md5(modules.cs.currentSheet.sheet.data[i].rows[j])) {
                            cell.removeClass(modules.cs.currentSheet.sheet.specialRowClass);
                        }
                    }
                }
            }
        }
    },

    restoreCell: function (cell) {
        let col = cell.attr("data-col");
        let row = cell.attr("data-row");

        if (modules.cs.currentSheet.sheet.specialRows.indexOf(modules.cs.rowsMd5[row]) >= 0) {
            for (let i in modules.cs.currentSheet.sheet.data) {
                if (col == md5(modules.cs.currentSheet.sheet.data[i].col)) {
                    for (let j in modules.cs.currentSheet.sheet.data[i].rows) {
                        if (row == md5(modules.cs.currentSheet.sheet.data[i].rows[j])) {
                            cell.addClass(modules.cs.currentSheet.sheet.specialRowClass);
                        }
                    }
                }
            }
        }
    },

    coordinate: function (cell) {
        let workflow = "";
        let logins = [];
        for (let i in modules.cs.currentSheet.sheet.data) {
            if (modules.cs.currentSheet.sheet.data[i].col == modules.cs.colsMd5[cell.attr("data-col")]) {
                if (typeof modules.cs.currentSheet.sheet.data[i].workflow !== "undefined") {
                    workflow = modules.cs.currentSheet.sheet.data[i].workflow;
                }
                if (typeof modules.cs.currentSheet.sheet.data[i].logins !== "undefined") {
                    logins = modules.cs.currentSheet.sheet.data[i].logins;
                }
            }
        }
        cardForm({
            title: i18n("cs.selectIssue"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            fields: [
                {
                    id: "issueId",
                    type: "text",
                    title: i18n("tt.issueId"),
                },
            ],
            callback: result => {
                prefferredValues = {};
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.sheet) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.sheet] = modules.cs.currentSheet.sheet.sheet;
                }
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.date) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.date] = modules.cs.currentSheet.sheet.date;
                }
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.col) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.col] = modules.cs.colsMd5[cell.attr("data-col")];
                }
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.row) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.row] = modules.cs.rowsMd5[cell.attr("data-row")];
                }
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.cells) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.cells] = "1";
                }
                if (modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.assigned && logins) {
                    prefferredValues[modules.cs.currentSheet.sheet.fields.assigned] = logins;
                }
                if (workflow) {
                    prefferredValues["workflow"] = workflow;
                }
                modules.tt.issue.issueAction(result.issueId, modules.cs.currentSheet.sheet.action, modules.cs.renderCS, prefferredValues)
            },
        }).show();
    },

    renderCS: function (silent) {
        modules.cs.issues = {};
        modules.cs.issuesInSheet = {};

        function loadIssues(callback) {
            if (modules.cs.currentSheet.sheet.issuesQuery) {
                modules.cs.currentSheet.sheet.issuesQuery.preprocess = {};
                modules.cs.currentSheet.sheet.issuesQuery.preprocess["%%sheet"] = modules.cs.currentSheet.sheet.sheet;
                modules.cs.currentSheet.sheet.issuesQuery.preprocess["%%date"] = modules.cs.currentSheet.sheet.date;
                POST("tt", "issues", false, modules.cs.currentSheet.sheet.issuesQuery).
                fail(FAIL).
                done(r => {
                    for (let i in r.issues.issues) {
                        let col = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.col];
                        let row = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.row];
                        let cells = parseInt(r.issues.issues[i][modules.cs.currentSheet.sheet.fields.cells]);
                        let installers = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.assigned];
                        let done = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.done];

                        console.log(r.issues.issues[i].issueId, col, row, cells, installers, done);
                        
                        let start = -1;

                        for (let j in modules.cs.currentSheet.sheet.data) {
                            if (modules.cs.currentSheet.sheet.data[j].col == col) {
                                for (let k in modules.cs.currentSheet.sheet.data[j].rows) {
                                    if (modules.cs.currentSheet.sheet.data[j].rows[k] == row || start >= 0) {
                                        if (start < 0) {
                                            start = k;
                                        }
                                        if (k - start < cells) {
                                            modules.cs.issues[r.issues.issues[i].issueId] = true;
                                            if (!modules.cs.issuesInSheet) {
                                                modules.cs.issuesInSheet = {};
                                            }
                                            let uid = md5($("#csSheet").val() + ":" + $("#csDate").val() + ":" + col + ":" + modules.cs.currentSheet.sheet.data[j].rows[k]);
                                            if (!modules.cs.issuesInSheet[uid]) {
                                                modules.cs.issuesInSheet[uid] = "";
                                            }
                                            if (installers && !done) {
                                                modules.cs.issuesInSheet[uid] += `<span class="csIssueSpan pl-1 pr-1 ${modules.cs.currentSheet.sheet.issueAssignedClass}">${r.issues.issues[i].issueId}</span><br />`;
                                            } else
                                            if (!installers && done) {
                                                modules.cs.issuesInSheet[uid] += `<span class="csIssueSpan pl-1 pr-1 ${modules.cs.currentSheet.sheet.issueDoneClass}">${r.issues.issues[i].issueId}</span><br />`;
                                            } else
                                            if (installers && done) {
                                                modules.cs.issuesInSheet[uid] += `<span class="csIssueSpan pl-1 pr-1 ${modules.cs.currentSheet.sheet.issueAssignedClass} ${modules.cs.currentSheet.sheet.issueDoneClass}">${r.issues.issues[i].issueId}</span><br />`;
                                            } else {
                                                modules.cs.issuesInSheet[uid] += `<span class="csIssueSpan pl-1 pr-1 ${modules.cs.currentSheet.sheet.issueCoordinatedClass}">${r.issues.issues[i].issueId}</span><br />`;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }).
                always(() => {
                    if (typeof callback === "function") {
                        callback();
                    }
                })
            } else {
                if (typeof callback === "function") {
                    callback();
                }
            }
        }

        function renderSheet(response) {
            if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.cellAvailableCheck) {
                modules.cs.cellAvailable = new Function ("sheetDate", "cellTime", `return ${modules.cs.currentSheet.sheet.cellAvailableCheck};`);
            } else {
                modules.cs.cellAvailable = new Function ("sheetDate", "cellTime", `return true;`);
            }

            if (response && response.sheet && response.sheet.sheet && response.sheet.sheet.data) {
                let s = response.sheet.sheet.data;
                for (let i in s) {
                    if (modules.cs.cols.indexOf(s[i].col) < 0) {
                        modules.cs.cols.push(s[i].col);
                        modules.cs.colsMd5[md5(s[i].col)] = s[i].col;
                    }
                    for (let j in s[i].rows) {
                        if (modules.cs.rows.indexOf(s[i].rows[j]) < 0) {
                            modules.cs.rows.push(s[i].rows[j]);
                            modules.cs.rowsMd5[md5(s[i].rows[j])] = s[i].rows[j];
                        }
                    }
                }

                function sf(a, b) {
                    if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.weights) {
                        if (typeof modules.cs.currentSheet.sheet.weights[a] !== "undefined") {
                            a = modules.cs.currentSheet.sheet.weights[a];
                        }
                        if (typeof modules.cs.currentSheet.sheet.weights[b] !== "undefined") {
                            b = modules.cs.currentSheet.sheet.weights[b];
                        }
                    }
                    if (a > b) {
                        return 1;
                    }
                    if (a < b) {
                        return -1;
                    }
                    return 0;
                }

                modules.cs.cols.sort(sf);
                modules.cs.rows.sort(sf);

                let h = '';
                h += '<table width="100%" class="mt-3 table table-hover table-bordered" id="csSheet">';
                h += '<thead>';
                h += '<tr>';
                h += '<td>&nbsp;</td>';
                for (let i in modules.cs.cols) {
                    let c = false;
                    for (let j in s) {
                        if (modules.cs.cols[i] == s[j].col) {
                            c = s[j];
                        }
                    }
                    if (c && c.class) {
                        h += '<td class="' + c.class + '" nowrap>';
                    } else {
                        h += '<td nowrap>';
                    }
                    h += "<span class='hoverable column' data-col='" + md5(modules.cs.cols[i]) + "'>" + escapeHTML(modules.cs.cols[i]) + "</span>";
                    if (c.logins && c.logins.length) {
                        for (let j in c.logins) {
                            let u = false;
                            for (let k in modules.users.meta) {
                                if (modules.users.meta[k].login == c.logins[j]) {
                                    u = modules.users.meta[k].realName;
                                    break;
                                }
                            }
                            h += "<br/>";
                            if (response.sheet.sheet.loginClass) {
                                h += "<span class='" + response.sheet.sheet.loginClass + "'>"
                            } else {
                                h += "<span>";
                            }
                            h += u?u:c.logins[j];
                            h += "</span>";
                        }
                    }
                    h += "</td>";
                }
                h += '</tr>';
                h += '</thead>';
                h += '<tbody>';
                for (let i in modules.cs.rows) {
                    h += '<tr>';
                    if (response.sheet.sheet.timeClass) {
                        h += '<td class="' + response.sheet.sheet.timeClass + '">' + escapeHTML(modules.cs.rows[i]) + '</td>';
                    } else {
                        h += '<td>' + escapeHTML(modules.cs.rows[i]) + '</td>';
                    }
                    for (let j in modules.cs.cols) {
                        let f = false;
                        for (let k in s) {
                            if (modules.cs.cols[j] == s[k].col) {
                                for (let l in s[k].rows) {
                                    if (s[k].rows[l] == modules.cs.rows[i] && modules.cs.cellAvailable(modules.cs.currentSheet.sheet.date, s[k].rows[l])) {
                                        f = true;
                                        let uid = md5($("#csSheet").val() + ":" + $("#csDate").val() + ":" + modules.cs.cols[j] + ":" + modules.cs.rows[i]);
                                        if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.specialRows && modules.cs.currentSheet.sheet.specialRows.indexOf(s[k].rows[l]) >= 0) {
                                            h += '<td class="' + modules.cs.currentSheet.sheet.specialRowClass + ' dataCell pointer" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                        } else {
                                            h += '<td class="dataCell pointer" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                        }
                                        if (modules.cs.issuesInSheet[uid]) {
                                            h += modules.cs.issuesInSheet[uid];
                                        }
                                        h += '</td>';
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                        if (!f) {
                            if (response.sheet.sheet.emptyClass) {
                                h += '<td class="' + response.sheet.sheet.emptyClass + '" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '"></td>';
                            } else {
                                h += '<td data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '"></td>';
                            }
                        }
                    }
                    h += '</tr>';
                }
                h += '</tbody>';
                h += '</table>';
                
                $("#mainForm").html(h);

                $(".dataCell").off("click").on("click", function () {
                    let cell = $(this);

                    if ($(".spinner-small").length) {
                        return;
                    }

                    if (cell.hasClass(modules.cs.currentSheet.sheet.emptyClass)) {
                        return;
                    }

                    if (cell.hasClass(modules.cs.currentSheet.sheet.reservedClass)) {
                        if (AVAIL("cs", "reserveCell", "DELETE") && cell.attr("data-login") != $.cookie("_login")) {
                            cell.addClass("spinner-small");
                                
                            PUT("cs", "reserveCell", false, {
                                action: "release-force",
                                sheet: md5($("#csSheet").val()),
                                date: md5($("#csDate").val()),
                                col: cell.attr("data-col"),
                                row: cell.attr("data-row"),
                                uid: cell.attr("data-uid"),
                                sid: modules.cs.sid,
                            }).
                            fail(FAIL).
                            fail(() => {
                                cell.removeClass("spinner-small");
                            });
                        } else
                        if (cell.attr("data-login") == $.cookie("_login")) {
                            mYesNo(i18n("cs.coordinateOrUnReserve"), i18n("cs.action"), () => {
                                modules.cs.coordinate(cell);
                            }, () => {
                                cell.addClass("spinner-small");
                                
                                PUT("cs", "cell", false, {
                                    action: "release",
                                    sheet: md5($("#csSheet").val()),
                                    date: md5($("#csDate").val()),
                                    col: cell.attr("data-col"),
                                    row: cell.attr("data-row"),
                                    uid: cell.attr("data-uid"),
                                    sid: modules.cs.sid,
                                }).
                                fail(FAIL).
                                fail(() => {
                                    cell.removeClass("spinner-small");
                                });
                            }, i18n("cs.coordinate"), i18n("cs.unReserve"));
                        }
                    } else
                    if (cell.hasClass(modules.cs.currentSheet.sheet.blockedClass)) {
                        if (cell.attr("data-login") == $.cookie("_login")) {
                            cell.addClass("spinner-small");

                            PUT("cs", "cell", false, {
                                action: "release",
                                sheet: md5($("#csSheet").val()),
                                date: md5($("#csDate").val()),
                                col: cell.attr("data-col"),
                                row: cell.attr("data-row"),
                                uid: cell.attr("data-uid"),
                                sid: modules.cs.sid,
                            }).
                            fail(FAIL).
                            fail(() => {
                                cell.removeClass("spinner-small");
                            });
                        }
                    } else {
                        cell.addClass("spinner-small");

                        PUT("cs", "cell", false, {
                            action: "claim",
                            sheet: md5($("#csSheet").val()),
                            date: md5($("#csDate").val()),
                            col: cell.attr("data-col"),
                            row: cell.attr("data-row"),
                            uid: cell.attr("data-uid"),
                            sid: modules.cs.sid,
                            expire: 60,
                        }).
                        fail(FAIL).
                        fail(() => {
                            cell.removeClass("spinner-small");
                        });
                    }
                });

                $(".column").off("click").on("click", function () {
                    let cell = $(this);
                    console.log(modules.cs.colsMd5[cell.attr("data-col")]);
                });

                for (let i in modules.cs.currentSheet.cells) {
                    switch (modules.cs.currentSheet.cells[i].mode) {
                        case "claimed":
                            $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").addClass(modules.cs.currentSheet.sheet.blockedClass).attr("data-login", modules.cs.currentSheet.cells[i].login);
                            break;
                        
                        case "reserved":
                            $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").addClass(modules.cs.currentSheet.sheet.reservedClass).attr("data-login", modules.cs.currentSheet.cells[i].login);
                            break;
                    }
                }

                $(".csIssueSpan").off("click").on("click", function (e) {
                    let cell = $(this);
                    location.href = "?#tt&issue=" + cell.text();
                    e.stopPropagation();
                });

                loadingDone();
            } else {
                $("#mainForm").html(i18n("cs.notFound"));
                loadingDone();
            }
        }

        function loadSheet() {
            if (!silent) {
                loadingStart();
            }

            GET("cs", "sheets").
            fail(FAIL).
            fail(() => {
                $("#mainForm").html(i18n("cs.errorLoadingSheet"));
            }).
            fail(loadingDone).
            done(response => {
                let sheets = [];
                let dates = [];

                for (let i in response.sheets) {
                    if (sheets.indexOf(response.sheets[i].metadata.sheet) < 0) {
                        sheets.push(response.sheets[i].metadata.sheet);
                    }
                    if (dates.indexOf(response.sheets[i].metadata.date) < 0) {
                        dates.push(response.sheets[i].metadata.date);
                    }
                }
    
                sheetsOptions = "";
                for (let i in sheets) {
                    if (sheets[i] == $.cookie("_sheet_name")) {
                        sheetsOptions += "<option selected='selected'>" + escapeHTML(sheets[i]) + "</option>";
                    } else {
                        sheetsOptions += "<option>" + escapeHTML(sheets[i]) + "</option>";
                    }
                }
    
                datesOptions = "";
                for (let i in dates) {
                    if (dates[i] == $.cookie("_sheet_date")) {
                        datesOptions += "<option selected='selected'>" + escapeHTML(dates[i]) + "</option>";
                    } else {
                        datesOptions += "<option>" + escapeHTML(dates[i]) + "</option>";
                    }
                }
    
                let rtd = "<div class='form-inline'>";
    
                rtd += `<div class="input-group input-group-sm mr-2" style="width: 200px;"><div class="input-group-prepend"><span class="input-group-text pointer-input-group" title="${i18n("cs.refresh")}" id="csRefresh"><i class="fas fa-fw fa-sync-alt"></i></span></div><select id="csSheet" class="form-control">${sheetsOptions}</select></div>`;
                rtd += `<div class="input-group input-group-sm" style="width: 150px;"><select id="csDate" class="form-control">${datesOptions}</select></div>`;
        
                if (AVAIL("cs", "sheet", "PUT")) {
                    rtd += `<div class="nav-item mr-0 pr-0 align-middle"><span id="addCSsheet" class="nav-link text-success mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.addSheet")}"><i class="fas fa-lg fa-fw fa-plus-square"></i></span></div>`;
                    rtd += `<div class="nav-item mr-0 pr-0"><span id="editCSsheet" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.editSheet")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></div>`;
                    rtd += `<div class="nav-item mr-0 pr-0"><span id="deleteCSsheet" class="nav-link text-danger mr-1 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.deleteSheet")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></div>`;
                }
        
                rtd += "</span>";
        
                $("#rightTopDynamic").html(rtd);
        
                $("#addCSsheet").off("click").on("click", () => {
                    cardForm({
                        title: i18n("cs.addSheet"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        fields: [
                            {
                                id: "sheet",
                                type: "select2",
                                title: i18n("cs.sheet"),
                                placeholder: i18n("cs.sheet"),
                                tags: true,
                                createTags: true,
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                }
                            },
                            {
                                id: "date",
                                type: "date",
                                title: i18n("cs.date"),
                                placeholder: i18n("cs.date"),
                                validate: (v) => {
                                    return $.trim(v) !== "";
                                }
                            },
                        ],
                        callback: result => {
                            $.cookie("_sheet_name", result.sheet, { expires: 3650, insecure: config.insecureCookie });
                            $.cookie("_sheet_date", result.date, { expires: 3650, insecure: config.insecureCookie });
                            location.href = "?#cs.sheet&sheet=" + encodeURIComponent(result.sheet) + "&date=" + encodeURIComponent(result.date);
                        },
                    }).show();
                });
    
                $("#editCSsheet").off("click").on("click", () => {
                    if ($("#csSheet").val() && $("#csDate").val()) {
                        location.href = "?#cs.sheet&sheet=" + encodeURIComponent($("#csSheet").val()) + "&date=" + encodeURIComponent($("#csDate").val());
                    }
                });
    
                $("#deleteCSsheet").off("click").on("click", () => {
                    if ($("#csSheet").val() && $("#csDate").val()) {
                        mConfirm(i18n("cs.confirmDeleteSheet", $("#csSheet").val(), $("#csDate").val()), i18n("confirm"), i18n("delete"), () => {
                            loadingStart();
                            DELETE("cs", "sheet", false, {
                                sheet: $("#csSheet").val(),
                                date: $("#csDate").val(),
                            }).
                            fail(FAIL).
                            fail(loadingDone).
                            done(modules.cs.renderCS);
                        })
                    }
                });

                $("#csSheet").off("change").on("change", () => {
                    $.cookie("_sheet_name", $("#csSheet").val(), { expires: 3650, insecure: config.insecureCookie });
                    modules.cs.renderCS();
                });

                $("#csDate").off("change").on("change", () => {
                    $.cookie("_sheet_date", $("#csDate").val(), { expires: 3650, insecure: config.insecureCookie });
                    modules.cs.renderCS();
                });

                $("#csRefresh").off("click").on("click", modules.cs.renderCS);
    
                if ($("#csSheet").val() && $("#csDate").val()) {
                    QUERY("cs", "sheet", {
                        "sheet": $("#csSheet").val(),
                        "date": $("#csDate").val(),
                        "extended": 1,
                    }).
                    fail(FAIL).
                    fail(loadingDone).
                    fail(() => {
                        $("#mainForm").html(i18n("cs.errorLoadingSheet"));
                    }).
                    done(response => {
                        modules.cs.cols = [];
                        modules.cs.rows = [];
                        modules.cs.colsMd5 = {};
                        modules.cs.rowsMd5 = {};

                        modules.cs.currentSheet = response.sheet;

                        loadIssues(() => {
                            renderSheet(response);
                        });
                    });
                } else {
                    $("#mainForm").html(i18n("cs.notFound"));
                    loadingDone();
                }
            });
        }

        modules.users.loadUsers("users", "users").
        fail(FAIL).
        fail(() => {
            $("#mainForm").html(i18n("cs.errorLoadingSheet"));
        }).
        fail(loadingDone).
        done(loadSheet);
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("cs.cs");

        modules.cs.renderCS();
    },
}).init();