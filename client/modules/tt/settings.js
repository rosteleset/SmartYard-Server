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

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "types",
            "statuses",
            "resolutions",
            "customFields",
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
        }
    },
}).init();