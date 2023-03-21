({
    init: function () {
        if (AVAIL("cs", "sheets")) {
            leftSide("fas fa-fw fa-table", i18n("cs.cs"), "#cs", "tt");
        }

        if (AVAIL("cs", "sheet", "PUT")) {
            loadSubModules("cs", [
                "sheet",
            ], this);
        } else {
            moduleLoaded("cs", this);
        }
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("cs.cs");

        GET("cs", "sheets").
        fail(FAIL).
        fail(() => {
            $("#mainForm").html(i18n("cs.csNotFound"));
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

            rtd += `<div class="input-group input-group-sm mr-2" style="width: 150px;"><select id="csSheet" class="form-control">${sheetsOptions}</select></div>`;
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
                    console.log($("#csSheet").val(), $("#csDate").val());
                }
            });

            if ($("#csSheet").val() && $("#csDate").val()) {
                QUERY("cs", "sheet", {
                    "sheet": $("#csSheet").val(),
                    "date": $("#csDate").val(),
                    "extended": 1,
                }).
                fail(FAIL).
                fail(loadingDone).
                fail(() => {
                    $("#mainForm").html(i18n("cs.csNotFound"));
                }).
                done(response => {
                    let cols = [];
                    let rows = [];
                    let colsMd5 = {};
                    let rowsMd5 = {};

                    if (response && response.sheet && response.sheet.sheet && response.sheet.sheet.data) {
                        let s = response.sheet.sheet.data;
                        for (let i in s) {
                            if (cols.indexOf(s[i].col) < 0) {
                                cols.push(s[i].col);
                                colsMd5[md5(s[i].col)] = s[i].col;
                            }
                            for (let j in s[i].rows) {
                                if (rows.indexOf(j) < 0) {
                                    rows.push(j);
                                    rowsMd5[md5(j)] = j;
                                }
                            }
                        }

                        cols.sort();
                        rows.sort();

                        let h = '';
                        h += '<table width="100%" class="mt-3 table table-hover table-bordered">';
                        h += '<thead>';
                        h += '<tr>';
                        h += '<td>&nbsp;</td>';
                        for (let i in cols) {
                            let c = false;
                            for (let j in s) {
                                if (cols[i] == s[j].col) {
                                    c = s[j];
                                }
                            }
                            if (c && c.class) {
                                h += '<td class="' + c.class + '">' + escapeHTML(cols[i]) + '</td>';
                            } else {
                                h += '<td>' + escapeHTML(cols[i]) + '</td>';
                            }
                        }
                        h += '</tr>';
                        h += '</thead>';
                        h += '<tbody>';
                        for (let i in rows) {
                            h += '<tr>';
                            if (response.sheet.sheet.timeClass) {
                                h += '<td class="' + response.sheet.sheet.timeClass + '">' + escapeHTML(rows[i]) + '</td>';
                            } else {
                                h += '<td>' + escapeHTML(rows[i]) + '</td>';
                            }
                            for (let j in cols) {
                                let f = false;
                                for (let k in s) {
                                    if (cols[j] == s[k].col) {
                                        for (let l in s[k].rows) {
                                            if (l == rows[i]) {
                                                f = true;
                                                if (s[k].rows[l].class) {
                                                    h += '<td class="' + s[k].rows[l].class + ' dataCell" data-col="' + md5(cols[j]) + '" data-row="' + md5(rows[i]) + '"></td>';
                                                } else {
                                                    h += '<td class="dataCell" data-col="' + md5(cols[j]) + '" data-row="' + md5(rows[i]) + '"></td>';
                                                }
                                                break;
                                            }
                                        }
                                        break;
                                    }
                                }
                                if (!f) {
                                    if (response.sheet.sheet.emptyClass) {
                                        h += '<td class="' + response.sheet.sheet.emptyClass + '" data-col="' + md5(cols[j]) + '" data-row="' + md5(rows[i]) + '"></td>';
                                    } else {
                                        h += '<td data-col="' + md5(cols[j]) + '" data-row="' + md5(rows[i]) + '"></td>';
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
                            let login = md5($.cookie("_login"));

                            if (cell.hasClass(response.sheet.sheet.reservedClass)) {

                            } else
                            if (cell.hasClass(login) || !cell.hasClass("login")) {
                                if (cell.hasClass(response.sheet.sheet.blockedClass)) {
                                    let cl = cell.attr("class").split(" ");
                                    for (let i in cl) {
                                        if (cl[i].substring(0, 3) == "bg-") {
                                            cell.removeClass([ cl[i], "login", login ]);
                                        }
                                    }
                                    if (cell.attr("data-class")) {
                                        cell.addClass(cell.attr("data-class"));
                                    }
                                } else {
                                    let cl = cell.attr("class").split(" ");
                                    let b = [];
                                    for (let i in cl) {
                                        if (cl[i].substring(0, 3) == "bg-") {
                                            cell.removeClass([ cl[i], login, "login" ]);
                                            b.push(cl[i]);
                                        }
                                    }
                                    if (b.length) {
                                        cell.attr("data-class", b.join(" "));
                                    } else {
                                        cell.attr("data-class", "");
                                    }
                                    $(".dataCell." + response.sheet.sheet.blockedClass + "." + login).each(function () {
                                        let c = $(this);
                                        let cl = c.attr("class").split(" ");
                                        for (let i in cl) {
                                            if (cl[i].substring(0, 3) == "bg-") {
                                                c.removeClass(cl[i]);
                                            }
                                        }
                                        if (c.attr("data-class")) {
                                            c.addClass(c.attr("data-class"));
                                        }
                                    });
                                    cell.addClass([ response.sheet.sheet.blockedClass, login, "login" ]);
                                    console.log(colsMd5[cell.attr("data-col")], rowsMd5[cell.attr("data-row")]);
                                }
                            }
                        });
                    } else {
                        $("#mainForm").html(i18n("cs.csNotFound"));
                    }
                    loadingDone();
                });
            } else {
                $("#mainForm").html(i18n("cs.csNotFound"));
                loadingDone();
            }
        });
    },
}).init();