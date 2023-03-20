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
            $("#mainForm").html(i18n("cs.cs"));
        }).
        fail(loadingDone).
        done(r1 => {
            console.log(r1);
        });

        let rtd = "<div class='form-inline'>";

        rtd += `<div class="input-group input-group-sm mr-2" style="width: 150px;"><select id="csSheet" class="form-control"></select></div>`;
        rtd += `<div class="input-group input-group-sm" style="width: 150px;"><select id="csDate" class="form-control"></select></div>`;

        if (AVAIL("cs", "sheet", "PUT")) {
            rtd += `<div class="nav-item mr-0 pr-0 align-middle"><span id="addCSsheet" class="nav-link text-success mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.addSheet")}"><i class="far fa-lg fa-fw fa-plus-square"></i></span></div>`;
            rtd += `<div class="nav-item mr-0 pr-0"><span id="editCSsheet" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.editSheet")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></div>`;
            rtd += `<div class="nav-item mr-0 pr-0"><span id="deleteCSsheet" class="nav-link text-danger mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("cs.deleteSheet")}"><i class="far fa-lg fa-fw fa-minus-square"></i></span></div>`;
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

        loadingDone();
    },
}).init();