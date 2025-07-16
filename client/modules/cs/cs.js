({
    currentSheet: false,
    cols: false,
    rows: false,
    colsMd5: false,
    rowsMd5: false,
    issues: {},
    issuesInSheet: {},
    sid: false,
    idle: true,
    preCoordinate: false,
    csChangedTimeout: -1,
    menuItem: false,
    highlight: false,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("cs", "sheets") && AVAIL("tt", "tt")) {
                this.menuItem = leftSide("fas fa-fw fa-table", i18n("cs.cs"), "?#cs", "tt");
            }
        }

        if (AVAIL("cs", "sheet", "PUT") && AVAIL("tt", "tt")) {
            loadSubModules("cs", [
                "sheet",
            ], this);
        } else {
            moduleLoaded("cs", this);
        }

        modules.mqtt.subscribe("_connect", () => {
            modules.cs.mqttSheetChanged();
        });

        modules.mqtt.subscribe("cs/cell", modules.cs.mqttCellMsg);
        modules.mqtt.subscribe("redis/expire", modules.cs.mqttRedisExpireMsg);
        modules.mqtt.subscribe("issue/changed", modules.cs.mqttIssueChanged);
        modules.mqtt.subscribe("sheet/changed", modules.cs.mqttSheetChanged);

        modules.cs.sid = md5(guid());

        setInterval(() => {
            $(".dataCell").each(function () {
                let cell = $(this);

                if (modules.cs.cellExpired(modules.cs.currentSheet.sheet.date, modules.cs.rowsMd5[cell.attr("data-row")])) {
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

    csChanged: function () {
        clearTimeout(modules.cs.csChangedTimeout);

        function refresh() {
            if ($("#csSheet:visible").length) {
                if (modules.cs.idle) {
                    modules.cs.renderCS(true);
                } else {
                    modules.cs.csChangedTimeout = setTimeout(refresh, Math.random() * 1000 + 1000);
                }
            }
        }

        modules.cs.csChangedTimeout = setTimeout(refresh, Math.random() * 1000 + 1000);
    },

    mqttCellMsg: function (topic, payload) {
        if (!payload || !payload.uid) return;

        cell = $(".dataCell[data-uid=" + payload.uid + "]");

        if (cell && cell.length == 1) {
            cell.removeClass("spinner-small");

            switch (payload["action"]) {
                case "claimed":
                    modules.cs.clearCell(cell);
                    cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.addClass(modules.cs.currentSheet.sheet.blockedClass);
                    cell.attr("data-login", payload.login).attr("data-login-display", modules.users.login2name(payload.login));
                    if (payload.login == lStore("_login") && payload.sid == modules.cs.sid) {
                        switch (parseInt(payload.step)) {
                            case 0:
                                if (AVAIL("cs", "reserveCell", "PUT")) {
                                    mYesNo(i18n("cs.coordinateOrReserve"), i18n("cs.action"), () => {
                                        cell.addClass("spinner-small");
                                        PUT("cs", "cell", false, {
                                            action: "claim",
                                            step: 1,
                                            sheet: md5($("#csSheet").val()),
                                            date: md5($("#csDate").val()),
                                            col: cell.attr("data-col"),
                                            row: cell.attr("data-row"),
                                            uid: cell.attr("data-uid"),
                                            sid: modules.cs.sid,
                                            expire: 300,
                                        }).
                                        fail(FAIL).
                                        fail(() => {
                                            cell.removeClass("spinner-small");
                                        });
                                    }, () => {
                                        cardForm({
                                            title: i18n("cs.reserve"),
                                            footer: true,
                                            borderless: true,
                                            topApply: true,
                                            fields: [
                                                {
                                                    id: "comment",
                                                    type: "text",
                                                    title: i18n("cs.comment"),
                                                    placeholder: i18n("cs.comment"),
                                                    validate: v => {
                                                        return $.trim(v) !== "";
                                                    }
                                                },
                                            ],
                                            callback: result => {
                                                cell.addClass("spinner-small");
                                                PUT("cs", "reserveCell", false, {
                                                    action: "reserve",
                                                    sheet: md5($("#csSheet").val()),
                                                    date: md5($("#csDate").val()),
                                                    col: cell.attr("data-col"),
                                                    row: cell.attr("data-row"),
                                                    uid: cell.attr("data-uid"),
                                                    sid: modules.cs.sid,
                                                    expire: (modules.cs.currentSheet.sheet.reserveDays ? parseInt(modules.cs.currentSheet.sheet.reserveDays) : 21) * 24 * 60 * 60,
                                                    comment: $.trim(result.comment),
                                                }).
                                                fail(FAIL).
                                                fail(() => {
                                                    cell.removeClass("spinner-small");
                                                });
                                            },
                                        });
                                    }, i18n("cs.coordinate"), i18n("cs.reserve"), 58 * 1000);
                                } else {
                                    cell.addClass("spinner-small");
                                    PUT("cs", "cell", false, {
                                        action: "claim",
                                        step: 1,
                                        sheet: md5($("#csSheet").val()),
                                        date: md5($("#csDate").val()),
                                        col: cell.attr("data-col"),
                                        row: cell.attr("data-row"),
                                        uid: cell.attr("data-uid"),
                                        sid: modules.cs.sid,
                                        expire: 300,
                                    }).
                                    fail(FAIL).
                                    fail(() => {
                                        cell.removeClass("spinner-small");
                                    });
                                }
                                break;

                            case 1:
                                modules.cs.clearCell(cell);
                                cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                                cell.addClass(modules.cs.currentSheet.sheet.blockedClass);
                                cell.attr("data-login", payload.login).attr("data-login-display", modules.users.login2name(payload.login));
                                if (payload.login == lStore("_login") && payload.sid == modules.cs.sid) {
                                    modules.cs.coordinate(cell);
                                }
                                break;

                            case 2:
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

                                modules.tt.issue.issueAction(modules.cs.preCoordinate.issueId, modules.cs.currentSheet.sheet.action, () => {
                                    message(i18n("cs.wasCoordinated"));
                                    lStore("_coordinate_issue", null);
/*
                                    PUT("cs", "cell", false, {
                                        action: "release",
                                        sheet: md5($("#csSheet").val()),
                                        date: md5($("#csDate").val()),
                                        col: cell.attr("data-col"),
                                        row: cell.attr("data-row"),
                                        uid: cell.attr("data-uid"),
                                        sid: modules.cs.sid,
                                    }).
                                    done(() => {
                                        lStore("_coordinate_issue", null);
                                    }).
                                    fail(FAIL).
                                    fail(() => {
                                        modules.cs.idle = true;
                                        cell.removeClass("spinner-small");
                                    });
*/
                                }, prefferredValues, 58 * 1000);
                            break;
                        }
                    }
                    break;

                case "reserved":
                    modules.cs.clearCell(cell);
                    cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                    cell.addClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.attr("data-login", payload.login).attr("data-login-display", modules.users.login2name(payload.login) + (payload.comment ? ("\n[" + payload.comment + "]") : ""));
                    break;

                case "released":
                    cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                    cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                    modules.cs.restoreCell(cell);
                    cell.attr("data-login", false).attr("data-login-display", false);
                    break;
            }
        }
    },

    mqttRedisExpireMsg: function (topic, payload) {
        if (payload.key.substring(0, 5) == "cell_" && payload.key.split("_")[5]) {
            let cell = $(".dataCell[data-uid=" + payload.key.split("_")[5] + "]");
            if (cell && cell.length == 1) {
                cell.removeClass("spinner-small");
                cell.removeClass(modules.cs.currentSheet.sheet.blockedClass);
                cell.removeClass(modules.cs.currentSheet.sheet.reservedClass);
                modules.cs.restoreCell(cell);
                cell.attr("data-login", false).attr("data-login-display", false);
            }
        }
    },

    mqttIssueChanged: function (topic, payload) {
        if ($("#csSheet:visible").length) {
            if (modules.cs.issues[payload]) {
                modules.cs.csChanged();
            } else {
                GET("tt", "issue", payload).
                done(r => {
                    if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.fields && modules.cs.currentSheet.sheet.fields.sheet && modules.cs.currentSheet.sheet.fields.date) {
                        if (modules.cs.currentSheet.sheet.sheet == r.issue.issue[modules.cs.currentSheet.sheet.fields.sheet] && modules.cs.currentSheet.sheet.date == r.issue.issue[modules.cs.currentSheet.sheet.fields.date]) {
                            modules.cs.csChanged();
                        }
                    }
                });
            }
        }
    },

    mqttSheetChanged: function (topic, payload) {
        if ($("#csSheet:visible").length) {
            if (payload) {
                if (modules.cs.currentSheet && modules.cs.currentSheet.sheet) {
                    if (modules.cs.currentSheet.sheet.sheet == payload.sheet && modules.cs.currentSheet.sheet.date == payload.date) {
                        modules.cs.csChanged();
                    }
                }
            } else {
                modules.cs.csChanged();
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
        cardForm({
            title: i18n("cs.selectIssue"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            timeout: 58 * 1000,
            fields: [
                {
                    id: "issueId",
                    type: "text",
                    title: i18n("tt.issueId"),
                    value: lStore("_coordinate_issue") ? lStore("_coordinate_issue") : "",
                    button: {
                        class: "fas fa-recycle",
                        click: p => {
                            $("#" + p + "issueId").val("");
                            lStore("_coordinate_issue", null);
                        }
                    },
                    validate: v => {
                        return v && $.trim(v) && v !== '-' && v !== 'undefined';
                    },
                },
            ],
            callback: result => {
                result.issueId = $.trim(result.issueId);
                modules.cs.preCoordinate = result;
                cell.addClass("spinner-small");
                PUT("cs", "cell", false, {
                    action: "claim",
                    step: 2,
                    sheet: md5($("#csSheet").val()),
                    date: md5($("#csDate").val()),
                    col: cell.attr("data-col"),
                    row: cell.attr("data-row"),
                    uid: cell.attr("data-uid"),
                    sid: modules.cs.sid,
                    expire: 300,
                }).
                fail(FAIL).
                fail(() => {
                    cell.removeClass("spinner-small");
                });
            },
        });
    },

    colMenu: function (col) {
        let mid = md5(guid());

        let h = `<span class="dropdown">`;
        h += `<span id="${mid}" class="pointer dropdown-toggle dropdown-toggle-no-icon" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" style="margin-left: -4px;"><i class="far fa-fw fa-caret-square-down mr-1"></i>${col}</span>`;
        h += `<ul class="dropdown-menu" aria-labelledby="${mid}">`;
        h += `<li class="pointer dropdown-item colMenuSetAssigners" data-col="${md5(col)}">${i18n("cs.setAssigners")}</li>`;
        h += `<li class="dropdown-divider"></li>`;
        h += `<li class="pointer dropdown-item colMenuAssignAll" data-col="${md5(col)}">${i18n("cs.assignAll")}</li>`;
        h += `<li class="pointer dropdown-item colClearAssigners" data-col="${md5(col)}">${i18n("cs.clearAssigners")}</li>`;
        h += `<li class="dropdown-divider"></li>`;
        h += `<li class="pointer dropdown-item colSettings" data-col="${md5(col)}">${i18n("cs.colSettings")}</li>`;
        if (modules.cs.currentSheet.sheet.fields.map || modules.cs.currentSheet.sheet.fields.list) {
            h += `<li class="dropdown-divider"></li>`;
        }
        if (modules.cs.currentSheet.sheet.fields.list) {
            h += `<li class="pointer dropdown-item colListIssues" data-col="${md5(col)}">${i18n("cs.list")}</li>`;
        }
        if (modules.cs.currentSheet.sheet.fields.map) {
            h += `<li class="pointer dropdown-item colMapIssues" data-col="${md5(col)}">${i18n("cs.map")}</li>`;
        }
        h += `</ul></span>`;

        return h;
    },

    renderCS: function (silent) {
        if (!silent) {
            loadingStart();
        }

        modules.cs.idle = false;
        modules.cs.issues = {};
        modules.cs.issuesInSheet = {};

        function loadIssues(callback) {
            try {
                if (modules.cs.currentSheet.sheet.issuesQuery) {
                    modules.cs.currentSheet.sheet.issuesQuery.preprocess = {};
                    modules.cs.currentSheet.sheet.issuesQuery.preprocess["%%sheet"] = modules.cs.currentSheet.sheet.sheet;
                    modules.cs.currentSheet.sheet.issuesQuery.preprocess["%%date"] = modules.cs.currentSheet.sheet.date;
                    modules.cs.currentSheet.sheet.issuesQuery.project = modules.cs.currentSheet.sheet.project;

                    POST("tt", "issues", false, modules.cs.currentSheet.sheet.issuesQuery).
                    fail(FAIL).
                    fail(() => {
                        modules.cs.idle = true;
                    }).
                    done(r => {
                        for (let i in r.issues.issues) {
                            let col = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.col];
                            let row = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.row];
                            let cells = parseInt(r.issues.issues[i][modules.cs.currentSheet.sheet.fields.cells]);
                            let installers = r.issues.issues[i][modules.cs.currentSheet.sheet.fields.assigned];
                            let done = modules.cs.issueDone(r.issues.issues[i]);
                            let closed = modules.cs.issueClosed(r.issues.issues[i]);

                            let start = -1;

                            for (let j in modules.cs.currentSheet.sheet.data) {
                                if (modules.cs.currentSheet.sheet.data[j].col == col) {
                                    let rs;
                                    if (typeof modules.cs.currentSheet.sheet.data[j].rows === "string") {
                                        try {
                                            rs = JSON.parse(JSON.stringify(modules.cs.currentSheet.sheet.rowsTemplates[modules.cs.currentSheet.sheet.data[j].rows]));
                                        } catch (_) {
                                            FAILPAGE();
                                            loadingDone();
                                            return;
                                        }
                                    } else {
                                        rs = modules.cs.currentSheet.sheet.data[j].rows;
                                    }
                                    for (let k in rs) {
                                        if (rs[k] == row || start >= 0) {
                                            if (start < 0) {
                                                start = k;
                                            }
                                            if (k - start < cells) {
                                                modules.cs.issues[r.issues.issues[i].issueId] = r.issues.issues[i];
                                                if (!modules.cs.issuesInSheet) {
                                                    modules.cs.issuesInSheet = {};
                                                }
                                                let uid = md5($("#csSheet").val() + ":" + $("#csDate").val() + ":" + col + ":" + rs[k]);
                                                if (!modules.cs.issuesInSheet[uid]) {
                                                    modules.cs.issuesInSheet[uid] = "";
                                                }
                                                let title = "";
                                                if (modules.cs.currentSheet.sheet.fields.hint && r.issues.issues[i][modules.cs.currentSheet.sheet.fields.hint]) {
                                                    title = escapeHTML(r.issues.issues[i][modules.cs.currentSheet.sheet.fields.hint]);
                                                }
                                                if (modules.cs.highlight == r.issues.issues[i].issueId) {
                                                    modules.cs.issuesInSheet[uid] += `<span class="${modules.cs.currentSheet.sheet.issueHighlightClass ? modules.cs.currentSheet.sheet.issueHighlightClass : "bg-orange"}"><a class="csIssueSpan pointer pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a></span><br />`;
                                                } else
                                                if (closed) {
                                                    modules.cs.issuesInSheet[uid] += `<span class="${modules.cs.currentSheet.sheet.issueClosedClass}"><a class="csIssueSpan pointer pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a></span><br />`;
                                                } else
                                                if (installers && installers.length && !done) {
                                                    modules.cs.issuesInSheet[uid] += `<span class="${modules.cs.currentSheet.sheet.issueAssignedClass}"><a class="csIssueSpan pointer pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a></span><br />`;
                                                } else
                                                if ((!installers || !installers.length) && done) {
                                                    modules.cs.issuesInSheet[uid] += `<span class="${modules.cs.currentSheet.sheet.issueDoneClass}"><a class="csIssueSpan pointer pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a></span><br />`;
                                                } else
                                                if (installers && installers.length && done) {
                                                    modules.cs.issuesInSheet[uid] += `<span class="${modules.cs.currentSheet.sheet.issueAssignedClass} ${modules.cs.currentSheet.sheet.issueDoneClass}"><a class="csIssueSpan pointer pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a></span><br />`;
                                                } else {
                                                    modules.cs.issuesInSheet[uid] += `<a class="csIssueSpan pointer text-dark pl-1 pr-1" href="?#tt&issue=${r.issues.issues[i].issueId}" title="${title}">${r.issues.issues[i].issueId}</a><br />`;
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
            } catch (e) {
                FAILPAGE();
                loadingDone();
                modules.cs.idle = true;
            }
        }

        function renderSheet(response) {
            if (response && response.sheet && response.sheet.sheet && response.sheet.sheet.data) {
                let s = response.sheet.sheet.data;
                let parts = {};
                let cp;
                for (let i in s) {
                    if (modules.cs.cols.indexOf(s[i].col) < 0 && s[i].col.charAt(0) != "#" && !s[i].hidden) {
                        if (typeof s[i].part == "undefined") {
                            s[i].part = i18n("cs.noPart");
                        }
                        if (!parts[s[i].part]) {
                            parts[s[i].part] = [];
                        }
                        parts[s[i].part].push(s[i].col);
                        modules.cs.cols.push(s[i].col);
                        modules.cs.colsMd5[md5(s[i].col)] = s[i].col;
                    }
                    let rs;
                    if (typeof s[i].rows === "string") {
                        rs = JSON.parse(JSON.stringify(response.sheet.sheet.rowsTemplates[s[i].rows]));
                    } else {
                        rs = s[i].rows;
                    }
                    for (let j in rs) {
                        if (modules.cs.rows.indexOf(rs[j]) < 0 && rs[j].charAt(0) != "#") {
                            modules.cs.rows.push(rs[j]);
                            modules.cs.rowsMd5[md5(rs[j])] = rs[j];
                        }
                    }
                }

                function sf(a, b) {
                    let aw = "0";
                    let bw = "0";
                    if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.weights) {
                        if (typeof modules.cs.currentSheet.sheet.weights[a] !== "undefined") {
                            aw = modules.cs.currentSheet.sheet.weights[a];
                        }
                        if (typeof modules.cs.currentSheet.sheet.weights[b] !== "undefined") {
                            bw = modules.cs.currentSheet.sheet.weights[b];
                        }
                    }
                    if (aw > bw) {
                        return 1;
                    }
                    if (aw < bw) {
                        return -1;
                    }
                    if (a > b) {
                        return 1;
                    }
                    if (a < b) {
                        return -1;
                    }
                    return 0;
                }

                let maxCols = 0;

                for (let p in parts) {
                    if (parts[p].length > maxCols) {
                        maxCols = parts[p].length;
                    }
                }

                modules.cs.cols.sort(sf);
                modules.cs.rows.sort(sf);

                let h = '';
                h += '<table width="100%" class="mt-3 table table-hover table-bordered" id="csSheet">';
                for (let p in parts) {
                    if (p != cp) {
                        if (parseInt(p) >= 0 || p) {
                            h += "<tr><td nowrap style='width: 0%'>&nbsp;</td><td style='border: none!important; font-weight: bold;' class='text-primary' colspan='" + maxCols.toString() + "'><span class='hoverable csPart'>" + p + "</span></td></tr>";
                        }
                        cp = p;
                    }
                    h += '<tr>';
                    h += '<td nowrap style="width: 0%">&nbsp;</td>';
                    let cCols = 0;
                    for (let i in modules.cs.cols) {
                        if (parts[p].indexOf(modules.cs.cols[i]) < 0) {
                            continue;
                        }
                        let c = false;
                        for (let j in s) {
                            if (modules.cs.cols[i] == s[j].col) {
                                c = s[j];
                            }
                        }
                        if (c && c.class) {
                            h += '<td class="' + c.class + '" nowrap style="vertical-align: top!important;">';
                        } else {
                            h += '<td nowrap style="vertical-align: top!important;">';
                        }
                        h += "<span>" + modules.cs.colMenu(modules.cs.cols[i]) + "</span>";
                        if (c.logins && c.logins.length) {
                            for (let j in c.logins) {
                                h += "<br/>";
                                if (response.sheet.sheet.loginClass) {
                                    h += "<span class='" + response.sheet.sheet.loginClass + "'>"
                                } else {
                                    h += "<span>";
                                }
                                h += modules.users.login2name(c.logins[j]);
                                h += "</span>";
                            }
                        }
                        h += "</td>";
                        cCols++;
                    }
                    if (cCols < maxCols) {
                        for (let i = 0; i < maxCols - cCols; i++) {
                            h += "<td>&nbsp;</td>";
                        }
                    }
                    h += '</tr>';
                    for (let i in modules.cs.rows) {
                        h += '<tr>';
                        if (response.sheet.sheet.timeClass) {
                            h += '<td class="timeCell ' + response.sheet.sheet.timeClass + '" nowrap style="width: 0%">' + escapeHTML(modules.cs.rows[i]) + '</td>';
                        } else {
                            h += '<td class="timeCell" nowrap style="width: 0%">' + escapeHTML(modules.cs.rows[i]) + '</td>';
                        }
                        cCols = 0;
                        for (let j in modules.cs.cols) {
                            if (parts[p].indexOf(modules.cs.cols[j]) < 0) {
                                continue;
                            }
                            let f = false;
                            for (let k in s) {
                                let rs;
                                if (typeof s[k].rows === "string") {
                                    rs = JSON.parse(JSON.stringify(response.sheet.sheet.rowsTemplates[s[k].rows]));
                                } else {
                                    rs = s[k].rows;
                                }
                                if (modules.cs.cols[j] == s[k].col) {
                                    for (let l in rs) {
                                        if (rs[l] == modules.cs.rows[i]) {
                                            f = true;
                                            let uid = md5($("#csSheet").val() + ":" + $("#csDate").val() + ":" + modules.cs.cols[j] + ":" + modules.cs.rows[i]);
                                            if (!modules.cs.cellExpired(modules.cs.currentSheet.sheet.date, rs[l])) {
                                                if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.specialRows && modules.cs.currentSheet.sheet.specialRows.indexOf(rs[l]) >= 0) {
                                                    h += '<td class="' + modules.cs.currentSheet.sheet.specialRowClass + ' dataCell pointer" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                                } else {
                                                    h += '<td class="dataCell pointer" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                                }
                                            } else {
                                                if (response.sheet.sheet.emptyClass) {
                                                    h += '<td class="' + response.sheet.sheet.emptyClass + '" data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                                } else {
                                                    h += '<td data-col="' + md5(modules.cs.cols[j]) + '" data-row="' + md5(modules.cs.rows[i]) + '" data-uid="' + uid + '">';
                                                }
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
                            cCols++;
                        }
                        if (cCols < maxCols) {
                            for (let i = 0; i < maxCols - cCols; i++) {
                                h += "<td>&nbsp;</td>";
                            }
                        }
                        h += '</tr>';
                    }
                }
                h += '</table>';

                $("#mainForm").html(h);

                $(".colMenuSetAssigners").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let u = [];
                    for (let i in modules.users.meta) {
                        if (parseInt(modules.users.meta[i].uid)) {
                            u.push({
                                id: modules.users.meta[i].login,
                                text: modules.users.meta[i].realName + ' [' + modules.users.meta[i].login + ']',
                            });
                        }
                    }

                    let logins = [];

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            logins = modules.cs.currentSheet.sheet.data[i].logins;
                            break;
                        }
                    }

                    cardForm({
                        title: i18n("cs.setColLogins"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        size: "lg",
                        fields: [
                            {
                                id: "logins",
                                type: "select2",
                                title: i18n("cs.colLogins"),
                                placeholder: i18n("cs.colLogins"),
                                multiple: true,
                                value: logins,
                                options: u,
                            },
                        ],
                        callback: result => {
                            for (let i in modules.cs.currentSheet.sheet.data) {
                                if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                                    modules.cs.currentSheet.sheet.data[i].logins = result.logins;
                                    loadingStart();
                                    PUT("cs", "sheet", false, {
                                        "sheet": modules.cs.currentSheet.sheet.sheet,
                                        "date": modules.cs.currentSheet.sheet.date,
                                        "data": $.trim(JSON.stringify(modules.cs.currentSheet.sheet, null, 4)),
                                    }).
                                    fail(FAIL).
                                    done(() => {
                                        message(i18n("cs.sheetWasSaved"));
                                    });
                                    break;
                                }
                            }
                        },
                    });
                });

                $(".colSettings").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let cols = {};
                    let colName = "";

                    let rows = [];
                    let t = {};
                    let colPart = "";

                    let colHidden = false;

                    $(".timeCell").each(function () {
                        t[$(this).text()] = true;
                    });

                    for (let i in t) {
                        rows.push(i);
                    }

                    t = rows;
                    rows = [];

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            colName = modules.cs.currentSheet.sheet.data[i].col;
                            colPart = modules.cs.currentSheet.sheet.data[i].part;
                            colHidden = modules.cs.currentSheet.sheet.data[i].hidden === true;
                            for (let j in t) {
                                rows.push({
                                    id: t[j],
                                    text: t[j],
                                    checked: modules.cs.currentSheet.sheet.data[i].rows.indexOf(t[j]) >= 0,
                                });
                            }
                        } else {
                            if (modules.cs.currentSheet.sheet.data[i].col) {
                                cols[modules.cs.currentSheet.sheet.data[i].col] = true;
                            }
                        }
                    }

                    cardForm({
                        title: i18n("cs.colSettings"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        fields: [
                            {
                                id: "colPart",
                                type: "text",
                                title: i18n("cs.colPart"),
                                placeholder: i18n("cs.colPart"),
                                value: colPart,
                                validate: v => {
                                    return $.trim(v) !== "";
                                },
                            },
                            {
                                id: "colName",
                                type: "text",
                                title: i18n("cs.colName"),
                                placeholder: i18n("cs.colName"),
                                value: colName,
                                validate: v => {
                                    return $.trim(v) !== "" && !cols[$.trim(v)];
                                },
                            },
                            {
                                id: "colWeight",
                                type: "text",
                                title: i18n("cs.colWeight"),
                                placeholder: i18n("cs.colWeight"),
                                value: modules.cs.currentSheet.sheet.weights[colName],
                            },
                            {
                                id: "colHidden",
                                type: "noyes",
                                title: i18n("cs.colHidden"),
                                value: colHidden ? "1" : "0",
                            },
                            {
                                id: "colRows",
                                type: "multiselect",
                                title: i18n("cs.colRows"),
                                options: rows,
                            },
                        ],
                        callback: result => {
                            for (let i in modules.cs.currentSheet.sheet.data) {
                                if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                                    modules.cs.currentSheet.sheet.data[i].col = $.trim(result.colName);
                                    modules.cs.currentSheet.sheet.data[i].rows = result.colRows;
                                    modules.cs.currentSheet.sheet.data[i].part = $.trim(result.colPart);
                                    modules.cs.currentSheet.sheet.data[i].hidden = parseInt(result.colHidden) === 1;
                                    try {
                                        if (!modules.cs.currentSheet.sheet.weights) {
                                            modules.cs.currentSheet.sheet.weights = {};
                                        }
                                    } catch (e) {
                                        modules.cs.currentSheet.sheet.weights = {};
                                    }
                                    for (let i in modules.cs.currentSheet.sheet.weights) {
                                        if (!cols[i]) {
                                            delete modules.cs.currentSheet.sheet.weights[i];
                                        }
                                    }
                                    if ($.trim(result.colWeight)) {
                                        modules.cs.currentSheet.sheet.weights[$.trim(result.colName)] = $.trim(result.colWeight);
                                    } else {
                                        delete modules.cs.currentSheet.sheet.weights[$.trim(result.colName)];
                                    }
                                    loadingStart();
                                    PUT("cs", "sheet", false, {
                                        "sheet": modules.cs.currentSheet.sheet.sheet,
                                        "date": modules.cs.currentSheet.sheet.date,
                                        "data": $.trim(JSON.stringify(modules.cs.currentSheet.sheet, null, 4)),
                                    }).
                                    fail(FAIL).
                                    done(() => {
                                        message(i18n("cs.sheetWasSaved"));
                                    });
                                    break;
                                }
                            }
                        },
                    });
                });

                $(".colMenuAssignAll").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let logins = [];
                    let col_name = "";

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            logins = modules.cs.currentSheet.sheet.data[i].logins;
                            col_name = modules.cs.currentSheet.sheet.data[i].col;
                            break;
                        }
                    }

                    if (col_name && logins) {
                        loadingStart();
                        let bulk = {
                            project: modules.cs.currentSheet.sheet.project,
                            query: { },
                            action: modules.cs.currentSheet.sheet.setAssignedAction,
                            set: { }
                        };

                        bulk.query[modules.cs.currentSheet.sheet.fields.sheet] = modules.cs.currentSheet.sheet.sheet;
                        bulk.query[modules.cs.currentSheet.sheet.fields.date] = modules.cs.currentSheet.sheet.date;
                        bulk.query[modules.cs.currentSheet.sheet.fields.col] = col_name;
                        bulk.set[modules.cs.currentSheet.sheet.fields.assigned] = logins;

                        PUT("tt", "bulkAction", false, bulk).
                        fail(FAIL).
                        done(() => {
                            loadingDone();
                            message(i18n("cs.done"));
                        });
                    } else {
                        mAlert(i18n("cs.loginsNotSet"));
                    }
                });

                $(".colClearAssigners").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let col_name = "";

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            col_name = modules.cs.currentSheet.sheet.data[i].col;
                            break;
                        }
                    }

                    if (col_name) {
                        loadingStart();
                        let bulk = {
                            project: modules.cs.currentSheet.sheet.project,
                            query: { },
                            action: modules.cs.currentSheet.sheet.setAssignedAction,
                            set: { },
                        };

                        bulk.query[modules.cs.currentSheet.sheet.fields.sheet] = modules.cs.currentSheet.sheet.sheet;
                        bulk.query[modules.cs.currentSheet.sheet.fields.date] = modules.cs.currentSheet.sheet.date;
                        bulk.query[modules.cs.currentSheet.sheet.fields.col] = col_name;
                        bulk.set[modules.cs.currentSheet.sheet.fields.assigned] = [];

                        PUT("tt", "bulkAction", false, bulk).
                        fail(FAIL).
                        done(() => {
                            loadingDone();
                            message(i18n("cs.done"));
                        });
                    } else {
                        mAlert(i18n("cs.columnNotFound"));
                    }
                });

                $(".colListIssues").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let rows = "";

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            rows = modules.cs.currentSheet.sheet.data[i].rows;
                            break;
                        }
                    }

                    let issues = [];

                    for (let i in modules.cs.issues) {
                        if (md5(modules.cs.issues[i][modules.cs.currentSheet.sheet.fields.col]) == col && modules.cs.issues[i][modules.cs.currentSheet.sheet.fields.map]) {
                            issues.push(modules.cs.issues[i]);
                        }
                    }

                    issues.sort((a, b) => {
                        return rows.indexOf(a[modules.cs.currentSheet.sheet.fields.row]) - rows.indexOf(b[modules.cs.currentSheet.sheet.fields.row]);
                    });

                    $("#modalBody").parent().removeClass("modal-sm modal-lg modal-xl");
                    $("#modalBody").parent().addClass("modal-xl");

                    let h = '';

                    h += `<div class="card-header pointer" id="modalHeader">`;
                    h += `<h3 class="card-title text-bold">`;
                    h += i18n("cs.list");
                    h += `</h3>`;
                    h += `<button type="button" class="btn btn-danger btn-xs btn-tool-rbt-right ml-2 float-right modalFormCancel" data-dismiss="modal" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>`;
                    h += `</div>`;

                    h += '<div class="card mt-0 mb-0" style="max-height: calc(100vh - 140px);">';
                    h += `<div class="card-body table-responsive p-2" style="overflow-x: hidden!important;">`;
                    h += '<table width="100%" class="datatable" style="">';

                    for (let i in issues) {
                        h += '<tr>';
                        h += '<td nowrap class="pl-2 pr-2">' + issues[i][modules.cs.currentSheet.sheet.fields.row] + '</td>';
                        h += '<td nowrap class="pl-2 pr-2"><a href="?#tt&issue=' + issues[i].issueId + '">' + issues[i].issueId + '</a></td>';
                        if (typeof modules.cs.currentSheet.sheet.fields.list == "string") {
                            h += '<td style="width: 100%;" class="pl-2 pr-2">' + (issues[i][modules.cs.currentSheet.sheet.fields.list] ? issues[i][modules.cs.currentSheet.sheet.fields.list] : '?') + '</td>';
                        } else
                        if (typeof modules.cs.currentSheet.sheet.fields.list == "object") {
                            let l = 0;
                            for (let j = 0; j < modules.cs.currentSheet.sheet.fields.list.length - 1; j++) {
                                l++;
                                h += '<td nowrap class="pl-2 pr-2">' + (issues[i][modules.cs.currentSheet.sheet.fields.list[j]] ? issues[i][modules.cs.currentSheet.sheet.fields.list[j]] : '?') + '</td>';
                            }
                            h += '<td style="width: 100%;" class="pl-2 pr-2">' + (issues[i][modules.cs.currentSheet.sheet.fields.list[l]] ? issues[i][modules.cs.currentSheet.sheet.fields.list[l]] : '?') + '</td>';
                        }
                        h += '</tr>';
                    }

                    h += '</table>';
                    h += '</div>';
                    h += '</div>';

                    modal(h);

                    $(".modalFormCancel").off("click").on("click", () => {
                        $('#modal').modal('hide');
                    });

                    $("#modal").draggable({
                        handle: "#modalHeader",
                    });
                });

                $(".colMapIssues").off("click").on("click", function () {
                    let col = $(this).attr("data-col");

                    let markers = "";
                    let rows = "";

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (md5(modules.cs.currentSheet.sheet.data[i].col) == col) {
                            rows = modules.cs.currentSheet.sheet.data[i].rows;
                            break;
                        }
                    }

                    let issues = [];

                    for (let i in modules.cs.issues) {
                        if (md5(modules.cs.issues[i][modules.cs.currentSheet.sheet.fields.col]) == col && modules.cs.issues[i][modules.cs.currentSheet.sheet.fields.map]) {
                            issues.push(modules.cs.issues[i]);
                        }
                    }

                    issues.sort((a, b) => {
                        return rows.indexOf(a[modules.cs.currentSheet.sheet.fields.row]) - rows.indexOf(b[modules.cs.currentSheet.sheet.fields.row]);
                    });

                    for (let i in issues) {
                        markers += issues[i][modules.cs.currentSheet.sheet.fields.map].coordinates[1] + "," + issues[i][modules.cs.currentSheet.sheet.fields.map].coordinates[0] + "," + issues[i][modules.cs.currentSheet.sheet.fields.row] + "|";
                    }

                    window.location.href = navigateUrl("map", {
                        markersLine: markers
                    });
                });

                $(".dataCell").off("click").on("click", function () {
                    let cell = $(this);

                    if ($(".spinner-small").length) {
                        return;
                    }

                    if (cell.hasClass(modules.cs.currentSheet.sheet.emptyClass)) {
                        return;
                    }

                    if (cell.hasClass(modules.cs.currentSheet.sheet.reservedClass)) {
                        if (AVAIL("cs", "reserveCell", "DELETE") && cell.attr("data-login") != lStore("_login")) {
                            mYesNo(i18n("cs.confirmUnReserve"), i18n("cs.action"), () => {
                                cell.addClass("spinner-small");

                                DELETE("cs", "reserveCell", false, {
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
                                    modules.cs.idle = true;
                                    cell.removeClass("spinner-small");
                                });
                            });
                        } else
                        if (cell.attr("data-login") == lStore("_login")) {
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
                                    modules.cs.idle = true;
                                    cell.removeClass("spinner-small");
                                });
                            }, i18n("cs.coordinate"), i18n("cs.unReserve"));
                        }
                    } else
                    if (cell.hasClass(modules.cs.currentSheet.sheet.blockedClass)) {
                        if (cell.attr("data-login") == lStore("_login")) {
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
                                modules.cs.idle = true;
                                cell.removeClass("spinner-small");
                            });
                        }
                    } else {
                        cell.addClass("spinner-small");

                        PUT("cs", "cell", false, {
                            action: "claim",
                            step: 0,
                            sheet: md5($("#csSheet").val()),
                            date: md5($("#csDate").val()),
                            col: cell.attr("data-col"),
                            row: cell.attr("data-row"),
                            uid: cell.attr("data-uid"),
                            sid: modules.cs.sid,
                            expire: 300,
                        }).
                        fail(FAIL).
                        fail(() => {
                            modules.cs.idle = true;
                            cell.removeClass("spinner-small");
                        });
                    }
                });

                for (let i in modules.cs.currentSheet.cells) {
                    switch (modules.cs.currentSheet.cells[i].mode) {
                        case "claimed":
                            if (modules.cs.currentSheet.sheet.specialRowClass) {
                                $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").removeClass(modules.cs.currentSheet.sheet.specialRowClass);
                            }
                            $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").addClass(modules.cs.currentSheet.sheet.blockedClass).attr("data-login", modules.cs.currentSheet.cells[i].login).attr("data-login-display", modules.users.login2name(modules.cs.currentSheet.cells[i].login));
                            break;

                        case "reserved":
                            if (modules.cs.currentSheet.sheet.specialRowClass) {
                                $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").removeClass(modules.cs.currentSheet.sheet.specialRowClass);
                            }
                            $(".dataCell[data-uid=" + modules.cs.currentSheet.cells[i].uid + "]").addClass(modules.cs.currentSheet.sheet.reservedClass).attr("data-login", modules.cs.currentSheet.cells[i].login).attr("data-login-display", modules.users.login2name(modules.cs.currentSheet.cells[i].login) + (modules.cs.currentSheet.cells[i].comment ? ("\n[" + modules.cs.currentSheet.cells[i].comment + "]") : ""));
                            break;
                    }
                }

                $(".csIssueSpan").off("click").on("click", function (e) {
/*
                    let cell = $(this);
                    window.location.href = "?#tt&issue=" + cell.text();
*/
                    e.stopPropagation();
                });

                $(".csPart").off("click").on("click", function () {
                    let part = $(this).text();

                    let cols = [];

                    for (let i in modules.cs.currentSheet.sheet.data) {
                        if (modules.cs.currentSheet.sheet.data[i].part == part) {
                            cols.push({
                                id: modules.cs.currentSheet.sheet.data[i].col,
                                text: modules.cs.currentSheet.sheet.data[i].col,
                                checked: modules.cs.currentSheet.sheet.data[i].hidden !== true,
                            });
                        }
                    }

                    cols.sort((a, b) => {
                        if (a.id > b.id) {
                            return 1;
                        }
                        if (a.id < b.id) {
                            return -1;
                        }
                        return 0;
                    });

                    cardForm({
                        title: i18n("cs.partCols"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        fields: [
                            {
                                id: "partCols",
                                type: "multiselect",
                                title: i18n("cs.cols"),
                                options: cols,
                                validate: v => {
                                    return v.length >= 1;
                                }
                            },
                        ],
                        callback: result => {
                            for (let i in modules.cs.currentSheet.sheet.data) {
                                if (modules.cs.currentSheet.sheet.data[i].part == part) {
                                    modules.cs.currentSheet.sheet.data[i].hidden = result.partCols.indexOf(modules.cs.currentSheet.sheet.data[i].col) < 0;
                                }
                            }
                            loadingStart();
                            PUT("cs", "sheet", false, {
                                "sheet": modules.cs.currentSheet.sheet.sheet,
                                "date": modules.cs.currentSheet.sheet.date,
                                "data": $.trim(JSON.stringify(modules.cs.currentSheet.sheet, null, 4)),
                            }).
                            fail(FAIL).
                            done(() => {
                                message(i18n("cs.sheetWasSaved"));
                            });
                        },
                    });
                });

                modules.cs.idle = true;
                loadingDone();
            } else {
                $("#mainForm").html(i18n("cs.notFound"));
                modules.cs.idle = true;
                loadingDone();
            }
        }

        function loadSheet() {
            GET("cs", "sheets").
            fail(FAIL).
            fail(() => {
                modules.cs.idle = true;
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

                sheets.sort();
                dates.sort();

                sheetsOptions = "";
                for (let i in sheets) {
                    if (sheets[i] == lStore("_sheet_name")) {
                        sheetsOptions += "<option selected='selected' style='font-weight: bold;'>" + escapeHTML(sheets[i]) + "</option>";
                    } else {
                        sheetsOptions += "<option>" + escapeHTML(sheets[i]) + "</option>";
                    }
                }

                datesOptions = "";
                for (let i in dates) {
                    if (dates[i] == lStore("_sheet_date")) {
                        datesOptions += "<option selected='selected' style='font-weight: bold;'>" + escapeHTML(dates[i]) + "</option>";
                    } else {
                        datesOptions += "<option>" + escapeHTML(dates[i]) + "</option>";
                    }
                }

                let rtd = "";

                rtd += `<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="csSheet" class="form-control select-arrow right-top-select top-input">${sheetsOptions}</select></div></div></form>`;
                rtd += `<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="csDate" class="form-control select-arrow right-top-select top-input">${datesOptions}</select></div></div></form>`;

                if (AVAIL("cs", "sheet", "PUT")) {
                    rtd += `<li class="nav-item nav-item-back-hover"><span id="cloneCSsheet" class="nav-link pointer" role="button" title="${i18n("cs.cloneSheet")}"><i class="fas fa-lg fa-fw fa-clone"></i></span></li>`;
                    rtd += `<li class="nav-item nav-item-back-hover"><span id="addCSsheet" class="nav-link pointer" role="button" title="${i18n("cs.addSheet")}"><i class="fas fa-lg fa-fw fa-plus-square"></i></span></li>`;
                    rtd += `<li class="nav-item nav-item-back-hover"><span id="editCSsheet" class="nav-link pointer" role="button" title="${i18n("cs.editSheet")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></li>`;
                    rtd += `<li class="nav-item nav-item-back-hover"><span id="deleteCSsheet" class="nav-link pointer" role="button" title="${i18n("cs.deleteSheet")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></li>`;
                }

                $("#rightTopDynamic").html(rtd);

                $("#cloneCSsheet").off("click").on("click", () => {
                    cardForm({
                        title: i18n("cs.cloneSheet"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        fields: [
                            {
                                id: "date",
                                type: "date",
                                title: i18n("cs.date"),
                                return: "asis",
                                placeholder: i18n("cs.date"),
                                validate: v => {
                                    return $.trim(v) !== "";
                                }
                            },
                        ],
                        callback: result => {
                            lStore("_sheet_date", result.date);
                            loadingStart();
                            modules.cs.currentSheet.sheet.date = result.date;
                            PUT("cs", "sheet", false, {
                                "sheet": modules.cs.currentSheet.sheet.sheet,
                                "date": modules.cs.currentSheet.sheet.date,
                                "data": $.trim(JSON.stringify(modules.cs.currentSheet.sheet)),
                            }).
                            fail(FAIL).
                            done(() => {
                                message(i18n("cs.sheetWasSaved"));
                                window.location.href = "?#cs&_=" + Math.random();
                            }).
                            always(() => {
                                loadingDone();
                            });
                        },
                    });
                });

                $("#addCSsheet").off("click").on("click", () => {
                    let sheetsOptions = [];

                    for (let i in sheets) {
                        sheetsOptions.push({
                            id: sheets[i],
                            text: sheets[i],
                        });
                    }

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
                                options: sheetsOptions,
                                validate: v => {
                                    return $.trim(v) !== "";
                                }
                            },
                            {
                                id: "date",
                                type: "date",
                                title: i18n("cs.date"),
                                return: "asis",
                                placeholder: i18n("cs.date"),
                                validate: v => {
                                    return $.trim(v) !== "";
                                }
                            },
                        ],
                        callback: result => {
                            lStore("_sheet_name", result.sheet);
                            lStore("_sheet_date", result.date);
                            window.location.href = "?#cs.sheet&sheet=" + encodeURIComponent(result.sheet) + "&date=" + encodeURIComponent(result.date);
                        },
                    });
                });

                $("#editCSsheet").off("click").on("click", () => {
                    if ($("#csSheet").val() && $("#csDate").val()) {
                        window.location.href = "?#cs.sheet&sheet=" + encodeURIComponent($("#csSheet").val()) + "&date=" + encodeURIComponent($("#csDate").val());
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
                            done(() => {
                                modules.cs.renderCS();
                            });
                        })
                    }
                });

                $("#csSheet").off("change").on("change", () => {
                    lStore("_sheet_name", $("#csSheet").val());
                    modules.cs.renderCS();
                });

                $("#csDate").off("change").on("change", () => {
                    lStore("_sheet_date", $("#csDate").val());
                    modules.cs.renderCS();
                });

                if ($("#csSheet").val() && $("#csDate").val()) {
                    QUERY("cs", "sheet", {
                        "sheet": $("#csSheet").val(),
                        "date": $("#csDate").val(),
                        "extended": 1,
                    }).
                    fail(FAIL).
                    fail(() => {
                        modules.cs.idle = true;
                        $("#mainForm").html(i18n("cs.errorLoadingSheet"));
                        loadingDone();
                    }).
                    done(response => {
                        modules.cs.cols = [];
                        modules.cs.rows = [];
                        modules.cs.colsMd5 = {};
                        modules.cs.rowsMd5 = {};

                        modules.cs.currentSheet = response.sheet;

                        if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.expireCondition) {
                            modules.cs.cellExpired = new Function ("sheetDate", "cellTime", `return ${modules.cs.currentSheet.sheet.expireCondition};`);
                        } else {
                            modules.cs.cellExpired = new Function ("sheetDate", "cellTime", `return false;`);
                        }

                        if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.doneCondition) {
                            modules.cs.issueDone = new Function ("issue", `return ${modules.cs.currentSheet.sheet.doneCondition};`);
                        } else {
                            modules.cs.issueDone = new Function ("issue", `return false;`);
                        }

                        if (modules.cs.currentSheet && modules.cs.currentSheet.sheet && modules.cs.currentSheet.sheet.closedCondition) {
                            modules.cs.issueClosed = new Function ("issue", `return ${modules.cs.currentSheet.sheet.closedCondition};`);
                        } else {
                            modules.cs.issueClosed = new Function ("issue", `return false;`);
                        }

                        loadIssues(() => {
                            renderSheet(response);
                        });
                    });
                } else {
                    $("#mainForm").html(i18n("cs.notFound"));
                    modules.cs.idle = true;
                    loadingDone();
                }
            });
        }

        modules.users.loadUsers().
        fail(FAIL).
        fail(() => {
            modules.cs.idle = true;
            $("#mainForm").html(i18n("cs.errorLoadingSheet"));
        }).
        fail(loadingDone).
        done(loadSheet);
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("cs.cs");

        if (params.sheet && params.date) {
            lStore("_sheet_name", params.sheet);
            lStore("_sheet_date", params.date);
        }

        modules.cs.highlight = params.highlight;

        if (modules.cs.menuItem) {
            $("#" + modules.cs.menuItem).children().first().attr("href", "?#cs&_=" + Math.random());
        }

        modules.cs.renderCS();
    },

    search: function (s) {
        modules.tt.search(s);
    },
}).init();