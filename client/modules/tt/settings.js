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

    doAddIssueType: function (type) {
        loadingStart();
        POST("tt", "issueType", false, {
            type: type,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasAdded"));
        }).
        always(window.modules["tt.settings"].renderIssueTypes);
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

    doModifyIssueType: function (typeId, type) {
        loadingStart();
        PUT("tt", "issueType", typeId, {
            type: type,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.issueTypeWasChanged"));
        }).
        always(window.modules["tt.settings"].renderIssueTypes);
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

    doDeleteIssueType: function (typeId) {
        loadingStart();
        DELETE("tt", "issueType", typeId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.issueTypeWasDeleted"));
        }).
        always(window.modules["tt.settings"].renderIssueTypes);
    },

    /*
        UI functions
     */

    deleteProject: function (projectId) {
        mConfirm(i18n("tt.confirmProjectDelete", projectId.toString()), i18n("confirm"), `danger:${i18n("tt.projectDelete")}`, () => {
            window.modules["tt.settings"].doDeleteProject(projectId);
        });
    },

    deleteIssueType: function (typeId) {
        mConfirm(i18n("tt.confirmIssueTypeDelete", typeId.toString()), i18n("confirm"), `danger:${i18n("tt.issueTypeDelete")}`, () => {
            window.modules["tt.settings"].doDeleteIssueType(typeId);
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

    modifyIssueType: function (typeId) {
        loadingStart();
        GET("tt", "issueType", typeId, true).
        done(response => {
            cardForm({
                title: i18n("tt.issueTypeEdit"),
                footer: true,
                borderless: true,
                topApply: true,
                fields: [
                    {
                        id: "typeId",
                        type: "text",
                        readonly: true,
                        title: i18n("tt.issueTypeId"),
                        value: response.issueType.typeId.toString(),
                    },
                    {
                        id: "type",
                        type: "text",
                        value: response.issueType.type,
                        title: i18n("tt.issueType"),
                        placeholder: i18n("tt.issueType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "delete",
                        type: "select",
                        value: "",
                        title: i18n("tt.issueTypeDelete"),
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
                        window.modules["tt.settings"].deleteIssueType(result.typeId);
                    } else {
                        window.modules["tt.settings"].doModifyIssueType(result.typeId, result.type);
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

    addIssueType: function () {
        cardForm({
            title: i18n("tt.addIssueType"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "type",
                    type: "text",
                    title: i18n("tt.issueType"),
                    placeholder: i18n("tt.issueType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["tt.settings"].doAddIssueType(result.type);
            },
        }).show();
    },

    modifyIssueTypeProjects: function (typeId) {
        loadingStart();
        GET("tt", "issueType", typeId, true).done(response => {
            let h = '';
            h += `<div class="card mt-2 mb-0">`;
            h += `<div class="card-header">`;
            h += `<h3 class="card-title">`;
            h += `<button class="btn btn-success mr-2 btn-xs modalFormOk" id="projectsFormApply" title="${i18n("apply")}"><i class="fas fa-fw fa-check-circle"></i></button> `;
            h += i18n("tt.issueTypeProjects") + " " + i18n("tt.issueTypeId") + typeId;
            h += `</h3>`;
            h += `<button type="button" class="btn btn-danger btn-xs float-right" id="groupFormCancel" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>`;
            h += `</div>`;
            h += `<div class="card-body pb-0" style="overflow: auto;">`;
            h += `<div class="form-group">`;

            let projects = window.modules["tt"].meta.projects;

            for (let i in projects) {
                let id = md5(guid());
                h += `
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="issueTypeToProject custom-control-input" id="${id}" data-projectId="${projects[i].projectId}" ${(response.issueType.projects.indexOf(projects[i].projectId) >= 0)?"checked":""}/>
                        <label for="${id}" class="custom-control-label">${projects[i].acronym + " [" + projects[i].project + "]"}</label>
                    </div>
                `;
            }
            h += `</div>`;
            h += `</div>`;
            h += `</div>`;

            $("#altForm").html(h).show();

            $("#projectsFormApply").off("click").on("click", () => {
                loadingStart();
                $("#altForm").hide();
                let projects = [];
                $(".issueTypeToProject").each(function () {
                    if ($(this).prop("checked")) {
                        projects.push($(this).attr("data-projectId"));
                    }
                });
                PUT("tt", "issueType", typeId, {
                    projects: projects,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.issueTypeWasChanged"));
                }).
                always(window.modules["tt.settings"].renderIssueTypes);
            });

            $("#groupFormCancel").off("click").on("click", () => {
                $("#altForm").hide();
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderProjects: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        fail(FAIL).
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

                    console.log(window.modules["tt"].meta);

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
                        });
                    }

                    return rows;
                },
            });
        }).
        always(loadingDone);
    },

    renderIssueTypes: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        fail(FAIL).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addIssueType"),
                        click: window.modules["tt.settings"].addIssueType,
                    },
                    caption: i18n("tt.issueTypes"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.issueTypeId"),
                    },
                    {
                        title: i18n("tt.issueType"),
                        fullWidth: true,
                    },
                    {
                        title: i18n("tt.issueTypeProjects"),
                    },
                ],
                rows: () => {
                    let rows = [];

                    console.log(window.modules["tt"].meta);

                    for (let i = 0; i < window.modules["tt"].meta.issueTypes.length; i++) {
                        rows.push({
                            uid: window.modules["tt"].meta.issueTypes[i].typeId.toString(),
                            cols: [
                                {
                                    data: window.modules["tt"].meta.issueTypes[i].typeId,
                                    click: window.modules["tt.settings"].modifyIssueType,
                                },
                                {
                                    data: window.modules["tt"].meta.issueTypes[i].type,
                                    click: window.modules["tt.settings"].modifyIssueType,
                                    nowrap: true,
                                },
                                {
                                    data: window.modules["tt"].meta.issueTypes[i].projects,
                                    click: window.modules["tt.settings"].modifyIssueTypeProjects,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            });
        }).
        always(loadingDone);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "issueTypes",
//            "statuses",
//            "resolutions",
//            "customFields",
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

            case "issueTypes":
                window.modules["tt.settings"].renderIssueTypes();
                break;
        }
    },
}).init();