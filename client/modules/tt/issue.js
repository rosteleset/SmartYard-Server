({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    createIssue: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {

            function workflowsByProject(project) {
                let w = [
                    {
                        id: "",
                        text: "-",
                    }
                ];

                if (project) {
                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].projectId == project) {
                            for (let j in modules.tt.meta.projects[i].workflows) {
                                let a = modules.tt.meta.projects[i].workflows[j];
                                for (let k in modules.tt.meta.workflowAliases) {
                                    if (modules.tt.meta.workflowAliases[k].workflow == modules.tt.meta.projects[i].workflows[j]) {
                                        a = modules.tt.meta.workflowAliases[k].alias;
                                        break;
                                    }
                                }
                                w.push({
                                    id: modules.tt.meta.projects[i].workflows[j],
                                    text: $.trim(a + " [" + modules.tt.meta.projects[i].workflows[j] + "]"),
                                    selected: $.cookie("_last_issue_workflow") == modules.tt.meta.projects[i].workflows[j],
                                });
                            }
                            break;
                        }
                    }
                }

                return w;
            }

            let projects = [];

            projects.push({
                id: "",
                text: "-",
            })

            for (let i in modules.tt.meta.projects) {
                projects.push({
                    id: modules.tt.meta.projects[i].projectId,
                    text: $.trim(modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]"),
                    selected: $.cookie("_last_issue_project") == modules.tt.meta.projects[i].projectId,
                });
            }

            let project = $.cookie("_last_issue_project")?$.cookie("_last_issue_project"):"";

            cardForm({
                title: i18n("tt.createIssue"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
                apply: "create",
                singleColumn: true,
                fields: [
                    {
                        id: "project",
                        type: "select2",
                        title: i18n("tt.project"),
                        options: projects,
                        minimumResultsForSearch: Infinity,
                        select: (el, id, prefix) => {
                            $(`#${prefix}workflow`).html("").select2({
                                data: workflowsByProject(el.val()),
                                minimumResultsForSearch: Infinity,
                                language: lang["_code"],
                            });
                        },
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                    },
                    {
                        id: "workflow",
                        type: "select2",
                        title: i18n("tt.workflow"),
                        minimumResultsForSearch: Infinity,
                        options: workflowsByProject(project),
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                    },
                ],
                callback: function (result) {
                    if (result.project && result.workflow) {
                        $.cookie("_last_issue_project", result.project, { expires: 36500, insecure: config.insecureCookie });
                        $.cookie("_last_issue_workflow", result.workflow, { expires: 36500, insecure: config.insecureCookie });
                    }
                    location.href = `#tt.issue&action=create&project=${result.project}&workflow=${result.workflow}`;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone)
    },

    issueField: function (custom, field, value) {

    },

    createIssueForm(projectId, workflow) {
        loadingStart();
        modules.users.loadUsers(() => {
            modules.groups.loadGroups(() => {
                QUERY("tt", "workflowCreateIssueTemplate", {
                    workflow: workflow,
                }).
                done(response => {
                    document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");

                    let projectName = projectId;
                    let project = false;
                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].projectId == projectId) {
                            project = modules.tt.meta.projects[i];
                            projectName = modules.tt.meta.projects[i].project?$.trim(modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]"):modules.tt.meta.projects[i].acronym;
                        }
                    }

                    let workflowAlias = workflow;
                    for (let i in modules.tt.meta.workflowAliases) {
                        if (modules.tt.meta.workflowAliases[i].workflow == workflow) {
                            workflowAlias = modules.tt.meta.workflowAliases[i].alias?$.trim(modules.tt.meta.workflowAliases[i].alias + " [" + workflow + "]"):workflow;
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
                            id: "workflowAlias",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.workflowAlias"),
                            value: workflowAlias,
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

                    let af = [];
                    if (response.template && response.template.fields) {
                        for (let i in response.template.fields) {
                            if (af.indexOf(response.template.fields[i]) < 0) {
                                let f = modules.tt.issueField2FormFieldEditor(false, response.template.fields[i], projectId);
                                if (f) {
                                    fields.push(f);
                                    af.push(response.template.fields[i]);
                                }
                            }
                        }
                    }

                    for (let i in project.customFields) {
                        for (let j in modules.tt.meta.customFields) {
                            if (modules.tt.meta.customFields[j].customFieldId === project.customFields[i]) {
                                if (af.indexOf("[cf]" + modules.tt.meta.customFields[j].field) < 0) {
                                    let f = modules.tt.issueField2FormFieldEditor(false, "[cf]" + modules.tt.meta.customFields[j].field, projectId);
                                    if (f) {
                                        fields.push(f);
                                        af.push("[cf]" + modules.tt.meta.customFields[j].field);
                                    }
                                }
                            }
                        }
                    }

                    cardForm({
                        title: i18n("tt.createIssueTitle"),
                        footer: true,
                        borderless: true,
                        target: "#mainForm",
                        apply: "create",
                        fields: fields,
                        callback: modules.tt.issue.doCreateIssue,
                        cancel: () => {
                            history.back();
                        },
                    });

                    loadingDone();
                }).
                fail(FAIL).
                fail(() => {
                    history.back();
                });
            });
        });
    },

    doCreateIssue: function (issue) {
        loadingStart();
        delete issue.projectName;
        delete issue.workflowAlias;
        issue.project = issue.projectAcronym;
        delete issue.projectAcronym;
        POST("tt", "issue", false, {
            issue: issue,
        }).
        done(result => {
            modules.tt.viewIssue(result.id);
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        GET("tt", "tt").
        fail(FAIL).
        fail(() => {
            history.back();
        }).
        done(modules.tt.tt).
        done(() => {
            switch (params.action) {
                case "create":
                    modules.tt.issue.createIssueForm(params.project, params.workflow);
                    break;
                default:
                    loadingDone();
                    page404();
                    break;
            }
        });
    },
}).init();