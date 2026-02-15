({
    init: function () {
        if (AVAIL("tt", "issue", "POST") && modules.tt.menuItem && !config.disableCreateIssue) {
            let c = leftSide("far fa-fw fa-plus-square", i18n("tt.createIssue"), navigateUrl("tt.createIssue", false, { exclude: [ "_" ]}), "tt");
            $("#" + modules.tt.menuItem).before($("#" + c));
        }
        moduleLoaded("tt.createIssue", this);
    },

    createIssue: function (currentProject, parent, catalogByIssue) {
        let projects = [];

        projects.push({
            id: "-",
            text: "-",
        });

        for (let i in modules.tt.meta.projects) {
            projects.push({
                id: modules.tt.meta.projects[i].acronym,
                text: $.trim(modules.tt.meta.projects[i].project ? modules.tt.meta.projects[i].project : modules.tt.meta.projects[i].acronym),
                selected: currentProject == modules.tt.meta.projects[i].acronym || lStore("ttProject") == modules.tt.meta.projects[i].acronym,
            });
        }

        for (let i in projects) {
            if (projects[i].selected) {
                currentProject = projects[i].id;
            }
        }

        let workflows = [];

        for (let i in modules.tt.meta.workflows) {
            workflows[i] = modules.tt.meta.workflows[i].name ? modules.tt.meta.workflows[i].name : i;
        }

        function workflowsByProject(project, catalogByIssue) {
            let w = [
                {
                    id: "-",
                    text: "-",
                }
            ];

            if (project) {
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].acronym == project) {
                        for (let j in modules.tt.meta.projects[i].workflows) {
                            let wn = $.trim(workflows[modules.tt.meta.projects[i].workflows[j]] ? workflows[modules.tt.meta.projects[i].workflows[j]] : modules.tt.meta.projects[i].workflows[j]);
                            if (wn.charAt(0) != "#") {
                                w.push({
                                    id: modules.tt.meta.projects[i].workflows[j],
                                    text: wn,
                                    selected: (catalogByIssue && parent && parent.workflow == modules.tt.meta.projects[i].workflows[j]) || (!catalogByIssue && !parent && lStore("ttWorkflow") == modules.tt.meta.projects[i].workflows[j]),
                                });
                            }
                        }
                        break;
                    }
                }
            }

            return w;
        }

        function catalogByWorkflow(workflow, prefix, catalogByIssue) {
            let catalog = [{
                id: "-",
                text: "-",
            }];

            let x = false;

            if (catalogByIssue) {
                x = catalogByIssue;
            } else {
                for (let i in modules.tt.meta.workflows) {
                    if (i == workflow) {
                        if (modules.tt.meta.workflows[i].catalog) {
                            x = modules.tt.meta.workflows[i].catalog;
                        }
                        break;
                    }
                }
            }

            if (x) {
                let k = Object.keys(x);
                k.sort();
                for (let i in k) {
                    let u1 = x[k[i]];
                    let u2 = [];
                    let l2 = [];
                    for (let j in u1) {
                        u2.push(u1[j]);
                    }
                    u2.sort();
                    for (let j in u2) {
                        l2.push({
                            id: u2[j],
                            text: u2[j],
                        });
                    }
                    catalog.push({
                        text: k[i],
                        children: l2,
                    });
                }
            }

            $(`#${prefix}catalog`).html("").select2({
                data: catalog,
                minimumResultsForSearch: 0,
                language: lang["_code"],
            });

            return x;
        }

        cardForm({
            title: i18n("tt.createIssue"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            apply: "create",
            noHover: true,
            fields: [
                {
                    id: "project",
                    type: "select2",
                    title: i18n("tt.project"),
                    options: projects,
                    minimumResultsForSearch: Infinity,
                    select: (el, id, prefix) => {
                        $(`#${prefix}workflow`).html("").select2({
                            data: workflowsByProject(el.val(), catalogByIssue),
                            minimumResultsForSearch: Infinity,
                            language: lang["_code"],
                        });
                        if (catalogByWorkflow($(`#${prefix}workflow`).val(), prefix, catalogByIssue)) {
                            $(`#${prefix}catalog`).attr("disabled", false);
                        } else {
                            $(`#${prefix}catalog`).attr("disabled", true);
                        }
                    },
                    validate: v => {
                        return v && v !== '-' && v !== 'undefined';
                    },
                    readonly: !!parent,
                },
                {
                    id: "workflow",
                    type: "select2",
                    title: i18n("tt.workflowName"),
                    minimumResultsForSearch: Infinity,
                    options: workflowsByProject(currentProject, catalogByIssue),
                    select: (el, id, prefix) => {
                        if (catalogByWorkflow(el.val(), prefix, catalogByIssue)) {
                            $(`#${prefix}catalog`).attr("disabled", false);
                        } else {
                            $(`#${prefix}catalog`).attr("disabled", true);
                        }
                    },
                    validate: v => {
                        return v && v !== '-' && v !== 'undefined';
                    },
                    readonly: !!catalogByIssue,
                },
                {
                    id: "catalog",
                    type: "select2",
                    title: i18n("tt.catalog"),
                    minimumResultsForSearch: Infinity,
                    validate: (v, prefix) => {
                        return !!$(`#${prefix}catalog`).attr("disabled") || (!!v && v !== '-' && v !== 'undefined');
                    },
                },
            ],
            done: prefix => {
                if (catalogByWorkflow($(`#${prefix}workflow`).val(), prefix, catalogByIssue)) {
                    $(`#${prefix}catalog`).attr("disabled", false);
                } else {
                    $(`#${prefix}catalog`).attr("disabled", true);
                }
            },
            callback: result => {
                if (result.project && result.workflow) {
                    lStore("ttProject", result.project);
                    lStore("ttWorkflow", result.workflow);
                }
                modules.tt.createIssue.createIssueForm(result.project, result.workflow, result.catalog, false);
                // loadingStart();
                // navigateUrl("tt.createIssue", { project: result.project, workflow: result.workflow, catalog: result.catalog, parent: !!parent ? parent["issueId"] : false }, { run });
            },
            cancel: () => {
                window.history.back();
            }
        });
    },

    createIssueForm: function (currentProject, workflow, catalog, parent) {
        subTop();

        $("#leftTopDynamic").html("");
        $("#rightTopDynamic").html("");

        loadingStart();

        function ciForm(currentProject, workflow, catalog, parent) {
            QUERY("tt", "issueTemplate", {
                _id: workflow,
                catalog: catalog,
                parent: parent,
            }, true).
            done(response => {
                document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");

                let workflows = [];

                for (let i in modules.tt.meta.workflows) {
                    workflows[i] = (modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i);
                }

                let projectName = "";
                let project = false;
                let projectId = -1;

                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].acronym == currentProject) {
                        project = modules.tt.meta.projects[i];
                        projectName = modules.tt.meta.projects[i].project?modules.tt.meta.projects[i].project:modules.tt.meta.projects[i].acronym;
                        projectId = modules.tt.meta.projects[i].projectId;
                    }
                }

                let fields = [
                    {
                        id: "projectName",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.project"),
                        value: projectName,
                    },
                    {
                        id: "projectAcronym",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.projectAcronym"),
                        value: project.acronym,
                        hidden: true,
                    },
                    {
                        id: "workflowName",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.workflowName"),
                        value: workflows[workflow],
                    },
                    {
                        id: "workflow",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.workflow"),
                        value: workflow,
                        hidden: true,
                    },
                ];

                if (catalog && catalog !== "-" && catalog !== true) {
                    fields.push({
                        id: "catalog",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.catalog"),
                        value: catalog,
                    });
                }

                if (parent && parent !== "-" && parent !== true) {
                    fields.push({
                        id: "parent",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.parent"),
                        value: parent,
                    });
                }

                let kx = [];
                let ky = {};

                for (let i in response.template.fields) {
                    let fx = ((typeof response.template.fields[i] == "string") ? response.template.fields[i] : i).toString();
                    if (fx.charAt(0) == '%') {
                        fx = fx.split('%');
                        kx[fx[1]] = fx[2];
                        ky[fx[2]] = (typeof response.template.fields[i] == "string") ? false : response.template.fields[i];
                    } else {
                        kx.push(fx);
                        ky[fx] = (typeof response.template.fields[i] == "string") ? false : response.template.fields[i];
                    }
                }

                for (let i in kx) {
                    let fi = modules.tt.issueField2FormFieldEditor(false, kx[i], projectId, ky[kx[i]]);
                    if (fi && kx[i] !== "comment" && kx[i] !== "optionalComment") {
                        fields.push(fi);
                    }
                }

                cardForm({
                    title: i18n("tt.createIssueTitle"),
                    footer: true,
                    borderless: true,
                    target: "#mainForm",
                    apply: "create",
                    fields: fields,
                    noHover: true,
                    callback: modules.tt.createIssue.doCreateIssue,
                    cancel: () => {
                        window.history.back();
                    },
                }).show();

                loadingDone();
            }).
            fail(FAIL).
            fail(() => {
                window.history.back();
            });
        }

        modules.users.loadUsers(() => {
            if (modules.groups) {
                modules.groups.loadGroups(() => {
                    ciForm(currentProject, workflow, catalog, parent);
                });
            } else {
                ciForm(currentProject, workflow, catalog, parent);
            }
        });
    },

    doCreateIssue: function (issue) {
        loadingStart();
        delete issue.projectName;
        delete issue.workflowName;
        issue.project = issue.projectAcronym;
        issue.markdown = modules.tt.markdown;
        delete issue.projectAcronym;
        POST("tt", "issue", false, {
            issue: issue,
        }).
        done(result => {
            window.location.href = navigateUrl("tt.issue", { issue: result.id });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (params.project && params.workflow) {
                modules.tt.createIssue.createIssueForm(params.project, params.workflow, params.catalog, params.parent);
            } else {
                loadingDone();
                modules.tt.createIssue.createIssue($("#ttProjectSelect").val() ? $("#ttProjectSelect").val() : lStore("ttProject"));
            }
        }).
        fail(FAIL).
        fail(loadingDone);
    },

}).init();
