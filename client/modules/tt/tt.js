({
    meta: {},

    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt");
        }
        loadSubModules("tt", [
            "issue",
            "settings",
        ], this);
    },

    issueField2FormField: function (issue, field) {
        let fieldId;

        if (typeof field == "object") {
            fieldId = field.field;
        } else{
            fieldId = field;
        }

        if (isNaN(fieldId)) {
            // regular issue fields
            switch (fieldId) {
                case "subject":
                    return {
                        id: "subject",
                        type: "text",
                        title: i18n("tt.subject"),
                        placeholder: i18n("tt.subject"),
                        value: (issue && issue.subject)?issue.subject:"",
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };
                    break;
                case "description":
                    return {
                        id: "description",
                        type: "rich",
                        title: i18n("tt.description"),
                        placeholder: i18n("tt.description"),
                        value: (issue && issue.description)?issue.description:"",
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };
                    break;
                case "resolution":
                    break;
                case "tags":
                    break;
                case "assigned":
                    break;
                case "watchers":
                    break;
                case "plans":
                    break;
                case "checklist":
                    break;
            }
        } else {
            // custom fields
        }
    },

    tt: function (tt) {
        modules.tt.meta = tt["meta"];
        console.log(modules.tt.meta);
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        $("#leftTopDynamic").html(`
            <li class="nav-item d-none d-sm-inline-block">
                <a href="javascript:void(0)" class="nav-link text-success text-bold createIssue">${i18n("tt.createIssue")}</a>
            </li>
        `);

        $("#rightTopDynamic").html(`
            <li class="nav-item">
                <a href="#tt.settings&edit=projects" class="nav-link text-primary" role="button" style="cursor: pointer" title="${i18n("tt.settings")}">
                    <i class="fas fa-lg fa-fw fa-cog"></i>
                </a>
            </li>
        `);

        $(".createIssue").off("click").on("click", modules.tt.issue.createIssue);

        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
        $("#mainForm").html(i18n("tt.tt"));

        loadingDone();
    }
}).init();