({
    meta: {},

    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt", "tt");
        }
        loadSubModules("tt", [
            "issue",
            "settings",
        ], this);
    },

    issueField2FormFieldEditor: function (issue, field, projectId) {

        function peoples(project, withGroups, withUsers) {
            let p = [];

            let already = {
                "admin": true,
            };

            if (withGroups) {
                for (let i in project.groups) {
                    for (let j in modules.groups.meta) {
                        if (modules.groups.meta[j].gid == project.groups[i].gid && !already[modules.groups.meta[j].acronym]) {
                            already[modules.groups.meta[j].acronym] = true;
                            p.push({
                                id: modules.groups.meta[j].acronym,
                                text: modules.groups.meta[j].name + " [" + i18n("groups.group") + "]",
                            });
                        }
                    }
                }
            }

            if (withUsers) {
                for (let i in project.users) {
                    for (let j in modules.users.meta) {
                        if (modules.users.meta[j].uid == project.users[i].uid && !already[modules.users.meta[j].login]) {
                            already[modules.users.meta[j].login] = true;
                            if (project.users[i].level > 0) {
                                p.push({
                                    id: modules.users.meta[j].login,
                                    text: modules.users.meta[j].realName?modules.users.meta[j].realName:modules.users.meta[j].login,
                                });
                            }
                        }
                    }
                }
            }

            return p;
        }

        let fieldId;

        if (typeof field === "object") {
            fieldId = field.field;
        } else{
            fieldId = field;
        }

        let tags = [];
        for (let i in modules.tt.meta.tags) {
            if (modules.tt.meta.tags[i].projectId == projectId) {
                tags.push({
                    id: modules.tt.meta.tags[i].tag,
                    text: modules.tt.meta.tags[i].tag,
                });
            }
        }

        let project;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
            }
        }

        if (fieldId.substring(0, 4) !== "_cf_") {
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

                case "description":
                    return {
                        id: "description",
                        type: "area",
                        title: i18n("tt.description"),
                        placeholder: i18n("tt.description"),
                        value: (issue && issue.description)?issue.description:"",
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "resolution":
                    let resolutions = [];

                    for (let i in modules.tt.meta.resolutions) {
                        if (project.resolutions.indexOf(modules.tt.meta.resolutions[i].resolutionId) >= 0) {
                            resolutions.push({
                                id: modules.tt.meta.resolutions[i].alias,
                                text: modules.tt.meta.resolutions[i].resolution,
                            });
                        }
                    }

                    return {
                        id: "resolution",
                        type: "select2",
                        title: i18n("tt.resolution"),
                        options: resolutions,
                        value: (issue && issue.resolution)?issue.resolution:-1,
                    };

                case "status":
                    let statuses = [];

                    for (let i in modules.tt.meta.statuses) {
                        statuses.push({
                            id: modules.tt.meta.statuses[i].status,
                            text: modules.tt.meta.statuses[i].statusDisplay?modules.tt.meta.statuses[i].statusDisplay:modules.tt.meta.statuses[i].status,
                        });
                    }

                    return {
                        id: "status",
                        type: "select2",
                        title: i18n("tt.status"),
                        options: statuses,
                        value: (issue && issue.status)?issue.status:-1,
                    };

                case "tags":
                    return {
                        id: "tags",
                        type: "select2",
                        tags: true,
                        createTags: false,
                        multiple: true,
                        title: i18n("tt.tags"),
                        placeholder: i18n("tt.tags"),
                        options: tags,
                    };

                case "assigned":
                    return {
                        id: "assigned",
                        type: "select2",
                        multiple: true,
                        title: i18n("tt.assigned"),
                        placeholder: i18n("tt.assigned"),
                        options: peoples(project, true, true),
                    };

                case "watchers":
                    return {
                        id: "watchers",
                        type: "select2",
                        multiple: true,
                        title: i18n("tt.watchers"),
                        placeholder: i18n("tt.watchers"),
                        options: peoples(project, false, true),
                    };

                case "attachments":
                    return {
                        id: "attachments",
                        type: "files",
                        title: i18n("tt.attachments"),
                        mimeTypes: JSON.parse(project.allowedMimeTypes),
                        maxSize: project.maxFileSize,
                    };
            }
        } else {
            // custom field
            fieldId = fieldId.substring(4);

            let cf = false;
            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].field === fieldId) {
                    cf = modules.tt.meta.customFields[i];
                    break;
                }
            }

            if (cf) {
                let validate = false;
                if (cf.required && !cf.regex) {
                    validate = new Function ("v", `return v && $.trim(v) !== "";`);
                } else
                if (!cf.required && cf.regex) {
                    validate = new Function ("v", `return /${cf.regex}/.test(v);`);
                } else
                if (cf.required && cf.regex) {
                    validate = new Function ("v", `return v && $.trim(v) !== "" && /${cf.regex}/.test(v);`);
                }

                let options = [];

                switch (cf.type) {
                    case "text":
                        if ([ "text", "number", "area", "email", "tel", "date", "time", "datetime-local", "yesno" ].indexOf(cf.editor) < 0) {
                            cf.editor = "text";
                        }

                        return {
                            id: "_cf_" + fieldId,
                            type: cf.editor,
                            title: cf.fieldDisplay,
                            placeholder: cf.fieldDisplay,
                            hint: cf.fieldDescription?cf.fieldDescription:false,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:"",
                            validate: validate,
                        }

                    case "select":
                        for (let i in cf.options) {
                            options.push({
                                id: cf.options[i].option,
                                text: cf.options[i].optionDisplay,
                            });
                        }
                        return {
                            id: "_cf_" + fieldId,
                            type: "select2",
                            title: cf.fieldDisplay,
                            placeholder: cf.fieldDisplay,
                            hint: cf.fieldDescription?cf.fieldDescription:false,
                            options: options,
                            multiple: cf.format.indexOf("multiple") >= 0,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[],
                            validate: validate,
                        }

                    case "users":
                        if (cf.format.split(" ").includes("users")) {
                            options = peoples(project, false, true);
                        } else
                        if (cf.format.split(" ").includes("groups")) {
                            options = peoples(project, true, false);
                        } else
                        if (cf.format.split(" ").includes("usersAndGroups")) {
                            options = peoples(project, true, true);
                        }
                        return {
                            id: "_cf_" + fieldId,
                            type: "select2",
                            title: cf.fieldDisplay,
                            placeholder: cf.fieldDisplay,
                            hint: cf.fieldDescription?cf.fieldDescription:false,
                            options: options,
                            multiple: cf.format.indexOf("multiple") >= 0,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[],
                            validate: validate,
                        }
                }
            }
        }
    },

    issueField2Html: function (issue, field) {
        /*
            const sum = new Function('a', 'b', 'return a + b');

            console.log(sum(2, 6));
            // Expected output: 8
         */
        return issue[field];
    },

    tt: function (tt) {
        modules.tt.meta = tt["meta"];

        if (!modules.tt.viewers) {
            modules.tt.viewers = {};
        }

        for (let i in modules.tt.meta.viewers) {
            if (!modules.tt.viewers[modules.tt.meta.viewers[i].field]) {
                modules.tt.viewers[modules.tt.meta.viewers[i].field] = {};
            }
            modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'field', 'issue', modules.tt.meta.viewers[i].code);
        }
    },

    selectFilter: function (filter) {
        $.cookie("_tt_issue_filter_" + $("#ttProjectSelect").val(), filter, { expires: 3650, insecure: config.insecureCookie });
        window.location.href = `#tt&filter=${filter}`;
    },

    selectProject: function (project) {
        $.cookie("_project", project, { expires: 36500, insecure: config.insecureCookie });
        window.location.href = `#tt&project=${project}`;
    },

    viewIssue: function (issue) {
        window.location.href = `#tt&issue=${issue}`;
    },

    route: function (params) {
        loadingStart();

        $("#subTop").html("");
        $("#altForm").hide();

        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (params["issue"]) {
                GET("tt", "issue", params["issue"], true).
                done(r => {
                    console.log(r);
                    document.title = i18n("windowTitle") + " :: " + i18n("tt.tt") + " :: " + r.issue.issue["issue_id"];
                    $("#mainForm").html(r.issue.issue["issue_id"]);
                }).
                fail(FAIL).
                always(loadingDone)
            } else {
                let rtd = '';

                let current_project = params["project"]?params["project"]:$.cookie("_project");

                if (AVAIL("tt", "project", "POST")) {
                    rtd += `
                        <li class="nav-item">
                            <a href="#tt.settings&edit=projects" class="nav-link text-primary" role="button" style="cursor: pointer" title="${i18n("tt.settings")}">
                                <i class="fas fa-lg fa-fw fa-cog"></i>
                            </a>
                        </li>
                    `;
                }

                let pn = {};

                for (let i in modules.tt.meta.projects) {
                    pn[modules.tt.meta.projects[i].acronym] = modules.tt.meta.projects[i].project;
                }

                if (Object.keys(modules.tt.meta.myRoles).length) {
                    rtd += `
                        <div class="form-inline mr-3 mt-1">
                            <div class="input-group input-group-sm mr-2">
                                <select id="ttProjectSelect" class="form-control">
                                    `;
                                    for (let j in modules.tt.meta.myRoles) {
                                        if (j == current_project) {
                                            rtd += `<option selected="selected" value="${j}">${pn[j]} [${j}]</option>`;
                                        } else {
                                            rtd += `<option value="${j}">${pn[j]} [${j}]</option>`;
                                        }
                                    }
                                    rtd += `
                                </select>
                            </div>
                            <div class="input-group input-group-sm">
                                <input id="ttSearch" class="form-control" type="search" aria-label="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-default" id="ttSearchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                $("#rightTopDynamic").html(rtd);

                current_project = $("#ttProjectSelect").val();

                $("#ttProjectSelect").off("change").on("change", () => {
                    modules.tt.selectProject($("#ttProjectSelect").val());
                });

                let project = false;

                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].acronym == current_project) {
                        project = modules.tt.meta.projects[i];
                    }
                }

                if (Object.keys(modules.tt.meta.myRoles).length) {
                    $("#ttProjectSelect").css("width", $("#ttSearch").parent().css("width"));
                }

                let x = false;
                let f = false;

                try {
                    x = params["filter"]?params["filter"]:$.cookie("_tt_issue_filter_" + current_project);
                    f = modules.tt.meta.filters[x];
                } catch (e) {
                    //
                }

                let fcount = 0;
                let filters = `<span class="dropdown">`;

                filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">${f?f:i18n("tt.filter")}</span>`;
                filters += `<ul class="dropdown-menu" aria-labelledby="ttFilter">`;
                for (let i in project.filters) {
                    if (x == project.filters[i]) {
                        filters += `<li class="pointer dropdown-item tt_issues_filter text-bold" data-filter-name="${project.filters[i]}">${modules.tt.meta.filters[project.filters[i]] + " [" + project.filters[i] + "]"}</li>`;
                    } else {
                        filters += `<li class="pointer dropdown-item tt_issues_filter" data-filter-name="${project.filters[i]}">${modules.tt.meta.filters[project.filters[i]] + " [" + project.filters[i] + "]"}</li>`;
                    }
                    fcount++;
                }
                filters += `</ul></span>`;

                if (!fcount) {
                    filters = `<span class="text-bold text-warning">${i18n('tt.noFiltersAvailable')}</span>`;
                }

                $("#leftTopDynamic").html(`
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="javascript:void(0)" class="nav-link text-success text-bold createIssue">${i18n("tt.createIssue")}</a>
                    </li>
                `);

                $(".createIssue").off("click").on("click", () => {
                    modules.tt.issue.createIssue($("#ttProjectSelect").val());
                });

                document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");

                f = $.cookie("_tt_issue_filter_" + current_project);

                QUERY("tt", "issues", {
                    "project": current_project,
                    "filter": f?f:'',
                }, true).
                done(response => {
                    let issues = response.issues;

                    $("#mainForm").html(`
                        <div class="row m-1 mt-2">
                            <div class="col col-left">
                                ${filters}
                            </div>
                            <div class="col col-right mr-0" style="text-align: right" id="issuesPager">1 2 3 4</div>
                        </div>
                        <div class="ml-2 mr-2" id="issuesList"></div>
                    `);

                    $(".tt_issues_filter").off("click").on("click", function () {
                        modules.tt.selectFilter($(this).attr("data-filter-name"));
                    });

                    if (issues.issues) {
                        cardTable({
                            target: "#issuesList",
                            columns: [
                                {
                                    title: i18n("tt.issueId"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("tt.subject"),
                                    nowrap: true,
                                    fullWidth: true,
                                },
                            ],
                            rows: () => {
                                let rows = [];

                                for (let i = 0; i < issues.issues.length; i++) {
                                    rows.push({
                                        uid: issues.issues[i]["issue_id"],
                                        cols: [
                                            {
                                                data: issues.issues[i]["issue_id"],
                                                nowrap: true,
                                                click: modules.tt.viewIssue,
                                            },
                                            {
                                                data: issues.issues[i]["subject"],
                                                click: modules.tt.viewIssue,
                                            },
                                        ],
                                    });
                                }

                                return rows;
                            },
                        });
                    } else {
                        $("#issuesList").append(`<span class="ml-1 text-bold">${i18n("tt.noIssuesAvailable")}</span>`);
                    }
                }).
                fail(FAIL).
                always(loadingDone);
            }
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();