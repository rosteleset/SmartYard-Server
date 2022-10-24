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
                                let a = "";
                                for (let k in modules.tt.meta.workflowAliases) {
                                    if (modules.tt.meta.workflowAliases[k].workflow == modules.tt.meta.projects[i].workflows[j]) {
                                        a = modules.tt.meta.workflowAliases[k].alias;
                                        break;
                                    }
                                }
                                w.push({
                                    id: modules.tt.meta.projects[i].workflows[j],
                                    text: $.trim(a + " [" + modules.tt.meta.projects[i].workflows[j] + "]"),
                                    selected: $.cookie("lastIssueWorkflow") == modules.tt.meta.projects[i].workflows[j],
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
                    selected: $.cookie("lastIssueProject") == modules.tt.meta.projects[i].projectId,
                });
            }

            let project = $.cookie("lastIssueProject")?$.cookie("lastIssueProject"):"";

            cardForm({
                title: i18n("tt.createIssue"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
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
                        $.cookie("lastIssueProject", result.project, { expires: 36500 });
                        $.cookie("lastIssueWorkflow", result.workflow, { expires: 36500 });
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

                    let workflowName = workflow;
                    for (let i in modules.tt.meta.workflowAliases) {
                        if (modules.tt.meta.workflowAliases[i].workflow == workflow) {
                            workflowName = modules.tt.meta.workflowAliases[i].alias?$.trim(modules.tt.meta.workflowAliases[i].alias + " [" + workflow + "]"):workflow;
                        }
                    }

                    let fields = [
                        {
                            id: "project",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.project"),
                            value: projectName,
                        },
                        {
                            id: "workflow",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.workflow"),
                            value: workflowName,
                        },
                        {
                            id: "attachments",
                            type: "files",
                            title: i18n("tt.attachments"),
                            mimeTypes: [ "image/jpeg", "image/png", "application/pdf" ],
                            maxSize: 1024 * 1024,
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
                        callback: function (result) {
                            console.log(result);
                        },
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