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

    doAddResolution: function (resolution) {
        loadingStart();
        POST("tt", "resolution", false, {
            resolution: resolution,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.resolutionWasAdded"));
        }).
        always(window.modules["tt.settings"].renderResolutions);
    },

    doAddCustomField: function (type, field, fieldDisplay) {
        loadingStart();
        POST("tt", "customField", false, {
            type: type,
            field: field,
            fieldDisplay: fieldDisplay
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.customFieldWasAdded"));
        }).
        always(window.modules["tt.settings"].renderCustomFields);
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

    doSetProjectResolutions: function (projectId, resolutions) {
        loadingStart();
        PUT("tt", "project", projectId, {
            resolutions: resolutions,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    doSetProjectCustomFields: function (projectId, customFields) {
        loadingStart();
        PUT("tt", "project", projectId, {
            customFields: customFields,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(window.modules["tt.settings"].renderProjects);
    },

    doModifyStatus: function (statusId, display) {
        loadingStart();
        PUT("tt", "status", statusId, {
            statusDisplay: display,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.statusWasChanged"));
        }).
        always(window.modules["tt.settings"].renderStatuses);
    },

    doModifyResolution: function (resolutionId, resolution) {
        loadingStart();
        PUT("tt", "resolution", resolutionId, {
            resolution: resolution,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.resolutionWasChanged"));
        }).
        always(window.modules["tt.settings"].renderResolutions);
    },

    doDeleteResolution: function (resolutionId) {
        loadingStart();
        DELETE("tt", "resolution", resolutionId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.resolutionWasDeleted"));
        }).
        always(window.modules["tt.settings"].renderResolutions);
    },

    /*
        UI functions
     */

    deleteProject: function (projectId) {
        mConfirm(i18n("tt.confirmProjectDelete", projectId.toString()), i18n("confirm"), `danger:${i18n("tt.projectDelete")}`, () => {
            window.modules["tt.settings"].doDeleteProject(projectId);
        });
    },

    deleteResolution: function (resolutionId) {
        mConfirm(i18n("tt.confirmResolutionDelete", resolutionId.toString()), i18n("confirm"), `danger:${i18n("tt.resolutionDelete")}`, () => {
            window.modules["tt.settings"].doDeleteResolution(resolutionId);
        });
    },

    modifyProject: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

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
                    value: project.projectId.toString(),
                    title: i18n("tt.projectId"),
                },
                {
                    id: "acronym",
                    type: "text",
                    value: project.acronym,
                    title: i18n("tt.projectAcronym"),
                    placeholder: i18n("tt.projectAcronym"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "project",
                    type: "text",
                    value: project.project,
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

    addResolution: function () {
        cardForm({
            title: i18n("tt.addResolution"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "resolution",
                    type: "text",
                    title: i18n("tt.resolution"),
                    placeholder: i18n("tt.resolution"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doAddResolution(result.resolution);
            },
        }).show();
    },

    addCustomField: function () {
        cardForm({
            title: i18n("tt.addResolution"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: "add",
            fields: [
                {
                    id: "field",
                    type: "text",
                    title: i18n("tt.customFieldField"),
                    placeholder: i18n("tt.customFieldField"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "fieldDisplay",
                    type: "text",
                    title: i18n("tt.customFieldDisplay"),
                    placeholder: i18n("tt.customFieldDisplay"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "type",
                    type: "select2",
                    title: i18n("tt.customFieldType"),
                    placeholder: i18n("tt.customFieldType"),
                    minimumResultsForSearch: Infinity,
                    options: [
                        {
                            id: "textString",
                            text: i18n("customFieldTypeTextString"),
                        },
                        {
                            id: "textArea",
                            text: i18n("customFieldTypeTextArea"),
                        },
                        {
                            id: "integer",
                            text: i18n("customFieldTypeInteger"),
                        },
                        {
                            id: "real",
                            text: i18n("customFieldTypeReal"),
                        },
                    ]
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doAddCustomField(result.type, result.field, result.fieldDisplay);
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

    modifyStatus: function (statusId) {
        let display = '';
        let status = '';

        for (let i in window.modules["tt"].meta.statuses) {
            if (window.modules["tt"].meta.statuses[i].statusId == statusId) {
                status = window.modules["tt"].meta.statuses[i].status;
                display = window.modules["tt"].meta.statuses[i].statusDisplay;
            }
        }

        cardForm({
            title: i18n("tt.workflowAlias"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "statusId",
                    type: "text",
                    title: i18n("tt.statusId"),
                    value: statusId,
                    readonly: true,
                },
                {
                    id: "status",
                    type: "text",
                    title: i18n("tt.status"),
                    value: status,
                    readonly: true,
                },
                {
                    id: "display",
                    type: "text",
                    title: i18n("tt.statusDisplay"),
                    placeholder: i18n("tt.statusDisplay"),
                    value: display,
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doModifyStatus(statusId, result.display);
            },
        }).show();
    },

    modifyResolution: function (resolutionId) {
        let resolution = '';

        for (let i in window.modules["tt"].meta.resolutions) {
            if (window.modules["tt"].meta.resolutions[i].resolutionId == resolutionId) {
                resolution = window.modules["tt"].meta.resolutions[i].resolution;
            }
        }

        cardForm({
            title: i18n("tt.workflowAlias"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "resolutionId",
                    type: "text",
                    title: i18n("tt.resolutionId"),
                    value: resolutionId,
                    readonly: true,
                },
                {
                    id: "resolution",
                    type: "text",
                    title: i18n("tt.resolution"),
                    value: resolution,
                },
                {
                    id: "delete",
                    type: "select",
                    value: "",
                    title: i18n("tt.resolutionDelete"),
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
                    window.modules["tt.settings"].deleteResolution(resolutionId);
                } else {
                    window.modules["tt.settings"].doModifyResolution(resolutionId, result.resolution);
                }
            },
        }).show();
    },

    projectWorkflows: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

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
                text: "<span class='text-monospace'>[" + i + "]</span> " + w[i],
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
                    value: project.workflows,
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doSetProjectWorkflows(projectId, result.workflows);
            },
        }).show();
    },

    projectResolutions: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

        let resolutions = [];
        for (let i in window.modules["tt"].meta.resolutions) {
            resolutions.push({
                id: window.modules["tt"].meta.resolutions[i].resolutionId,
                text: window.modules["tt"].meta.resolutions[i].resolution,
            });
        }

        cardForm({
            title: i18n("tt.projectResolutions"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
            fields: [
                {
                    id: "resolutions",
                    type: "multiselect",
                    title: i18n("tt.resolutions"),
                    options: resolutions,
                    value: project.resolutions,
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doSetProjectResolutions(projectId, result.resolutions);
            },
        }).show();
    },

    projectCustomFields: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

        let customFields = [];
        for (let i in window.modules["tt"].meta.customFields) {
            customFields.push({
                id: window.modules["tt"].meta.customFields[i].customFieldId,
                text: window.modules["tt"].meta.customFields[i].field,
            });
        }

        cardForm({
            title: i18n("tt.projectCustomFields"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
            fields: [
                {
                    id: "customFields",
                    type: "multiselect",
                    title: i18n("tt.customFields"),
                    options: customFields,
                    value: project.customFields,
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doSetProjectCustomFields(projectId, result.customFields);
            },
        }).show();
    },

    projectUsers: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

    },

    projectGroups: function (projectId) {
        let project = false;
        for (let i in window.modules["tt"].meta.projects) {
            if (window.modules["tt"].meta.projects[i].projectId == projectId) {
                project = window.modules["tt"].meta.projects[i];
                break;
            }
        }

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

    renderStatuses: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.statuses"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.statusId"),
                    },
                    {
                        title: i18n("tt.status"),
                        nowrap: true,
                    },
                    {
                        title: i18n("tt.statusDisplay"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < window.modules["tt"].meta.statuses.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.statuses[i].statusId,
                            cols: [
                                {
                                    data: window.modules["tt"].meta.statuses[i].statusId,
                                    click: window.modules["tt.settings"].modifyStatus,
                                },
                                {
                                    data: window.modules["tt"].meta.statuses[i].status,
                                    click: window.modules["tt.settings"].modifyStatus,
                                },
                                {
                                    data: window.modules["tt"].meta.statuses[i].statusDisplay,
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

    renderResolutions: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addResolution"),
                        click: window.modules["tt.settings"].addResolution,
                    },
                    caption: i18n("tt.resolutions"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.resolutionId"),
                    },
                    {
                        title: i18n("tt.resolution"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < window.modules["tt"].meta.resolutions.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.resolutions[i].resolutionId,
                            cols: [
                                {
                                    data: window.modules["tt"].meta.resolutions[i].resolutionId,
                                    click: window.modules["tt.settings"].modifyResolution,
                                },
                                {
                                    data: window.modules["tt"].meta.resolutions[i].resolution,
                                    click: window.modules["tt.settings"].modifyResolution,
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

    renderCustomFields: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addCustomField"),
                        click: window.modules["tt.settings"].addCustomField,
                    },
                    caption: i18n("tt.customFields"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.customFieldId"),
                    },
                    {
                        title: i18n("tt.customFieldName"),
                    },
                    {
                        title: i18n("tt.customFieldType"),
                    },
                    {
                        title: i18n("tt.customFieldWorflow"),
                    },
                    {
                        title: i18n("tt.customFieldDisplay"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < window.modules["tt"].meta.customFields.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.customFields[i].customFieldId,
                            cols: [
                                {
                                    data: window.modules["tt"].meta.customFields[i].customFieldId,
                                    click: window.modules["tt.settings"].modifyCustomField,
                                },
                                {
                                    data: window.modules["tt"].meta.customFields[i].field,
                                    click: window.modules["tt.settings"].modifyCustomField,
                                },
                                {
                                    data: window.modules["tt"].meta.customFields[i].type,
                                },
                                {
                                    data: window.modules["tt"].meta.customFields[i].workflow,
                                },
                                {
                                    data: window.modules["tt"].meta.customFields[i].fieldDisplay,
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

            case "statuses":
                window.modules["tt.settings"].renderStatuses();
                break;

            case "resolutions":
                window.modules["tt.settings"].renderResolutions();
                break;

            case "customFields":
                window.modules["tt.settings"].renderCustomFields();
                break;
        }
    },
}).init();