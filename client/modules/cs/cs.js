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
                    console.log(response);
                    if (response && response.sheet && response.sheet.sheet && response.sheet.sheet.data) {
                        console.log(response.sheet.sheet.data);
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