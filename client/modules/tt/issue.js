({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    createIssue: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {

            function workflowsByProject(project) {
                let w = [
                    {
                        id: "",
                        text: "-",
                    }
                ];

                if (project) {
                    for (let i in window.modules["tt"].meta.projects) {
                        if (window.modules["tt"].meta.projects[i].projectId == project) {
                            for (let j in window.modules["tt"].meta.projects[i].workflows) {
                                let a = "";
                                for (let k in window.modules["tt"].meta.workflowAliases) {
                                    if (window.modules["tt"].meta.workflowAliases[k].workflow == window.modules["tt"].meta.projects[i].workflows[j]) {
                                        a = window.modules["tt"].meta.workflowAliases[k].alias;
                                        break;
                                    }
                                }
                                w.push({
                                    id: window.modules["tt"].meta.projects[i].workflows[j],
                                    text: $.trim(a + " [" + window.modules["tt"].meta.projects[i].workflows[j] + "]"),
                                    selected: $.cookie("lastIssueWorkflow") == window.modules["tt"].meta.projects[i].workflows[j],
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

            for (let i in window.modules["tt"].meta.projects) {
                projects.push({
                    id: window.modules["tt"].meta.projects[i].projectId,
                    text: $.trim(window.modules["tt"].meta.projects[i].project + " [" + window.modules["tt"].meta.projects[i].acronym + "]"),
                    selected: $.cookie("lastIssueProject") == window.modules["tt"].meta.projects[i].projectId,
                });
            }

            let project = $.cookie("lastIssueProject")?$.cookie("lastIssueProject"):"";

            window.modules["tt"].meta.projects
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
                                language: window.lang["_code"],
                            });
                        }
                    },
                    {
                        id: "workflow",
                        type: "select2",
                        title: i18n("tt.workflow"),
                        minimumResultsForSearch: Infinity,
                        options: workflowsByProject(project),
                    },
                ],
                callback: function (result) {
                    if (result.project && result.workflow) {
                        $.cookie("lastIssueProject", result.project, { expires: 36500 });
                        $.cookie("lastIssueWorkflow", result.workflow, { expires: 36500 });
                    }
                    window.location.href = `#tt.issue&action=create&project=${result.project}&workflow=${result.workflow}`;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone)
    },

    createIssueForm(project, workflow) {
        loadingStart();
        QUERY("tt", "workflowCreateIssueTemplate", {
            workflow: workflow,
        }).
        done(response => {
            console.log(response);

            document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");

            let projectName = "";
            for (let i in window.modules["tt"].meta.projects) {
                if (window.modules["tt"].meta.projects[i].projectId == project) {
                    projectName = $.trim(window.modules["tt"].meta.projects[i].project + " [" + window.modules["tt"].meta.projects[i].acronym + "]");
                }
            }

            let workflowName = "";
            for (let i in window.modules["tt"].meta.workflowAliases) {
                if (window.modules["tt"].meta.workflowAliases[i].workflow == workflow) {
                    workflowName = $.trim(window.modules["tt"].meta.workflowAliases[i].alias + " [" + workflow + "]");
                }
            }

            cardForm({
                title: i18n("tt.createIssueTitle"),
                footer: true,
                borderless: true,
                target: "#mainForm",
                apply: "create",
                fields: [
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
                        id: "subject",
                        type: "text",
                        title: i18n("tt.subject"),
                        placeholder: i18n("tt.subject"),
                    },
                    {
                        id: "description",
                        type: "rich",
                        title: i18n("tt.description"),
                        placeholder: i18n("tt.description"),
                    },
                    {
                        id: "tags",
                        type: "select2",
                        tags: true,
                        createTags: false,
                        multiple: true,
                        title: i18n("tt.tags"),
                        placeholder: i18n("tt.tags"),
                        options: [
                            {
                                id: 1,
                                text: "one",
                            },
                            {
                                id: 2,
                                text: "two",
                            }
                        ]
                    }
                ],
                callback: function (result) {
                    console.log(result);
                },
                cancel: () => {
                    window.history.back();
                },
            });

            loadingDone();
        }).
        fail(FAIL).
        fail(() => {
            window.history.back();
        });
    },

    route: function (params) {
        $("#altForm").hide();

        GET("tt", "tt").
        fail(FAIL).
        fail(() => {
            window.history.back();
        }).
        done(window.modules["tt"].tt).
        done(() => {
            switch (params.action) {
                case "create":
                    window.modules["tt.issue"].createIssueForm(params.project, params.workflow);
                    break;
                default:
                    loadingDone();
                    page404();
                    break;
            }
        });
    },
}).init();