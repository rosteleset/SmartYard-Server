({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.settings", this);
    },

    /*
        action functions
     */

    doAddProject: function (acronym, project) {
        loadingStart();
        POST("tt", "project", false, {
            acronym: acronym,
            project: project,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasAdded"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    doModifyProject: function (projectId, acronym, project) {
        loadingStart();
        PUT("tt", "project", projectId, {
            acronym: acronym,
            project: project,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    doDeleteProject: function (projectId) {
        loadingStart();
        DELETE("tt", "project", projectId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasDeleted"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    doSetWorkflowAlias: function (workflow, alias) {
        loadingStart();
        PUT("tt", "workflow", false, {
            workflow: workflow,
            alias: alias,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.workflowWasChanged"));
        }).
        always(window.modules["tt.settings"].renderAllWorkflows);
    },

    doSetProjectWorkflows: function (projectId, workflows) {
        loadingStart();
        PUT("tt", "project", projectId, {
            workflows: workflows,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    /*
        UI functions
     */

    deleteProject: function (projectId) {
        mConfirm(i18n("tt.confirmProjectDelete", projectId.toString()), i18n("confirm"), `danger:${i18n("tt.projectDelete")}`, () => {
            window.modules["tt.settings"].doDeleteProject(projectId);
        });
    },

    modifyProject: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(response => {
            cardForm({
                title: i18n("tt.projectEdit"),
                footer: true,
                borderless: true,
                topApply: true,
                fields: [
                    {
                        id: "projectId",
                        type: "text",
                        readonly: true,
                        value: response.project.projectId.toString(),
                        title: i18n("tt.projectId"),
                    },
                    {
                        id: "acronym",
                        type: "text",
                        value: response.project.acronym,
                        title: i18n("tt.projectAcronym"),
                        placeholder: i18n("tt.projectAcronym"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "project",
                        type: "text",
                        value: response.project.project,
                        title: i18n("tt.projectProject"),
                        placeholder: i18n("tt.projectProject"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "delete",
                        type: "select",
                        value: "",
                        title: i18n("tt.projectDelete"),
                        options: [
                            {
                                value: "",
                                text: "",
                            },
                            {
                                value: "yes",
                                text: i18n("yes"),
                            },
                        ]
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        window.modules["tt.settings"].deleteProject(result.projectId);
                    } else {
                        window.modules["tt.settings"].doModifyProject(result.projectId, result.acronym, result.project);
                    }
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    addProject: function () {
        cardForm({
            title: i18n("tt.addProject"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "acronym",
                    type: "text",
                    title: i18n("tt.projectAcronym"),
                    placeholder: i18n("tt.projectAcronym"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "project",
                    type: "text",
                    title: i18n("tt.projectProject"),
                    placeholder: i18n("tt.projectProject"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doAddProject(result.acronym, result.project);
            },
        }).show();
    },

    setWorkflowAlias: function (workflow) {
        let w = '';

        for (let i in window.modules["tt"].meta.workflowAliases) {
            if (window.modules["tt"].meta.workflowAliases[i].workflow === workflow) {
                w = window.modules["tt"].meta.workflowAliases[i].alias;
                break;
            }
        }

        cardForm({
            title: i18n("tt.workflowAlias"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "workflow",
                    type: "text",
                    title: i18n("tt.workflow"),
                    value: workflow,
                    readonly: true,
                },
                {
                    id: "alias",
                    type: "text",
                    title: i18n("tt.workflowAlias"),
                    placeholder: i18n("tt.workflowAlias"),
                    value: w,
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doSetWorkflowAlias(workflow, result.alias);
            },
        }).show();
    },

    projectWorkflows: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(project => {
            let w = {};
            for (let i in window.modules["tt"].meta.workflows) {
                w[window.modules["tt"].meta.workflows[i]] = window.modules["tt"].meta.workflows[i];
            }
            for (let i in window.modules["tt"].meta.workflowAliases) {
                if (w[window.modules["tt"].meta.workflowAliases[i].workflow]) {
                    w[window.modules["tt"].meta.workflowAliases[i].workflow] = window.modules["tt"].meta.workflowAliases[i].alias;
                }
            }

            let workflows = [];
            for (let i in w) {
                workflows.push({
                    id: i,
                    text: "[" + i + "] " + w[i],
                });
            }

            cardForm({
                title: i18n("tt.projectForkflows"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
                singleColumn: true,
                fields: [
                    {
                        id: "workflows",
                        type: "multiselect",
                        title: i18n("tt.workflows"),
                        options: workflows,
                        value: project.project.workflows,
                    },
                ],
                callback: function (result) {
                    console.log(projectId, result.workflows);
                    window.modules["tt.settings"].doSetProjectWorkflows(projectId, result.workflows);
                },
            }).show();
            loadingDone();
        }).
        fail(FAIL);
    },

    projectResolutions: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(project => {
            console.log(project);
            loadingDone();
        }).
        fail(FAIL);
    },

    projectCustomFields: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(project => {
            console.log(project);
            loadingDone();
        }).
        fail(FAIL);
    },

    projectUsers: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(project => {
            console.log(project);
            loadingDone();
        }).
        fail(FAIL);
    },

    projectGroups: function (projectId) {
        loadingStart();
        GET("tt", "project", projectId, true).
        done(project => {
            console.log(project);
            loadingDone();
        }).
        fail(FAIL);
    },

    renderProjects: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addProject"),
                        click: window.modules["tt.settings"].addProject,
                    },
                    caption: i18n("tt.projects"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.projectId"),
                    },
                    {
                        title: i18n("tt.projectAcronym"),
                    },
                    {
                        title: i18n("tt.projectProject"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < window.modules["tt"].meta.projects.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.projects[i].projectId.toString(),
                            cols: [
                                {
                                    data: window.modules["tt"].meta.projects[i].projectId,
                                    click: window.modules["tt.settings"].modifyProject,
                                },
                                {
                                    data: window.modules["tt"].meta.projects[i].acronym,
                                    click: window.modules["tt.settings"].modifyProject,
                                    nowrap: true,
                                },
                                {
                                    data: window.modules["tt"].meta.projects[i].project,
                                },
                            ],
                            dropDown: {
                                icon: "fas fa-tools",
                                items: [
                                    {
                                        icon: "fas fa-shoe-prints",
                                        title: i18n("tt.workflows"),
                                        click: window.modules["tt.settings"].projectWorkflows,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-signature",
                                        title: i18n("tt.resolutions"),
                                        click: window.modules["tt.settings"].projectResolutions,
                                    },
                                    {
                                        icon: "fas fa-edit",
                                        title: i18n("tt.customFields"),
                                        click: window.modules["tt.settings"].projectCustomFields,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-user",
                                        title: i18n("tt.users"),
                                        click: window.modules["tt.settings"].projectUsers,
                                    },
                                    {
                                        icon: "fas fa-users",
                                        title: i18n("tt.groups"),
                                        click: window.modules["tt.settings"].projectGroups,
                                    },
                                ],
                            }
                        });
                    }

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderAllWorkflows: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.workflows"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.workflow"),
                    },
                    {
                        title: i18n("tt.workflowAlias"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    let w = {};
                    for (let i = 0; i < window.modules["tt"].meta.workflowAliases.length; i++) {
                        w[window.modules["tt"].meta.workflowAliases[i].workflow] = window.modules["tt"].meta.workflowAliases[i].alias;
                    }

                    for (let i = 0; i < window.modules["tt"].meta.workflows.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.workflows[i],
                            cols: [
                                {
                                    data: window.modules["tt"].meta.workflows[i],
                                    click: window.modules["tt.settings"].setWorkflowAlias,
                                },
                                {
                                    data: w[window.modules["tt"].meta.workflows[i]]?w[window.modules["tt"].meta.workflows[i]]:window.modules["tt"].meta.workflows[i],
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "workflows",
            "statuses",
            "resolutions",
            "customFields"
        ];
        let section = (params["section"] && sections.indexOf(params["section"]) >= 0)?params["section"]:"projects";

        let top = '';

        top += ``;

        for (let i in sections) {
            if (sections[i] === section) {
                top += `
                    <li class="nav-item d-none d-sm-inline-block">
                        <span class="nav-link text-primary text-bold">${i18n("tt." + sections[i])}</span>
                    </li>
                `;
            } else {
                top += `
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="#tt.settings&section=${sections[i]}" class="nav-link">${i18n("tt." + sections[i])}</a>
                    </li>
                `;
            }
        }

        $("#topMenuLeftDynamic").html(top);

        switch (section) {
            case "projects":
                window.modules["tt.settings"].renderProjects();
                break;

            case "workflows":
                window.modules["tt.settings"].renderAllWorkflows();
                break;
        }
    },
}).init();