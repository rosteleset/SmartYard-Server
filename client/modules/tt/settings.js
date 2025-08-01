({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.settings", this);
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
                    validate: v => {
                        return !!v.match(/^[a-zA-Z_][a-zA-Z0-9_]*$/g);
                    }
                },
                {
                    id: "project",
                    type: "text",
                    title: i18n("tt.projectProject"),
                    placeholder: i18n("tt.projectProject"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "project", false, {
                    acronym: result.acronym.toUpperCase(),
                    project: result.project,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasAdded"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    addStatus: function () {
        cardForm({
            title: i18n("tt.addStatus"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "status",
                    type: "text",
                    title: i18n("tt.status"),
                    placeholder: i18n("tt.status"),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                },
                {
                    id: "final",
                    type: "noyes",
                    title: i18n("tt.finalStatus"),
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "status", false, {
                    status: result.status,
                    final: result.final,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.statusWasAdded"));
                }).
                always(modules.tt.settings.renderStatuses);
            },
        });
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "resolution", false, {
                    resolution: result.resolution,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.resolutionWasAdded"));
                }).
                always(modules.tt.settings.renderResolutions);
            },
        });
    },

    addCustomField: function () {
        let cfc = [];
        let a = {};
        for (let i in modules.tt.meta.customFields) {
            if (modules.tt.meta.customFields[i].catalog && !a[modules.tt.meta.customFields[i].catalog]) {
                a[modules.tt.meta.customFields[i].catalog] = true;
                cfc.push({
                    id: modules.tt.meta.customFields[i].catalog,
                    text: modules.tt.meta.customFields[i].catalog,
                });
            }
        }
        $("#altForm").hide();
        cardForm({
            title: i18n("tt.addCustomField"),
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
                    validate: v => {
                        return !!v.match(/^[a-z_A-Z]\w*$/g);
                    },
                },
                {
                    id: "catalog",
                    type: "select2",
                    title: i18n("tt.customFieldCatalog"),
                    placeholder: i18n("tt.customFieldCatalog"),
                    tags: true,
                    createTags: true,
                    options: cfc,
                },
                {
                    id: "type",
                    type: "select2",
                    title: i18n("tt.customFieldType"),
                    placeholder: i18n("tt.customFieldType"),
                    minimumResultsForSearch: Infinity,
                    options: [
                        {
                            id: "text",
                            text: i18n("tt.customFieldTypeText"),
                        },
                        {
                            id: "select",
                            text: i18n("tt.customFieldTypeSelect"),
                        },
                        {
                            id: "users",
                            text: i18n("tt.customFieldTypeUsers"),
                        },
                        {
                            id: "issues",
                            text: i18n("tt.customFieldTypeIssues"),
                        },
                        {
                            id: "geo",
                            text: i18n("tt.customFieldTypeGeo"),
                        },
                        {
                            id: "array",
                            text: i18n("tt.customFieldTypeArray"),
                        },
                        {
                            id: "virtual",
                            text: i18n("tt.customFieldTypeVirtual"),
                        },
                    ]
                },
                {
                    id: "fieldDisplay",
                    type: "text",
                    title: i18n("tt.customFieldDisplay"),
                    placeholder: i18n("tt.customFieldDisplay"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "fieldDisplayList",
                    type: "text",
                    title: i18n("tt.customFieldDisplayList"),
                    placeholder: i18n("tt.customFieldDisplayList"),
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "customField", false, {
                    catalog: result.catalog,
                    type: result.type,
                    field: result.field,
                    fieldDisplay: result.fieldDisplay,
                    fieldDisplayList: result.fieldDisplayList,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.customFieldWasAdded"));
                }).
                always(modules.tt.settings.renderCustomFields);
            },
        });
    },

    addProjectUser: function (projectId, users, roles) {
        let u = [];
        for (let i in users) {
            u.push({
                id: i,
                text: users[i],
            })
        }
        let r = [];
        for (let i in roles) {
            r.push({
                id: i,
                text: roles[i],
            })
        }
        cardForm({
            title: i18n("tt.addProjectUser"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "uid",
                    type: "select2",
                    title: i18n("tt.projectUser"),
                    options: u,
                },
                {
                    id: "roleId",
                    type: "select2",
                    title: i18n("tt.projectRole"),
                    options: r,
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "role", false, {
                    projectId: projectId,
                    uid: result.uid,
                    roleId: result.roleId,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(() => {
                    modules.tt.settings.projectUsers(projectId);
                });
            },
        });
    },

    addProjectGroup: function (projectId, groups, roles) {
        let g = [];
        for (let i in groups) {
            g.push({
                id: i,
                text: groups[i],
            })
        }

        let r = [];
        for (let i in roles) {
            r.push({
                id: i,
                text: roles[i],
            })
        }
        cardForm({
            title: i18n("tt.addProjectGroup"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "gid",
                    type: "select2",
                    title: i18n("tt.projectGroup"),
                    options: g,
                },
                {
                    id: "roleId",
                    type: "select2",
                    title: i18n("tt.projectRole"),
                    options: r,
                },
            ],
            callback: function (result) {
                loadingStart();
                POST("tt", "role", false, {
                    projectId: projectId,
                    gid: result.gid,
                    roleId: result.roleId,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(() => {
                    modules.tt.settings.projectGroups(projectId);
                });
            },
        });
    },

    addPrint: function () {
        cardForm({
            title: i18n("tt.addPrint"),
            apply: i18n("add"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "formName",
                    type: "text",
                    title: i18n("tt.printFormName"),
                    placeholder: i18n("tt.printFormName"),
                    validate: v => {
                        return !!v.match(/^[a-z_A-Z]\w*$/g);
                    }
                },
                {
                    id: "extension",
                    type: "select2",
                    title: i18n("tt.printExtension"),
                    placeholder: i18n("tt.printExtension"),
                    options: [
                        {
                            id: "docx",
                            text: "docx",
                        },
                        {
                            id: "xlsx",
                            text: "xlsx",
                        },
                        {
                            id: "pdf",
                            text: "pdf",
                        },
                    ],
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "description",
                    type: "text",
                    title: i18n("tt.printDescription"),
                    placeholder: i18n("tt.printDescription"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: r => {
                loadingStart();
                POST("tt", "prints", false, r).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.printWasAdded"));
                }).
                always(modules.tt.settings.renderPrints);
            },
        });
    },

    modifyProject: function (projectId) {
        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        cardForm({
            title: i18n("tt.projectEdit"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
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
                    readonly: true,
                },
                {
                    id: "project",
                    type: "text",
                    value: project.project,
                    title: i18n("tt.projectProject"),
                    placeholder: i18n("tt.projectProject"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "maxFileSize",
                    type: "select2",
                    value: project.maxFileSize,
                    title: i18n("tt.maxFileSize"),
                    options: [
                        {
                            id: 1024 * 1024,
                            text: "1Mb"
                        },
                        {
                            id: 2 * 1024 * 1024,
                            text: "2Mb"
                        },
                        {
                            id: 4 * 1024 * 1024,
                            text: "4Mb"
                        },
                        {
                            id: 8 * 1024 * 1024,
                            text: "8Mb"
                        },
                        {
                            id: 16 * 1024 * 1024,
                            text: "16Mb"
                        },
                        {
                            id: 32 * 1024 * 1024,
                            text: "32Mb"
                        },
                        {
                            id: 64 * 1024 * 1024,
                            text: "64Mb"
                        },
                    ],
                    validate: v => {
                        return parseInt(v) >= 0 && parseInt(v) <= 64 * 1024 * 1024;
                    }
                },
                {
                    id: "searchSubject",
                    type: "yesno",
                    value: project.searchSubject,
                    title: i18n("tt.searchSubject"),
                    placeholder: i18n("tt.searchSubject"),
                },
                {
                    id: "searchDescription",
                    type: "yesno",
                    value: project.searchDescription,
                    title: i18n("tt.searchDescription"),
                    placeholder: i18n("tt.searchDescription"),
                },
                {
                    id: "searchComments",
                    type: "yesno",
                    value: project.searchComments,
                    title: i18n("tt.searchComments"),
                    placeholder: i18n("tt.searchComments"),
                },
                {
                    id: "assigned",
                    type: "select",
                    value: project.assigned,
                    title: i18n("tt.assigned"),
                    options: [
                        {
                            id: "0",
                            text: i18n("tt.assignedUsersAndGroups"),
                        },
                        {
                            id: "1",
                            text: i18n("tt.assignedOnlyUsers"),
                        },
                        {
                            id: "2",
                            text: i18n("tt.assignedOnlyGroups"),
                        },
                        {
                            id: "3",
                            text: i18n("tt.assignedUsersAndGroupsMultiple"),
                        },
                        {
                            id: "4",
                            text: i18n("tt.assignedOnlyUsersMultiple"),
                        },
                        {
                            id: "5",
                            text: i18n("tt.assignedOnlyGroupsMultiple"),
                        },
                    ],
                },
            ],
            delete: i18n("tt.projectDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteProject(result.projectId);
                } else {
                    loadingStart();
                    PUT("tt", "project", project["projectId"], result).
                    fail(FAIL).
                    done(() => {
                        message(i18n("tt.projectWasChanged"));
                    }).
                    always(modules.tt.settings.renderProjects);
                }
            },
        });
    },

    modifyStatus: function (statusId) {
        let status = '';
        let final = 0;

        for (let i in modules.tt.meta.statuses) {
            if (modules.tt.meta.statuses[i].statusId == statusId) {
                status = modules.tt.meta.statuses[i].status;
                final = parseInt(modules.tt.meta.statuses[i].finalStatus);
            }
        }

        cardForm({
            title: i18n("tt.status"),
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "final",
                    type: "noyes",
                    title: i18n("tt.finalStatus"),
                    value: final,
                },
            ],
            delete: i18n("tt.statusDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteStatus(statusId);
                } else {
                    loadingStart();
                    PUT("tt", "status", statusId, {
                        status: result.status,
                        final: result.final,
                    }).
                    fail(FAIL).
                    done(() => {
                        message(i18n("tt.statusWasChanged"));
                    }).
                    always(modules.tt.settings.renderStatuses);
                }
            },
        });
    },

    modifyResolution: function (resolutionId) {
        let resolution = '';

        for (let i in modules.tt.meta.resolutions) {
            if (modules.tt.meta.resolutions[i].resolutionId == resolutionId) {
                resolution = modules.tt.meta.resolutions[i].resolution;
            }
        }

        cardForm({
            title: i18n("tt.resolution"),
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
                    title: i18n("tt.resolutionName"),
                    value: resolution,
                },
            ],
            delete: i18n("tt.resolutionDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteResolution(resolutionId);
                } else {
                    loadingStart();
                    PUT("tt", "resolution", resolutionId, {
                        resolution: result.resolution,
                    }).
                    fail(FAIL).
                    done(() => {
                        message(i18n("tt.resolutionWasChanged"));
                    }).
                    always(modules.tt.settings.renderResolutions);
                }
            },
        });
    },

    modifyRole: function (roleId) {
        let name = '';
        let display = '';

        for (let i in modules.tt.meta.roles) {
            if (modules.tt.meta.roles[i].roleId == roleId) {
                name = modules.tt.meta.roles[i].name;
                display = modules.tt.meta.roles[i].nameDisplay;
            }
        }

        cardForm({
            title: i18n("tt.role"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "roleId",
                    type: "text",
                    title: i18n("tt.roleId"),
                    value: roleId,
                    readonly: true,
                },
                {
                    id: "name",
                    type: "text",
                    title: i18n("tt.roleName"),
                    value: name,
                    readonly: true,
                },
                {
                    id: "display",
                    type: "text",
                    title: i18n("tt.roleDisplay"),
                    placeholder: i18n("tt.roleDisplay"),
                    value: display,
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                loadingStart();
                PUT("tt", "role", roleId, {
                    display: result.display,
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    message(i18n("tt.roleWasChanged"));
                }).
                done(modules.tt.settings.renderRoles);
            },
        });
    },

    modifyCustomField: function (customFieldId) {
        loadingStart();
        GET("tt", "tt", false, true).
        fail(FAIL).
        done(modules.tt.tt).
        done(() => {
            $(window).scrollTop(0);

            let cfc = [];
            let a = {};
            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].catalog && !a[modules.tt.meta.customFields[i].catalog]) {
                    a[modules.tt.meta.customFields[i].catalog] = true;
                    cfc.push({
                        id: modules.tt.meta.customFields[i].catalog,
                        text: modules.tt.meta.customFields[i].catalog,
                    });
                }
            }

            let cf = {};
            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].customFieldId == customFieldId) {
                    cf = modules.tt.meta.customFields[i];
                }
            }

            let options = "";

            for (let i in cf.options) {
                options += cf.options[i].optionDisplay + "\n";
            }

            cardForm({
                title: i18n("tt.customField") + " " + i18n("tt.customFieldId") + customFieldId,
                footer: true,
                borderless: true,
                topApply: true,
                target: "#altForm",
                fields: [
                    {
                        id: "catalog",
                        type: "select2",
                        title: i18n("tt.customFieldCatalog"),
                        placeholder: i18n("tt.customFieldCatalog"),
                        tags: true,
                        createTags: true,
                        options: cfc,
                        value: cf.catalog,
                    },
                    {
                        id: "field",
                        type: "text",
                        title: i18n("tt.customFieldField"),
                        readonly: true,
                        value: cf.field,
                    },
                    {
                        id: "type",
                        type: "text",
                        title: i18n("tt.customFieldType"),
                        readonly: true,
                        value: i18n("tt.customFieldType" + cf.type.charAt(0).toUpperCase() + cf.type.slice(1)),
                    },
                    {
                        id: "fieldDisplay",
                        type: "text",
                        title: i18n("tt.customFieldDisplay"),
                        placeholder: i18n("tt.customFieldDisplay"),
                        value: cf.fieldDisplay,
                        validate: (v, prefix) => {
                            return $(`#${prefix}delete`).val() === "yes" || $.trim(v) !== "";
                        }
                    },
                    {
                        id: "fieldDisplayList",
                        type: "text",
                        title: i18n("tt.customFieldDisplayList"),
                        placeholder: i18n("tt.customFieldDisplayList"),
                        value: cf.fieldDisplayList,
                    },
                    {
                        id: "fieldDescription",
                        type: "text",
                        title: i18n("tt.customFieldDescription"),
                        placeholder: i18n("tt.customFieldDescription"),
                        value: cf.fieldDescription,
                        hidden: cf.type === "virtual",
                    },
                    {
                        id: "regex",
                        type: "text",
                        title: i18n("tt.customFieldRegex"),
                        placeholder: i18n("tt.customFieldRegex"),
                        value: cf.regex,
                        hint: i18n("forExample") + " ^[A-Z0-9]+$",
                        hidden: cf.type !== "text",
                    },
                    {
                        id: "format",
                        type: "text",
                        title: i18n("tt.customFieldDisplayFormat"),
                        placeholder: i18n("tt.customFieldDisplayFormat"),
                        value: cf.format,
                        hint: i18n("forExample") + " %.02d",
                        hidden: cf.type !== "text",
                    },
                    {
                        id: "editor",
                        type: "select2",
                        title: i18n("tt.customFieldEditor"),
                        placeholder: i18n("tt.customFieldEditor"),
                        value: cf.editor,
                        options: [
                            {
                                id: "text",
                                text: i18n("tt.customFieldEditorString"),
                            },
                            {
                                id: "number",
                                text: i18n("tt.customFieldEditorNumber"),
                            },
                            {
                                id: "area",
                                text: i18n("tt.customFieldEditorText"),
                            },
                            {
                                id: "email",
                                text: i18n("tt.customFieldEditorEmail"),
                            },
                            {
                                id: "tel",
                                text: i18n("tt.customFieldEditorTel"),
                            },
                            {
                                id: "date",
                                text: i18n("tt.customFieldEditorDate"),
                            },
                            {
                                id: "time",
                                text: i18n("tt.customFieldEditorTime"),
                            },
                            {
                                id: "datetime-local",
                                text: i18n("tt.customFieldEditorDateTime"),
                            },
                            {
                                id: "yesno",
                                text: i18n("tt.customFieldEditorYesNo"),
                            },
                            {
                                id: "noyes",
                                text: i18n("tt.customFieldEditorNoYes"),
                            },
                            {
                                id: "json",
                                text: i18n("tt.customFieldEditorJSON"),
                            },
                        ],
                        hidden: cf.type !== "text",
                        select: (el, id, prefix) => {
                            if ($(el).val() == "number") {
                                $("#" + prefix + "float").parent().parent().show();
                            } else {
                                $("#" + prefix + "float").parent().parent().hide();
                            }
                        },
                    },
                    {
                        id: "float",
                        type: "number",
                        title: i18n("tt.float"),
                        placeholder: "0",
                        value: cf.float,
                        hidden: cf.editor != "number",
                    },
                    {
                        id: "link",
                        type: "text",
                        title: i18n("tt.customFieldLink"),
                        placeholder: i18n("tt.customFieldLink"),
                        value: cf.link,
                        hint: i18n("forExample") + " https://example.com/?search=%value%",
                        hidden: cf.type === "virtual" || cf.type === "issues" || cf.type === "geo" || cf.type === "array",
                    },
                    {
                        id: "options",
                        type: "area",
                        title: i18n("tt.customFieldOptions"),
                        placeholder: i18n("tt.customFieldOptions"),
                        value: $.trim(options),
                        validate: (v, prefix) => {
                            return $(`#${prefix}delete`).val() === "yes" || parseInt($(`#${prefix}suggestions`).val()) || parseInt($(`#${prefix}editable`).val()) || $.trim(v) !== "";
                        },
                        hidden: cf.type !== "select",
                    },
                    {
                        id: "editable",
                        type: "noyes",
                        title: i18n("tt.editable"),
                        value: (cf.format && cf.format.split(" ").includes("editable"))?"1":"0",
                        hidden: cf.type !== "select",
                    },
                    {
                        id: "multiple",
                        type: "noyes",
                        title: i18n("tt.multiple"),
                        value: (cf.format && cf.format.split(" ").includes("multiple"))?"1":"0",
                        hidden: cf.type === "virtual" || cf.type === "text" || cf.type === "geo" || cf.type === "array",
                    },
                    {
                        id: "suggestions",
                        type: "noyes",
                        title: i18n("tt.suggestions"),
                        value: (cf.format && cf.format.split(" ").includes("suggestions"))?"1":"0",
                        hidden: cf.type !== "select",
                    },
                    {
                        id: "usersAndGroups",
                        type: "select",
                        title: i18n("tt.usersAndGroups"),
                        options: [
                            {
                                id: "users",
                                text: i18n("tt.users"),
                                selected: cf.format && cf.format.split(" ").includes("users"),
                            },
                            {
                                id: "groups",
                                text: i18n("tt.groups"),
                                selected: cf.format && cf.format.split(" ").includes("groups"),
                            },
                            {
                                id: "usersAndGroups",
                                text: i18n("tt.usersAndGroupsChoice"),
                                selected: cf.format && cf.format.split(" ").includes("usersAndGroups"),
                            },
                        ],
                        hidden: cf.type !== "users",
                    },
                    {
                        id: "indx",
                        type: "noyes",
                        title: i18n("tt.customFieldIndex"),
                        value: cf.indx,
                        hidden: cf.type === "virtual",
                    },
                    {
                        id: "search",
                        type: "noyes",
                        title: i18n("tt.customFieldSearch"),
                        value: cf.search,
                        hidden: cf.type !== "text",
                    },
                    {
                        id: "required",
                        type: "noyes",
                        title: i18n("tt.required"),
                        value: cf.required,
                        hidden: cf.type === "virtual",
                    },
                    {
                        id: "readonly",
                        type: "noyes",
                        title: i18n("tt.readonly"),
                        value: cf.readonly,
                        hidden: cf.type === "virtual",
                    },
                ],
                delete: i18n("tt.customFieldDelete"),
                callback: function (result) {
                    result.options = $.trim(result.options);
                    if (result.delete === "yes") {
                        modules.tt.settings.deleteCustomField(customFieldId);
                    } else {
                        result.format = "";
                        if (result.multiple === "1") {
                            result.format += " multiple";
                        }
                        if (result.suggestions === "1") {
                            result.format += " suggestions";
                        }
                        if (result.editable === "1") {
                            result.format += " editable";
                        }
                        if (cf.type === "users") {
                            result.format += " " + result.usersAndGroups;
                        }
                        result.format = $.trim(result.format);
                        loadingStart();
                        PUT("tt", "customField", customFieldId, result).
                        fail(FAIL).
                        done(() => {
                            message(i18n("tt.customFieldWasChanged"));
                        }).
                        done(() => {
                            $("#altForm").hide();
                        }).
                        always(modules.tt.settings.renderCustomFields);
                    }
                },
                cancel: function () {
                    $("#altForm").hide();
                }
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    modifyPrint: function (printId) {
        let print = {};

        for (let i in modules.tt.meta.prints) {
            if (modules.tt.meta.prints[i].printId == printId) {
                print = modules.tt.meta.prints[i];
            }
        }

        cardForm({
            title: i18n("tt.modifyPrint"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "printId",
                    type: "text",
                    hidden: true,
                    value: print.printId,
                },
                {
                    id: "formName",
                    type: "text",
                    title: i18n("tt.printFormName"),
                    placeholder: i18n("tt.printFormName"),
                    readonly: true,
                    value: print.formName,
                    validate: v => {
                        return !!v.match(/^[a-z_A-Z]\w*$/g);
                    },
                },
                {
                    id: "extension",
                    type: "select2",
                    title: i18n("tt.printExtension"),
                    placeholder: i18n("tt.printExtension"),
                    value: print.extension,
                    options: [
                        {
                            id: "docx",
                            text: "docx",
                        },
                        {
                            id: "xlsx",
                            text: "xlsx",
                        },
                        {
                            id: "pdf",
                            text: "pdf",
                        },
                    ],
                validate: v => {
                        return $.trim(v) !== "";
                    },
                },
                {
                    id: "description",
                    type: "text",
                    title: i18n("tt.printDescription"),
                    placeholder: i18n("tt.printDescription"),
                    value: print.description,
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                },
            ],
            callback: r => {
                loadingStart();
                PUT("tt", "prints", r.printId, r).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.printWasChanged"));
                }).
                always(modules.tt.settings.renderPrints);
            },
        });
    },

    deleteProject: function (projectId) {
        mConfirm(i18n("tt.confirmProjectDelete", projectId.toString()), i18n("confirm"), `danger:${i18n("tt.projectDelete")}`, () => {
            loadingStart();
            DELETE("tt", "project", projectId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.projectWasDeleted"));
            }).
            always(modules.tt.settings.renderProjects);
        });
    },

    deleteCustomField: function (customFieldId) {
        mConfirm(i18n("tt.confirmCustomFieldDelete", customFieldId.toString()), i18n("confirm"), `danger:${i18n("tt.customFieldDelete")}`, () => {
            loadingStart();
            DELETE("tt", "customField", customFieldId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.customFieldWasDeleted"));
            }).
            done(() => {
                $("#altForm").hide();
            }).
            always(modules.tt.settings.renderCustomFields);
        });
    },

    deleteStatus: function (statusId) {
        mConfirm(i18n("tt.confirmStatusDelete", statusId.toString()), i18n("confirm"), `danger:${i18n("tt.statusDelete")}`, () => {
            loadingStart();
            DELETE("tt", "status", statusId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.statusWasDeleted"));
            }).
            always(modules.tt.settings.renderStatuses);
        });
    },

    deleteResolution: function (resolutionId) {
        mConfirm(i18n("tt.confirmResolutionDelete", resolutionId.toString()), i18n("confirm"), `danger:${i18n("tt.resolutionDelete")}`, () => {
            loadingStart();
            DELETE("tt", "resolution", resolutionId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.resolutionWasDeleted"));
            }).
            always(modules.tt.settings.renderResolutions);
        });
    },

    deleteViewer: function (field, name) {
        mConfirm(i18n("tt.confirmViewerDelete", field + " [" + name + "]"), i18n("confirm"), `danger:${i18n("tt.viewerDelete")}`, () => {
            loadingStart();
            DELETE("tt", "viewer", false, {
                field: field,
                name: name,
            }).
            fail(FAIL).
            done(() => {
                message(i18n("tt.viewerWasDeleted"));
            }).
            always(modules.tt.settings.renderViewers);
        });
    },

    projectWorkflows: function (projectId) {
        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let workflows = [];
        for (let i in modules.tt.meta.workflows) {
            workflows.push({
                id: i,
                text: modules.tt.meta.workflows[i].name?((modules.tt.meta.workflows[i].name.charAt(0) == "#")?modules.tt.meta.workflows[i].name.substring(1):modules.tt.meta.workflows[i].name):i,
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
                loadingStart();
                PUT("tt", "project", projectId, {
                    workflows: result.workflows,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    addProjectFilter: function (projectId, personals) {
        let p = [
            {
                id: "0",
                text: i18n("tt.commonFilter"),
                icon: "fas fa-fw fa-globe-americas",
            }
        ];

        for (let i in personals) {
            p.push({
                id: i,
                text: personals[i],
                icon: "fas fa-fw " + ((parseInt(i) > 1000000)?"fa-users":"fa-user"),
            });
        }

        let f = [];

        for (let i in modules.tt.meta.filters) {
            if (i.charAt(0) !== "#" && !modules.tt.meta.filters[i].owner) {
                f.push({
                    id: i,
                    text: modules.tt.meta.filters[i].name ? (modules.tt.meta.filters[i].name + " [" + i + "]") : i,
                });
            }
        }

        cardForm({
            title: i18n("tt.addProjectFilter"),
            footer: true,
            borderless: true,
            topApply: true,
            singleColumn: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "filter",
                    type: "select2",
                    title: i18n("tt.filter"),
                    options: f,
                    validate: v => {
                        return !!$.trim(v);
                    },
                },
                {
                    id: "personal",
                    type: "select2",
                    title: i18n("tt.personal"),
                    options: p,
                },
            ],
            callback: result => {
                loadingStart();
                POST("tt", "project", projectId, {
                    filter: result.filter,
                    personal: result.personal,
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                done(() => {
                    modules.tt.settings.projectFilters(projectId);
                });
            },
        });
    },

    deleteProjectFilter: function (projectFilterId, projectId) {
        mConfirm(i18n("tt.confirmFilterDelete", projectFilterId), i18n("confirm"), `danger:${i18n("delete")}`, () => {
            loadingStart();
            DELETE("tt", "project", false, {
                filter: projectFilterId,
            }).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("tt.projectWasChanged"));
            }).
            done(() => {
                modules.tt.settings.projectFilters(projectId);
            });
        });
    },

    projectFilters: function (projectId) {

        function pFilters(projectId, personals) {
            GET("accounts", "users").
            done(response => {
                let project = false;
                let filters = {};

                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].projectId == projectId) {
                        project = modules.tt.meta.projects[i];
                        break;
                    }
                }

                for (let i in response.users) {
                    if (response.users[i].uid) {
                        personals[parseInt(response.users[i].uid)] = $.trim((response.users[i].realName?response.users[i].realName:response.users[i].login) + " [" + response.users[i].login + "]");
                    }
                }

                for (let i in modules.tt.meta.filters) {
                    if (!modules.tt.meta.filters[i].owner) {
                        filters[i] = true;
                    }
                }

                cardTable({
                    target: "#altForm",
                    title: {
                        caption: i18n("tt.projectFilters") + " " + i18n("tt.projectId") + projectId,
                        button: {
                            caption: i18n("tt.addProjectFilter"),
                            click: () => {
                                modules.tt.settings.addProjectFilter(projectId, personals);
                            },
                        },
                        altButton: {
                            caption: i18n("close"),
                            click: () => {
                                $("#altForm").hide();
                            },
                        },
                        filter: true,
                    },
                    columns: [
                        {
                            title: i18n("tt.projectFilterId"),
                        },
                        {
                            title: i18n("tt.projectFilter"),
                            nowrap: true,
                        },
                        {
                            title: i18n("tt.projectFilterFile"),
                            nowrap: true,
                        },
                        {
                            title: i18n("tt.filterPersonal"),
                            nowrap: true,
                            fullWidth: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        for (let i in project.filters) {
                            if (filters[project.filters[i].filter]) {
                                rows.push({
                                    uid: project.filters[i].projectFilterId,
                                    cols: [
                                        {
                                            data: project.filters[i].projectFilterId,
                                        },
                                        {
                                            data: trimStr(project.filters[i].filter ? modules.tt.meta.filters[project.filters[i].filter].name : project.filters[i].filter),
                                            nowrap: true,
                                        },
                                        {
                                            data: trimStr(project.filters[i].filter),
                                            nowrap: true,
                                        },
                                        {
                                            data: project.filters[i].personal?personals[project.filters[i].personal]:i18n("tt.commonFilter"),
                                            nowrap: true,
                                        },
                                    ],
                                    dropDown: {
                                        items: [
                                            {
                                                icon: "fas fa-trash-alt",
                                                title: i18n("tt.deleteFilter"),
                                                class: "text-danger",
                                                click: projectFilterId => {
                                                    modules.tt.settings.deleteProjectFilter(projectFilterId, projectId);
                                                },
                                            },
                                        ],
                                    },
                                });
                            }
                        }

                        return rows;
                    },
                }).show();
                loadingDone();
            }).
            fail(FAIL).
            fail(loadingDone);
        }

        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (modules.groups) {
                modules.groups.loadGroups(() => {
                    let personals = {};

                    for (let i in modules.groups.meta) {
                        if (modules.groups.meta[i].gid) {
                            personals[1000000 + parseInt(modules.groups.meta[i].gid)] = $.trim(modules.groups.meta[i].name + " [" + modules.groups.meta[i].acronym + "]");
                        }
                    }

                    pFilters(projectId, personals);
                });
            } else {
                pFilters(projectId, {});
            }
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    projectResolutions: function (projectId) {
        let project = false;
        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let resolutions = [];
        for (let i in modules.tt.meta.resolutions) {
            resolutions.push({
                id: modules.tt.meta.resolutions[i].resolutionId,
                text: modules.tt.meta.resolutions[i].resolution,
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
                loadingStart();
                PUT("tt", "project", projectId, {
                    resolutions: result.resolutions,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    projectCustomFields: function (projectId) {
        let project = false;
        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let customFields = [];
        for (let i in modules.tt.meta.customFields) {
            customFields.push({
                id: modules.tt.meta.customFields[i].customFieldId,
                text: $.trim(modules.tt.meta.customFields[i].fieldDisplay + " [" + modules.tt.meta.customFields[i].field + "]"),
            });
        }

        customFields.sort((a, b) => {
            if (a.text > b.text) {
                return 1;
            }
            if (a.text < b.text) {
                return -1;
            }
            return 0;
        });

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
                loadingStart();
                PUT("tt", "project", projectId, {
                    customFields: result.customFields,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    projectCustomFieldsNoJournal: function (projectId) {
        let project = false;
        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let customFields = [];
        for (let i in modules.tt.meta.customFields) {
            customFields.push({
                id: modules.tt.meta.customFields[i].customFieldId,
                text: $.trim(modules.tt.meta.customFields[i].fieldDisplay + " [" + modules.tt.meta.customFields[i].field + "]"),
            });
        }

        customFields.sort((a, b) => {
            if (a.text > b.text) {
                return 1;
            }
            if (a.text < b.text) {
                return -1;
            }
            return 0;
        });

        cardForm({
            title: i18n("tt.projectCustomFieldsNoJournal"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
            fields: [
                {
                    id: "customFieldsNoJournal",
                    type: "multiselect",
                    title: i18n("tt.customFieldsNoJournal"),
                    options: customFields,
                    value: project.customFieldsNoJournal,
                },
            ],
            callback: function (result) {
                loadingStart();
                PUT("tt", "project", projectId, {
                    customFieldsNoJournal: result.customFieldsNoJournal,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    projectDeleteUser: function (projectRoleId, projectId) {
        mConfirm(i18n("users.confirmDelete", projectRoleId.toString()), i18n("confirm"), `warning:${i18n("tt.removeUserFromProject")}`, () => {
            loadingStart();
            DELETE("tt", "role", projectRoleId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("tt.projectWasChanged"));
            }).
            done(() => {
                modules.tt.settings.projectUsers(projectId);
            });
        });
    },

    projectDeleteGroup: function (projectRoleId, projectId) {
        mConfirm(i18n("groups.confirmDelete", projectRoleId.toString()), i18n("confirm"), `warning:${i18n("tt.removeGroupFromProject")}`, () => {
            loadingStart();
            DELETE("tt", "role", projectRoleId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("tt.projectWasChanged"));
            }).
            done(() => {
                modules.tt.settings.projectGroups(projectId);
            });
        });
    },

    projectUsers: function (projectId) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("accounts", "users").
            done(response => {
                let project = false;
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].projectId == projectId) {
                        project = modules.tt.meta.projects[i];
                        break;
                    }
                }

                let users = {};
                for (let i in response.users) {
                    if (response.users[i].uid) {
                        users[response.users[i].uid] = $.trim((response.users[i].realName?response.users[i].realName:response.users[i].login) + " [" + response.users[i].login + "]");
                    }
                }

                let roles = {};
                for (let i in modules.tt.meta.roles) {
                    roles[modules.tt.meta.roles[i].roleId] = $.trim((modules.tt.meta.roles[i].nameDisplay?modules.tt.meta.roles[i].nameDisplay:i18n("tt." + modules.tt.meta.roles[i].name)) + " [" + modules.tt.meta.roles[i].level + "]");
                }

                cardTable({
                    target: "#altForm",
                    title: {
                        caption: i18n("tt.projectUsers") + " " + i18n("tt.projectId") + projectId,
                        button: {
                            caption: i18n("tt.addProjectUser"),
                            click: () => {
                                modules.tt.settings.addProjectUser(projectId, users, roles);
                            },
                        },
                        altButton: {
                            caption: i18n("close"),
                            click: () => {
                                $("#altForm").hide();
                            },
                        },
                    },
                    columns: [
                        {
                            title: i18n("tt.projectRoleId"),
                        },
                        {
                            title: i18n("tt.projectUser"),
                            nowrap: true,
                            fullWidth: true,
                        },
                        {
                            title: i18n("tt.projectRole"),
                            nowrap: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        for (let i in project.users) {
                            if (!project.users[i].byGroup) {
                                rows.push({
                                    uid: project.users[i].projectRoleId,
                                    cols: [
                                        {
                                            data: project.users[i].projectRoleId,
                                        },
                                        {
                                            data: users[project.users[i].uid],
                                        },
                                        {
                                            data: roles[project.users[i].roleId],
                                            nowrap: true,
                                        },
                                    ],
                                    dropDown: {
                                        items: [
                                            {
                                                icon: "fas fa-trash-alt",
                                                title: i18n("users.delete"),
                                                class: "text-danger",
                                                click: projectRoleId => {
                                                    modules.tt.settings.projectDeleteUser(projectRoleId, projectId);
                                                },
                                            },
                                        ],
                                    },
                                });
                            }
                        }

                        return rows;
                    },
                }).show();
            }).
            fail(FAIL).
            always(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    projectGroups: function (projectId) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            modules.groups.loadGroups(() => {
                let project = false;
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].projectId == projectId) {
                        project = modules.tt.meta.projects[i];
                        break;
                    }
                }

                let groups = {};
                for (let i in modules.groups.meta) {
                    if (modules.groups.meta[i].gid) {
                        groups[modules.groups.meta[i].gid] = $.trim(modules.groups.meta[i].name + " [" + modules.groups.meta[i].acronym + "]");
                    }
                }

                let roles = {};
                for (let i in modules.tt.meta.roles) {
                    if (modules.tt.meta.roles[i].level > 0) {
                        roles[modules.tt.meta.roles[i].roleId] = $.trim((modules.tt.meta.roles[i].nameDisplay?modules.tt.meta.roles[i].nameDisplay:i18n("tt." + modules.tt.meta.roles[i].name)) + " [" + modules.tt.meta.roles[i].level + "]");
                    }
                }

                cardTable({
                    target: "#altForm",
                    title: {
                        caption: i18n("tt.projectGroups") + " " + i18n("tt.projectId") + projectId,
                        button: {
                            caption: i18n("tt.addProjectGroup"),
                            click: () => {
                                modules.tt.settings.addProjectGroup(projectId, groups, roles);
                            },
                        },
                        altButton: {
                            caption: i18n("close"),
                            click: () => {
                                $("#altForm").hide();
                            },
                        },
                    },
                    columns: [
                        {
                            title: i18n("tt.projectRoleId"),
                        },
                        {
                            title: i18n("tt.projectGroup"),
                            nowrap: true,
                            fullWidth: true,
                        },
                        {
                            title: i18n("tt.projectRole"),
                            nowrap: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        for (let i in project.groups) {
                            rows.push({
                                uid: project.groups[i].projectRoleId,
                                cols: [
                                    {
                                        data: project.groups[i].projectRoleId,
                                    },
                                    {
                                        data: groups[project.groups[i].gid],
                                    },
                                    {
                                        data: roles[project.groups[i].roleId],
                                        nowrap: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-trash-alt",
                                            title: i18n("groups.delete"),
                                            class: "text-danger",
                                            click: projectRoleId => {
                                                modules.tt.settings.projectDeleteGroup(projectRoleId, projectId);
                                            },
                                        },
                                    ],
                                },
                            });
                        }

                        return rows;
                    },
                }).show();
                loadingDone();
            });
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    projectTags: function (projectId) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#altForm",
                title: {
                    caption: i18n("tt.tags") + " " + i18n("tt.projectId") + projectId,
                    button: {
                        caption: i18n("tt.addProjectTag"),
                        click: () => {
                            cardForm({
                                title: i18n("tt.addTag"),
                                footer: true,
                                borderless: true,
                                topApply: true,
                                apply: i18n("add"),
                                fields: [
                                    {
                                        id: "tag",
                                        type: "text",
                                        title: i18n("tt.tag"),
                                        placeholder: i18n("tt.tag"),
                                    },
                                    {
                                        id: "foreground",
                                        type: "color",
                                        title: i18n("tt.foreground"),
                                        placeholder: i18n("tt.foreground"),
                                        value: "#666666",
                                    },
                                    {
                                        id: "background",
                                        type: "color",
                                        title: i18n("tt.background"),
                                        placeholder: i18n("tt.background"),
                                        value: "#ffffff",
                                    },
                                ],
                                callback: f => {
                                    loadingStart();
                                    POST("tt", "tag", false, {
                                        projectId: projectId,
                                        tag: f.tag,
                                        foreground: f.foreground,
                                        background: f.background,
                                    }).
                                    fail(FAIL).
                                    done(() => {
                                        message(i18n("tt.projectWasChanged"));
                                    }).
                                    always(() => {
                                        modules.tt.settings.projectTags(projectId);
                                    });
                                },
                            });
                        },
                    },
                    altButton: {
                        caption: i18n("close"),
                        click: () => {
                            $("#altForm").hide();
                        },
                    },
                },
                edit: tagId => {
                    let tag = "";
                    let foreground = "#666666";
                    let background = "#ffffff";
                    for (let i in modules.tt.meta.tags) {
                        if (modules.tt.meta.tags[i].projectId == projectId && modules.tt.meta.tags[i].tagId == tagId) {
                            tag = modules.tt.meta.tags[i].tag;
                            foreground = modules.tt.meta.tags[i].foreground?modules.tt.meta.tags[i].foreground:foreground;
                            background = modules.tt.meta.tags[i].background?modules.tt.meta.tags[i].background:background;
                        }
                    }
                    cardForm({
                        title: i18n("tt.modifyTag"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        apply: i18n("apply"),
                        delete: i18n("tt.deleteTag"),
                        fields: [
                            {
                                id: "tagId",
                                type: "text",
                                title: i18n("tt.tagId"),
                                readonly: true,
                                value: tagId,
                            },
                            {
                                id: "tag",
                                type: "text",
                                title: i18n("tt.tag"),
                                placeholder: i18n("tt.tag"),
                                value: tag,
                            },
                            {
                                id: "foreground",
                                type: "color",
                                title: i18n("tt.foreground"),
                                placeholder: i18n("tt.foreground"),
                                value: foreground,
                            },
                            {
                                id: "background",
                                type: "color",
                                title: i18n("tt.background"),
                                placeholder: i18n("tt.background"),
                                value: background,
                            },
                        ],
                        callback: f => {
                            if (f.delete === "yes") {
                                mConfirm(i18n("tt.confirmDeleteTag", tagId), i18n("confirm"), `danger:${i18n("tt.deleteTag")}`, () => {
                                    loadingStart();
                                    DELETE("tt", "tag", tagId).
                                    fail(FAIL).
                                    fail(loadingDone).
                                    done(() => {
                                        message(i18n("tt.projectWasChanged"));
                                    }).
                                    done(() => {
                                        modules.tt.settings.projectTags(projectId);
                                    });
                                });
                            } else {
                                modules.tt.settings.doModifyTag(tagId, f.tag, f.foreground, f.background, projectId);
                                loadingStart();
                                PUT("tt", "tag", tagId, {
                                    tag: f.tag,
                                    foreground: f.foreground,
                                    background: f.background,
                                }).
                                fail(FAIL).
                                fail(loadingDone).
                                done(() => {
                                    message(i18n("tt.projectWasChanged"));
                                }).
                                done(() => {
                                    modules.tt.settings.projectTags(projectId);
                                });
                            }
                        },
                    });
                },
                columns: [
                    {
                        title: i18n("tt.tagId"),
                    },
                    {
                        title: i18n("tt.tag"),
                        nowrap: true,
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.tags) {
                        if (modules.tt.meta.tags[i].projectId == projectId) {
                            rows.push({
                                uid: modules.tt.meta.tags[i].tagId,
                                cols: [
                                    {
                                        data: modules.tt.meta.tags[i].tagId,
                                    },
                                    {
                                        data: `<span class="mr-1 text-bold" style='border: solid thin #cbccce; padding-left: 7px; padding-right: 7px; padding-top: 2px; padding-bottom: 2px; color: ${modules.tt.meta.tags[i].foreground}; border-radius: 4px; background: ${modules.tt.meta.tags[i].background};'><i class="fas fa-tag mr-2"></i>${modules.tt.meta.tags[i].tag}</span>`,
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            }).show();
            loadingDone();
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    projectViewers: function (projectId) {
        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let cf = {};

        for (let i in modules.tt.meta.customFields) {
            cf["_cf_" + modules.tt.meta.customFields[i].field] = modules.tt.meta.customFields[i].fieldDisplay;
        }

        let vi = {};
        let va = [];

        let viewers = [];
        for (let i in modules.tt.meta.viewers) {
            let key = md5(guid());
            vi[key] = {
                field: modules.tt.meta.viewers[i].field,
                name: modules.tt.meta.viewers[i].name,
            }
            if (modules.tt.meta.viewers[i].field.substring(0, 4) == "_cf_") {
                viewers.push({
                    id: key,
                    text: $.trim(cf[modules.tt.meta.viewers[i].field] + " [" + modules.tt.meta.viewers[i].name + "]"),
                });
            } else {
                viewers.push({
                    id: key,
                    text: $.trim(i18n("tt." + modules.tt.meta.viewers[i].field) + " [" + modules.tt.meta.viewers[i].name + "]"),
                });
            }
            for (let j in project.viewers) {
                if (project.viewers[j].field == modules.tt.meta.viewers[i].field && project.viewers[j].name == modules.tt.meta.viewers[i].name) {
                    va.push(key);
                }
            }
        }

        viewers.sort((a, b) => {
            if (a.text > b.text) {
                return 1;
            }
            if (a.text < b.text) {
                return -1;
            }
            return 0;
        });

        cardForm({
            title: i18n("tt.projectViewers"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
            noFocus: true,
            fields: [
                {
                    id: "viewers",
                    type: "multiselect",
                    title: i18n("tt.projectViewers"),
                    options: viewers,
                    value: va,
                },
            ],
            callback: function (result) {
                loadingStart();
                let vo = [];
                for (let i in result.viewers) {
                    vo.push(vi[result.viewers[i]]);
                }
                PUT("tt", "project", projectId, {
                    viewers: vo,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    projectCommentTypes: function (projectId) {
        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        cardForm({
            title: i18n("tt.commentTypes"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
            fields: [
                {
                    id: "comments",
                    type: "area",
                    value: project.comments,
                },
            ],
            callback: r => {
                loadingStart();
                PUT("tt", "project", projectId, {
                    comments: r.comments,
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.projectWasChanged"));
                }).
                always(modules.tt.settings.renderProjects);
            },
        });
    },

    renderProjects: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addProject"),
                        click: modules.tt.settings.addProject,
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
                edit: modules.tt.settings.modifyProject,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.projects.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.projects[i].projectId.toString(),
                            cols: [
                                {
                                    data: modules.tt.meta.projects[i].projectId,
                                },
                                {
                                    data: modules.tt.meta.projects[i].acronym,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.projects[i].project,
                                },
                            ],
                            dropDown: {
                                icon: "fas fa-tools",
                                items: [
                                    {
                                        icon: "fas fa-shoe-prints",
                                        title: i18n("tt.workflows"),
                                        click: modules.tt.settings.projectWorkflows,
                                    },
                                    {
                                        icon: "fas fa-filter",
                                        title: i18n("tt.filters"),
                                        click: modules.tt.settings.projectFilters,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-signature",
                                        title: i18n("tt.resolutions"),
                                        click: modules.tt.settings.projectResolutions,
                                    },
                                    {
                                        icon: "fas fa-edit",
                                        title: i18n("tt.customFields"),
                                        click: modules.tt.settings.projectCustomFields,
                                        disabled: modules.tt.meta.customFields.length <= 0,
                                    },
                                    {
                                        icon: "fas fa-eye-slash",
                                        title: i18n("tt.customFieldsNoJournal"),
                                        click: modules.tt.settings.projectCustomFieldsNoJournal,
                                        disabled: modules.tt.meta.customFields.length <= 0,
                                    },
                                    {
                                        icon: "fas fa-eye",
                                        title: i18n("tt.projectViewers"),
                                        click: modules.tt.settings.projectViewers,
                                        disabled: modules.tt.meta.viewers.length <= 0,
                                    },
                                    {
                                        icon: "fas fa-tags",
                                        title: i18n("tt.tags"),
                                        click: modules.tt.settings.projectTags,
                                    },
                                    {
                                        icon: "fas fa-comment-alt",
                                        title: i18n("tt.commentTypes"),
                                        click: modules.tt.settings.projectCommentTypes,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-user",
                                        title: i18n("tt.users"),
                                        click: modules.tt.settings.projectUsers,
                                    },
                                    {
                                        icon: "fas fa-users",
                                        title: i18n("tt.groups"),
                                        click: modules.tt.settings.projectGroups,
                                        disabled: !AVAIL("accounts", "groups"),
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

    renderWorkflow: function (workflow) {
        loadingStart();
        GET("tt", "workflow", workflow, true).
        done(w => {
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="workflowEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="workflowSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.workflowSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("workflowEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
            editor.session.setMode("ace/mode/lua");
            editor.setValue(w.body, -1);
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.focus();
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#workflowSave").click();
                }),
            });
            $("#workflowSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "workflow", workflow, { "body": textRTrim($.trim(editor.getValue())) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.workflowWasSaved"));
                    currentAceEditorOriginalValue = currentAceEditor.getValue();
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    deleteWorkflow: function (workflow) {
        mConfirm(i18n("tt.confirmWorkflowDelete", workflow), i18n("confirm"), i18n("delete"), () => {
            loadingStart();
            DELETE("tt", "workflow", workflow, false).
            fail(err => {
                FAIL(err);
                loadingDone();
            }).
            done(() => {
                modules.tt.settings.renderWorkflows();
            });
        });
    },

    addWorkflow: function () {
        cardForm({
            title: i18n("tt.addWorkflow"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "file",
                    type: "text",
                    title: i18n("tt.workflow"),
                    placeholder: i18n("tt.workflow"),
                    validate: v => {
                        return !!v.match(/^[a-z_A-Z]\w*$/g);
                    }
                },
            ],
            callback: f => {
                window.location.href = "?#tt.settings&section=workflow&workflow=" + encodeURIComponent(f.file);
            },
        });
    },

    renderWorkflows: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.workflows") + " <a href='?#tt.settings&section=libs' class='ml-3 hoverable pointer'>" + i18n("tt.libs") + "</a>",
                    button: {
                        caption: i18n("tt.addWorkflow"),
                        click: modules.tt.settings.addWorkflow,
                    },
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.workflow"),
                    },
                    {
                        title: i18n("tt.workflowName"),
                        fullWidth: true,
                    },
                ],
                edit: workflow => {
                    window.location.href = "?#tt.settings&section=workflow&workflow=" + encodeURIComponent(workflow);
                },
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.workflows) {
                        let wn = modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i;

                        if (wn.charAt(0) == "#") {
                            wn = wn.substring(1);
                        }
                        rows.push({
                            uid: i,
                            cols: [
                                {
                                    data: i,
                                },
                                {
                                    data: wn,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "far fa-trash-alt",
                                        title: i18n("tt.deleteWorkflow"),
                                        class: "text-danger",
                                        click: workflow => {
                                            modules.tt.settings.deleteWorkflow(workflow);
                                        },
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderWorkflowLib: function (lib) {
        loadingStart();
        GET("tt", "lib", lib, true).
        done(l => {
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="libEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="libSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.workflowLibSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("libEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
            editor.session.setMode("ace/mode/lua");
            editor.setValue(l.body, -1);
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.focus();
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#libSave").click();
                }),
            });
            $("#libSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "lib", lib, { "body": textRTrim($.trim(editor.getValue())) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.workflowLibWasSaved"));
                    currentAceEditorOriginalValue = currentAceEditor.getValue();
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    deleteWorkflowLib: function (lib) {
        mConfirm(i18n("tt.confirmWorkflowLibDelete", lib), i18n("confirm"), i18n("delete"), () => {
            loadingStart();
            DELETE("tt", "lib", lib, false).
            fail(err => {
                FAIL(err);
                loadingDone();
            }).
            done(() => {
                modules.tt.settings.renderWorkflowLibs();
            });
        });
    },

    addWorkflowLib: function () {
        cardForm({
            title: i18n("tt.addWorkflowLib"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "file",
                    type: "text",
                    title: i18n("tt.workflowLib"),
                    placeholder: i18n("tt.workflowLib"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: f => {
                window.location.href = "?#tt.settings&section=lib&lib=" + encodeURIComponent(f.file);
            },
        });
    },

    renderWorkflowLibs: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.workflowLibs") + " <a href='?#tt.settings&section=workflows' class='ml-3 hoverable pointer'>" + i18n("tt.workflows") + "</a>",
                    button: {
                        caption: i18n("tt.addWorkflowLib"),
                        click: modules.tt.settings.addWorkflowLib,
                    },
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.workflowLib"),
                        fullWidth: true,
                    },
                ],
                edit: lib => {
                    window.location.href = "?#tt.settings&section=lib&lib=" + encodeURIComponent(lib);
                },
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.workflowLibs) {
                        let wl = modules.tt.meta.workflowLibs[i];

                        rows.push({
                            uid: wl,
                            cols: [
                                {
                                    data: wl,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "far fa-trash-alt",
                                        title: i18n("tt.deleteWorkflowLib"),
                                        class: "text-danger",
                                        click: lib => {
                                            modules.tt.settings.deleteWorkflowLib(lib);
                                        },
                                    },
                                ],
                            },
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
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addStatus"),
                        click: modules.tt.settings.addStatus,
                    },
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
                        fullWidth: true,
                    },
                    {
                        title: i18n("tt.finalStatus"),
                    },
                ],
                edit: modules.tt.settings.modifyStatus,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.statuses.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.statuses[i].statusId,
                            cols: [
                                {
                                    data: modules.tt.meta.statuses[i].statusId,
                                },
                                {
                                    data: modules.tt.meta.statuses[i].status,
                                },
                                {
                                    data: parseInt(modules.tt.meta.statuses[i].final) ? i18n("yes") : i18n("no"),
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
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addResolution"),
                        click: modules.tt.settings.addResolution,
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
                edit: modules.tt.settings.modifyResolution,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.resolutions.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.resolutions[i].resolutionId,
                            cols: [
                                {
                                    data: modules.tt.meta.resolutions[i].resolutionId,
                                },
                                {
                                    data: modules.tt.meta.resolutions[i].resolution,
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

    renderRoles: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.roles"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.roleId"),
                    },
                    {
                        title: i18n("tt.roleName"),
                        nowrap: true,
                    },
                    {
                        title: i18n("tt.roleLevel"),
                        nowrap: true,
                    },
                    {
                        title: i18n("tt.roleNameDisplay"),
                        fullWidth: true,
                    },
                ],
                edit: modules.tt.settings.modifyRole,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.roles.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.roles[i].roleId,
                            cols: [
                                {
                                    data: modules.tt.meta.roles[i].roleId,
                                },
                                {
                                    data: modules.tt.meta.roles[i].name,
                                },
                                {
                                    data: modules.tt.meta.roles[i].level,
                                },
                                {
                                    data: modules.tt.meta.roles[i].nameDisplay?modules.tt.meta.roles[i].nameDisplay:"",
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
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addCustomField"),
                        click: modules.tt.settings.addCustomField,
                    },
                    caption: i18n("tt.customFields"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.customFieldId"),
                    },
                    {
                        title: i18n("tt.customFieldCatalog"),
                    },
                    {
                        title: i18n("tt.customFieldField"),
                    },
                    {
                        title: i18n("tt.customFieldType"),
                    },
                    {
                        title: i18n("tt.customFieldEditor"),
                    },
                    {
                        title: i18n("tt.customFieldDisplay"),
                        fullWidth: true,
                    },
                ],
                edit: modules.tt.settings.modifyCustomField,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.customFields.length; i++) {
                        let editor = '';

                        if (modules.tt.meta.customFields[i].type == "text") {
                            switch (modules.tt.meta.customFields[i].editor) {
                                case "text":
                                    editor = i18n("tt.customFieldEditorString");
                                    break;
                                case "number":
                                    editor = i18n("tt.customFieldEditorNumber");
                                    break;
                                case "area":
                                    editor = i18n("tt.customFieldEditorText");
                                    break;
                                case "email":
                                    editor = i18n("tt.customFieldEditorEmail");
                                    break;
                                case "tel":
                                    editor = i18n("tt.customFieldEditorTel");
                                    break;
                                case "date":
                                    editor = i18n("tt.customFieldEditorDate");
                                    break;
                                case "time":
                                    editor = i18n("tt.customFieldEditorTime");
                                    break;
                                case "datetime-local":
                                    editor = i18n("tt.customFieldEditorDateTime");
                                    break;
                                case "yesno":
                                    editor = i18n("tt.customFieldEditorYesNo");
                                    break;
                                case "noyes":
                                    editor = i18n("tt.customFieldEditorNoYes");
                                    break;
                                case "json":
                                    editor = i18n("tt.customFieldEditorJSON");
                                    break;
                            }
                        }

                        try {
                            if (modules.tt.meta.customFields[i].type == "select" || modules.tt.meta.customFields[i].type == "users") {
                                editor = (modules.tt.meta.customFields[i].format.indexOf("multiple") >= 0) ? i18n("tt.multiple") : i18n("tt.single");
                            }
                        } catch (_) {
                            // do nothing
                        }

                        if (modules.tt.meta.customFields[i].type == "array") {
                            editor = i18n("tt.customFieldEditorArray");
                        }

                        if (modules.tt.meta.customFields[i].type === "virtual") {
                            editor = i18n("tt.customFieldEditorVirtual");
                        }

                        rows.push({
                            uid: modules.tt.meta.customFields[i].customFieldId,
                            cols: [
                                {
                                    data: modules.tt.meta.customFields[i].customFieldId,
                                },
                                {
                                    data: modules.tt.meta.customFields[i].catalog ? modules.tt.meta.customFields[i].catalog : "-",
                                },
                                {
                                    data: modules.tt.meta.customFields[i].field + (parseInt(modules.tt.meta.customFields[i].readonly) ? '&nbsp;<sup class="text-danger">R</sup>' : ''),
                                },
                                {
                                    data: i18n("tt.customFieldType" + modules.tt.meta.customFields[i].type.charAt(0).toUpperCase() + modules.tt.meta.customFields[i].type.slice(1)),
                                    nowrap: true,
                                },
                                {
                                    data: editor,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.customFields[i].fieldDisplay,
                                },
                            ],
                        });
                    }

                    rows.sort((a, b) => {
                        if (a.cols[1].data > b.cols[1].data) {
                            return 1;
                        }
                        if (a.cols[1].data < b.cols[1].data) {
                            return -1;
                        }
                        if (a.cols[5].data > b.cols[5].data) {
                            return 1;
                        }
                        if (a.cols[5].data < b.cols[5].data) {
                            return -1;
                        }
                        return 0;
                    });

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderFilter: function (filter) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("tt", "filter", filter, true).
            done(f => {
                let readOnly = false;
                try {
                    readOnly = modules.tt.meta.filters[filter].owner ? true : false;
                } catch (_) {
                    //
                }
                let height = $(window).height() - mainFormTop;
                let h = '';
                h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
                h += `<pre class="ace-editor mt-2" id="filterEditor"></pre>`;
                h += "</div>";
                if (!readOnly) {
                    h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="filterSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.filterSave")}</span></span>`;
                }
                $("#mainForm").html(h);
                let editor = ace.edit("filterEditor");
                if (modules.darkmode && modules.darkmode.isDark())
                    editor.setTheme("ace/theme/one_dark");
                else
                    editor.setTheme("ace/theme/chrome");
                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: true,
                });
                editor.session.setMode("ace/mode/json");

                let template = {
                    "name": "My",
                    "filter": {
                        "$or": [
                            {
                                "assigned": {
                                    "$elemMatch": {
                                        "$in": "%%my"
                                    }
                                }
                            },
                            {
                                "author": "%%me"
                            }
                        ]
                    },
                    "fields": [
                        "subject",
                        "status",
                        "workflow"
                    ]
                };
                template.name = filter;

                editor.setValue((trim(f.body) == "{}") ? JSON.stringify(template, null, 4) : f.body, -1);
                currentAceEditor = editor;
                currentAceEditorOriginalValue = currentAceEditor.getValue();
                editor.getSession().getUndoManager().reset();
                editor.clearSelection();
                editor.focus();
                editor.setFontSize(14);
                editor.setReadOnly(readOnly);
                editor.commands.removeCommand("removeline");
                editor.commands.removeCommand("redo");
                editor.commands.addCommand({
                    name: "removeline",
                    description: "Remove line",
                    bindKey: {
                        win: "Ctrl-Y",
                        mac: "Cmd-Y"
                    },
                    exec: function (editor) { editor.removeLines(); },
                    scrollIntoView: "cursor",
                    multiSelectAction: "forEachLine"
                });
                editor.commands.addCommand({
                    name: "redo",
                    description: "Redo",
                    bindKey: {
                        win: "Ctrl-Shift-Z",
                        mac: "Command-Shift-Z"
                    },
                    exec: function (editor) { editor.redo(); }
                });
                editor.commands.addCommand({
                    name: 'save',
                    bindKey: {
                        win: "Ctrl-S",
                        mac: "Cmd-S"
                    },
                    exec: (() => {
                        $("#filterSave").click();
                    }),
                });
                $("#filterSave").off("click").on("click", () => {
                    let f = false;
                    let err;
                    try {
                        f = JSON.parse($.trim(editor.getValue()));
                    } catch (e) {
                        f = false;
                        err = e.message;
                    }
                    if (f && $.trim(f.name) && f.fields) {
                        f.fileName = filter;
                        loadingStart();
                        PUT("tt", "filter", filter, { "body": JSON.stringify(f, true, 4) }).
                        done(() => {
                            message(i18n("tt.filterWasSaved"));
                            window.onbeforeunload = null;
//                            window.location.href = '?#tt.settings&section=filter&filter=' + encodeURIComponent(filter) + '&_=' + Math.random();
                            modules.tt.settings.renderFilter(filter);
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    } else {
                        if (err) {
                            error(err, i18n("errors.invalidFilter"), 30);
                        } else {
                            error(i18n("errors.invalidFilter"), i18n("error"), 30);
                        }
                    }
                });
            }).
            fail(FAIL).
            always(() => {
                loadingDone();
            });
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    deleteFilter: function (filter) {
        mConfirm(i18n("tt.filterDelete", filter), i18n("confirm"), i18n("delete"), () => {
            loadingStart();
            DELETE("tt", "filter", filter, false).
            fail(err => {
                FAIL(err);
                loadingDone();
            }).
            done(() => {
                modules.tt.settings.renderFilters();
            });
        });
    },

    addFilter: function () {
        cardForm({
            title: i18n("tt.addFilter"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "file",
                    type: "text",
                    title: i18n("tt.filter"),
                    placeholder: i18n("tt.filter"),
                    validate: v => {
                        return !!v.match(/^[a-z_A-Z]\w*$/g);
                    }
                },
            ],
            callback: f => {
                window.location.href = "?#tt.settings&section=filter&filter=" + encodeURIComponent(f.file);
            },
        });
    },

    renderFilters: function () {
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("accounts", "users").
            done(response => {
                let users = {};

                for (let i in response.users) {
                    if (response.users[i].uid) {
                        users[response.users[i].login] = $.trim((response.users[i].realName?response.users[i].realName:response.users[i].login) + " [" + response.users[i].login + "]");
                    }
                }

                cardTable({
                    target: "#mainForm",
                    title: {
                        button: {
                            caption: i18n("tt.addFilter"),
                            click: modules.tt.settings.addFilter,
                        },
                        caption: i18n("tt.filters"),
                        filter: true,
                    },
                    columns: [
                        {
                            title: i18n("tt.filterFileName"),
                        },
                        {
                            title: i18n("tt.filterOwner"),
                        },
                        {
                            title: i18n("tt.filterName"),
                            fullWidth: true,
                        },
                    ],
                    edit: filter => {
                        window.location.href = "?#tt.settings&section=filter&filter=" + encodeURIComponent(filter);
                    },
                    rows: () => {
                        let rows = [];

                        for (let i in modules.tt.meta.filters) {
                            rows.push({
                                uid: i,
                                cols: [
                                    {
                                        data: trimStr(i),
                                        nowrap: true,
                                    },
                                    {
                                        data: trimStr(modules.tt.meta.filters[i].owner ? (users[modules.tt.meta.filters[i].owner] ? users[modules.tt.meta.filters[i].owner] : modules.tt.meta.filters[i].owner):i18n("tt.commonFilter")),
                                        nowrap: true,
                                    },
                                    {
                                        data: trimStr(modules.tt.meta.filters[i].name ? modules.tt.meta.filters[i].name : i, 128),
                                        nowrap: true,
                                    },
                                ],
                                class: modules.tt.meta.filters[i].pipeline ? 'text-info' : '',
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-trash-alt",
                                            title: i18n("tt.deleteFilter"),
                                            class: "text-danger",
                                            click: modules.tt.settings.deleteFilter,
                                        },
                                    ],
                                },
                            });
                        }

                        return rows;
                    },
                }).show();
                loadingDone();
            }).
            fail(FAIL).
            fail(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    addCrontab: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("accounts", "users").
            done(response => {
                let users = response.users;

                let crontabs = [
                    {
                        id: "-",
                        text: "-",
                    },
                    {
                        id: "daily",
                        text: i18n("tt.crontabDaily"),
                    },
                    {
                        id: "minutely",
                        text: i18n("tt.crontabMinutely"),
                    },
                    {
                        id: "5min",
                        text: i18n("tt.crontab5min"),
                    },
                    {
                        id: "hourly",
                        text: i18n("tt.crontabHourly"),
                    },
                    {
                        id: "monthly",
                        text: i18n("tt.crontabMonthly"),
                    },
                ];

                let projectsOptions = [
                    {
                        id: "-",
                        text: "-",
                    },
                ];

                let projects = {};
                let project = false;

                for (let i in modules.tt.meta.projects) {
                    projects[modules.tt.meta.projects[i].projectId] = modules.tt.meta.projects[i];
                    if (!project) {
                        project = modules.tt.meta.projects[i].projectId;
                    }
                    projectsOptions.push({
                        id: modules.tt.meta.projects[i].projectId,
                        text: $.trim(modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]"),
                    });
                }

                function filtersByProject(projectId) {
                    let f = [
                        {
                            id: "-",
                            text: "-",
                        },
                    ];

                    for (let k in modules.tt.meta.filters) {
                        f.push({
                            id: k,
                            text: modules.tt.meta.filters[k].name,
                        });
                    }

                    return f;
                }

                function uidsByProject(projectId) {
                    let u = [
                        {
                            id: "-",
                            text: "-",
                        },
                    ];

                    for (let k in users) {
                        u.push({
                            id: users[k].uid,
                            text: $.trim((users[k].realName?users[k].realName:users[k].login) + " [" + users[k].login + "]"),
                        });
                    }

                    return u;
                }

                cardForm({
                    title: i18n("tt.addCrontab"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    fields: [
                        {
                            id: "crontab",
                            type: "select2",
                            title: i18n("tt.crontab"),
                            placeholder: i18n("tt.crontab"),
                            options: crontabs,
                            validate: v => {
                                return $.trim(v) !== "" && $.trim(v) !== "-";
                            },
                        },
                        {
                            id: "projectId",
                            type: "select2",
                            title: i18n("tt.project"),
                            placeholder: i18n("tt.project"),
                            options: projectsOptions,
                            validate: v => {
                                return parseInt(v) && $.trim(v) !== "-";
                            },
                            select: (el, id, prefix) => {
                                $(`#${prefix}filter`).html("").select2({
                                    data: filtersByProject(el.val()),
                                    language: lang["_code"],
                                });
                                $(`#${prefix}uid`).html("").select2({
                                    data: uidsByProject(el.val()),
                                    language: lang["_code"],
                                });
                            },
                        },
                        {
                            id: "filter",
                            type: "select2",
                            title: i18n("tt.filter"),
                            placeholder: i18n("tt.filter"),
                            options: filtersByProject(project),
                            validate: v => {
                                return $.trim(v) !== "" && $.trim(v) !== "-";
                            },
                        },
                        {
                            id: "uid",
                            type: "select2",
                            title: i18n("tt.crontabUser"),
                            placeholder: i18n("tt.crontabUser"),
                            options: uidsByProject(project),
                            validate: v => {
                                return $.trim(v) !== "" && $.trim(v) !== "-";
                            },
                        },
                        {
                            id: "action",
                            type: "text",
                            title: i18n("tt.action"),
                            placeholder: i18n("tt.action"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                        },
                    ],
                    callback: f => {
                        loadingStart();
                        POST("tt", "crontab", false, f).
                        fail(FAIL).
                        done(() => {
                            message(i18n("tt.crontabWasAdded"));
                        }).
                        always(modules.tt.settings.renderCrontabs);
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    deleteCrontab: function (crontabId) {
        mConfirm(i18n("tt.confirmCrontabDelete", crontabId), i18n("confirm"), `warning:${i18n("tt.crontabDelete")}`, () => {
            loadingStart();
            DELETE("tt", "crontab", crontabId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.crontabWasDeleted"));
            }).
            always(modules.tt.settings.renderCrontabs);
        });
    },

    renderCrontabs: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("accounts", "users").
            done(response => {
                let users = {};

                for (let i in response.users) {
                    users[response.users[i].uid] = $.trim((response.users[i].realName?response.users[i].realName:response.users[i].login) + " [" + response.users[i].login + "]");
                }

                let projects = {};

                for (let i in modules.tt.meta.projects) {
                    projects[modules.tt.meta.projects[i].projectId] = modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]";
                }

                let filters = {};
                for (let i in modules.tt.meta.filters) {
                    filters[i] = modules.tt.meta.filters[i].name + " [" + i + "]";
                }

                cardTable({
                    target: "#mainForm",
                    title: {
                        button: {
                            caption: i18n("tt.addCrontab"),
                            click: modules.tt.settings.addCrontab,
                        },
                        caption: i18n("tt.crontabs"),
                        filter: true,
                    },
                    columns: [
                        {
                            title: i18n("tt.crontabId"),
                        },
                        {
                            title: i18n("tt.crontab"),
                        },
                        {
                            title: i18n("tt.project"),
                        },
                        {
                            title: i18n("tt.filter"),
                        },
                        {
                            title: i18n("tt.crontabUser"),
                            noWrap: true,
                        },
                        {
                            title: i18n("tt.action"),
                            fullWidth: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        for (let i in modules.tt.meta.crontabs) {
                            rows.push({
                                uid: modules.tt.meta.crontabs[i].crontabId,
                                cols: [
                                    {
                                        data: modules.tt.meta.crontabs[i].crontabId,
                                        nowrap: true,
                                    },
                                    {
                                        data: i18n("tt.crontab" + modules.tt.meta.crontabs[i].crontab.charAt(0).toUpperCase() + modules.tt.meta.crontabs[i].crontab.slice(1)),
                                        nowrap: true,
                                    },
                                    {
                                        data: projects[modules.tt.meta.crontabs[i].projectId],
                                        nowrap: true,
                                    },
                                    {
                                        data: filters[modules.tt.meta.crontabs[i].filter],
                                        nowrap: true,
                                    },
                                    {
                                        data: users[modules.tt.meta.crontabs[i].uid],
                                        nowrap: true,
                                    },
                                    {
                                        data: modules.tt.meta.crontabs[i].action,
                                        nowrap: true,
                                        fullWidth: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-trash-alt",
                                            title: i18n("tt.deleteCrontab"),
                                            class: "text-danger",
                                            click: modules.tt.settings.deleteCrontab,
                                        },
                                    ],
                                },
                            });
                        }

                        return rows;
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    addViewer: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            let fields = [
                {
                    id: "assigned",
                    text: i18n("tt.assigned") + ' [assigned]',
                },
                {
                    id: "catalog",
                    text: i18n("tt.catalog") + ' [catalog]',
                },
                {
                    id: "subject",
                    text: i18n("tt.subject") + ' [subject]',
                },
                {
                    id: "description",
                    text: i18n("tt.description") + ' [description]',
                },
                {
                    id: "status",
                    text: i18n("tt.status") + ' [status]',
                },
                {
                    id: "resolution",
                    text: i18n("tt.resolution") + ' [resolution]',
                },
            ];

            for (let i in modules.tt.meta.customFields) {
                fields.push({
                    id: "_cf_" + modules.tt.meta.customFields[i].field,
                    text: modules.tt.meta.customFields[i].fieldDisplay + ' [_cf_' + modules.tt.meta.customFields[i].field + ']',
                });
            }

            cardForm({
                title: i18n("tt.addViewer"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "lg",
                fields: [
                    {
                        id: "field",
                        type: "select2",
                        title: i18n("tt.viewerField"),
                        placeholder: i18n("tt.viewerField"),
                        options: fields,
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "name",
                        type: "text",
                        title: i18n("tt.viewerName"),
                        placeholder: i18n("tt.viewerName"),
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                ],
                callback: r => {
                    window.location.href = `?#tt.settings&section=viewer&field=${encodeURIComponent(r.field)}&name=${encodeURIComponent(r.name)}`;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderViewer: function (field, name) {
        loadingStart();
        GET("tt", "viewer", false, true).
        done(v => {
            let code = `//function ${name} (value, issue, field, target, filter) {\n\treturn value;\n//}\n`;
            for (let i in v.viewers) {
                if (v.viewers[i].field == field && v.viewers[i].name == name) {
                    code = v.viewers[i].code ? v.viewers[i].code : `//function ${name} (value, issue, field, target, filter) {\n\treturn value;\n//}\n`;
                    break;
                }
            }
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="viewerEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="viewerSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.viewerSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("viewerEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
            editor.session.setMode("ace/mode/javascript");
            editor.setValue(code, -1);
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.focus();
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#viewerSave").click();
                }),
            });
            $("#viewerSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "viewer", false, { field: field, name: name, code: textRTrim($.trim(editor.getValue())) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.viewerWasSaved"));
                    currentAceEditorOriginalValue = currentAceEditor.getValue();
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    renderViewers: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("tt", "viewer", false, true).
            done(r => {
                let cf = {};

                for (let i in modules.tt.meta.customFields) {
                    cf["_cf_" + modules.tt.meta.customFields[i].field] = modules.tt.meta.customFields[i].fieldDisplay;
                }

                let v = {};

                r.viewers.sort((a, b) => {
                    let f1 = (a.field.substring(0, 4) == "_cf_") ? cf[a.field] : i18n("tt." + a.field);
                    let f2 = (b.field.substring(0, 4) == "_cf_") ? cf[b.field] : i18n("tt." + b.field);
                    if (f1 > f2) {
                        return 1;
                    }
                    if (f1 < f2) {
                        return -1;
                    }
                    return 0;
                });

                cardTable({
                    target: "#mainForm",
                    title: {
                        button: {
                            caption: i18n("tt.addViewer"),
                            click: modules.tt.settings.addViewer,
                        },
                        caption: i18n("tt.viewers"),
                        filter: true,
                    },
                    columns: [
                        {
                            title: i18n("tt.viewer"),
                        },
                        {
                            title: i18n("tt.viewerField"),
                        },
                        {
                            title: i18n("tt.viewerName"),
                            fullWidth: true,
                        },
                    ],
                    edit: k => {
                        window.location.href = `?#tt.settings&section=viewer&field=${encodeURIComponent(v[k].field)}&name=${encodeURIComponent(v[k].name)}`;
                    },
                    rows: () => {
                        let rows = [];

                        for (let i in r.viewers) {
                            let key = md5(guid());
                            v[key] = {
                                field: r.viewers[i].field,
                                name: r.viewers[i].name,
                            }
                            rows.push({
                                uid: key,
                                cols: [
                                    {
                                        data: r.viewers[i].filename,
                                        nowrap: true,
                                    },
                                    {
                                        data: (r.viewers[i].field.substring(0, 4) == "_cf_")?cf[r.viewers[i].field]:i18n("tt." + r.viewers[i].field),
                                        nowrap: true,
                                    },
                                    {
                                        data: r.viewers[i].name,
                                        nowrap: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-trash-alt",
                                            title: i18n("tt.deleteFilter"),
                                            class: "text-danger",
                                            click: k => {
                                                modules.tt.settings.deleteViewer(v[k].field, v[k].name);
                                            },
                                        },
                                    ],
                                },
                            });
                        }

                        return rows;
                    },
                });
            }).
            fail(FAIL).
            always(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    modifyPrintData: function (printId) {
        window.location.href = `?#tt.settings&section=printData&printId=${printId}`;
    },

    modifyPrintFormatter: function (printId) {
        window.location.href = `?#tt.settings&section=printFormatter&printId=${printId}`;
    },

    uploadPrintTemplate: function (printId) {
        loadFile([ ".doc", ".odt", ".docx", ".xlsx", "application/msword", "application/vnd.oasis.opendocument.text", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ], false, f => {
            loadingStart();
            PUT("tt", "prints", printId, {
                "mode": "template",
                "name": f.name,
                "body": f.body,
            }).
            fail(FAIL).
            done(() => {
                message(i18n("tt.printTemplateWasUploaded"));
            }).
            always(modules.tt.settings.renderPrints);
        }, i18n("tt.uploadPrintTemplate"));
    },

    downloadPrintTemplate: function (printId) {
        window.location.href = lStore("_server") + "/tt/prints/" + printId + "?mode=template&_token=" + encodeURIComponent(lStore("_token"));
    },

    deletePrintTemplate: function (printId) {
        mConfirm(i18n("tt.confirmDeletePrintTemplate", printId.toString()), i18n("confirm"), `danger:${i18n("tt.deletePrintTemplate")}`, () => {
            loadingStart();
            DELETE("tt", "prints", printId, {
                "mode": "template",
            }).
            fail(FAIL).
            done(() => {
                message(i18n("tt.printTemplateWasDeleted"));
            }).
            always(modules.tt.settings.renderPrints);
        });
    },

    deletePrint: function (printId) {
        mConfirm(i18n("tt.confirmDeletePrint", printId.toString()), i18n("confirm"), `danger:${i18n("tt.deletePrint")}`, () => {
            loadingStart();
            DELETE("tt", "prints", printId).
            fail(FAIL).
            done(() => {
                message(i18n("tt.printWasDeleted"));
            }).
            always(modules.tt.settings.renderPrints);
        });
    },

    renderPrints: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    button: {
                        caption: i18n("tt.addPrint"),
                        click: modules.tt.settings.addPrint,
                    },
                    caption: i18n("tt.prints"),
                    filter: true,
                },
                columns: [
                    {
                        title: i18n("tt.printId"),
                    },
                    {
                        title: i18n("tt.printFormName"),
                    },
                    {
                        title: i18n("tt.printTemplateName"),
                    },
                    {
                        title: i18n("tt.printTemplateSize"),
                    },
                    {
                        title: i18n("tt.printTemplateDate"),
                    },
                    {
                        title: i18n("tt.printExtension"),
                    },
                    {
                        title: i18n("tt.printDescription"),
                        fullWidth: true,
                    },
                ],
                edit: modules.tt.settings.modifyPrint,
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.prints) {
                        rows.push({
                            uid: modules.tt.meta.prints[i].printId,
                            cols: [
                                {
                                    data: modules.tt.meta.prints[i].printId,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].formName,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].templateName ? modules.tt.meta.prints[i].templateName : "&nbsp;",
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].templateSize ? formatBytes(modules.tt.meta.prints[i].templateSize) : "&nbsp;",
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].templateUploadDate ? ttDate(modules.tt.meta.prints[i].templateUploadDate["$numberLong"] / 1000) : "&nbsp;",
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].extension,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.prints[i].description,
                                    nowrap: true,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-database",
                                        title: i18n("tt.modifyPrintData"),
                                        click: modules.tt.settings.modifyPrintData,
                                    },
                                    {
                                        icon: "fas fa-paragraph",
                                        title: i18n("tt.modifyPrintFormatter"),
                                        click: modules.tt.settings.modifyPrintFormatter,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-upload",
                                        title: i18n("tt.uploadPrintTemplate"),
                                        click: modules.tt.settings.uploadPrintTemplate,
                                    },
                                    {
                                        icon: "fas fa-download",
                                        title: i18n("tt.downloadPrintTemplate"),
                                        disabled: !modules.tt.meta.prints[i].hasTemplate,
                                        click: modules.tt.settings.downloadPrintTemplate,
                                    },
                                    {
                                        icon: "fas fa-ban",
                                        title: i18n("tt.deletePrintTemplate"),
                                        class: "text-danger",
                                        disabled: !modules.tt.meta.prints[i].hasTemplate,
                                        click: modules.tt.settings.deletePrintTemplate,
                                    },
                                    {
                                        title: "-",
                                    },
                                    {
                                        icon: "fas fa-trash-alt",
                                        title: i18n("tt.deletePrint"),
                                        class: "text-danger",
                                        click: modules.tt.settings.deletePrint,
                                    },
                                ],
                            },
                        });
                    }
                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderPrintData: function (printId) {
        loadingStart();
        QUERY("tt", "prints", {
            "_id": printId,
            "mode": "data",
        }, true).
        done(v => {
            let code = (v && v.data)?v.data:`//function data (issue, callback) {\n\tcallback(issue);\n//}\n`;
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="printDataEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="printDataSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.printDataSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("printDataEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
            editor.session.setMode("ace/mode/javascript");
            editor.setValue(code, -1);
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.focus();
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#printDataSave").click();
                }),
            });
            $("#printDataSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "prints", printId, {
                    "mode": "data",
                    "data": textRTrim($.trim(editor.getValue())),
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.printDataWasSaved"));
                    currentAceEditorOriginalValue = currentAceEditor.getValue();
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    renderPrintFormatter: function (printId) {
        loadingStart();
        QUERY("tt", "prints", {
            "_id": printId,
            "mode": "formatter",
        }, true).
        done(v => {
            let code = (v && v.formatter)?v.formatter:"";
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="printFormatterEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="printFormatterSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.printFormatterSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("printFormatterEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
            editor.session.setMode("ace/mode/javascript");
            editor.setValue(code, -1);
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.focus();
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#printFormatterSave").click();
                }),
            });
            $("#printFormatterSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "prints", printId, {
                    "mode": "formatter",
                    "formatter": textRTrim($.trim(editor.getValue())),
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.printFormatterWasSaved"));
                    currentAceEditorOriginalValue = currentAceEditor.getValue();
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    route: function (params) {
        $("#altForm").hide();
        subTop();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "workflows",
            "filters",
            "crontabs",
            "statuses",
            "resolutions",
            "roles",
            "customs",
            "viewers",
            "prints",
        ];

        let section = params["section"]?params["section"]:"projects";

        let top = '';

        for (let i in sections) {
            top += `<li class="nav-item d-none d-sm-inline-block">`;
            if (sections[i] === section) {
                top += `<a href="?#tt.settings&section=${sections[i]}" class="nav-link text-primary nav-item-back-selected">${i18n("tt." + sections[i])}</a>`;
            } else {
                top += `<a href="?#tt.settings&section=${sections[i]}" class="nav-link text-dark nav-item-back-hover">${i18n("tt." + sections[i])}</a>`;
            }
            top += `</li>`;
        }

        $("#leftTopDynamic").html(top);

        switch (section) {
            case "workflows":
                modules.tt.settings.renderWorkflows();
                break;

            case "workflow":
                modules.tt.settings.renderWorkflow(params["workflow"]);
                break;

            case "libs":
                modules.tt.settings.renderWorkflowLibs();
                break;

            case "lib":
                modules.tt.settings.renderWorkflowLib(params["lib"]);
                break;

            case "filters":
                modules.tt.settings.renderFilters();
                break;

            case "filter":
                modules.tt.settings.renderFilter(params["filter"]);
                break;

            case "crontabs":
                modules.tt.settings.renderCrontabs();
                break;

            case "statuses":
                modules.tt.settings.renderStatuses();
                break;

            case "resolutions":
                modules.tt.settings.renderResolutions();
                break;

            case "roles":
                modules.tt.settings.renderRoles();
                break;

            case "customs":
                modules.tt.settings.renderCustomFields();
                break;

            case "viewer":
                modules.tt.settings.renderViewer(params["field"], params["name"]);
                break;

            case "viewers":
                modules.tt.settings.renderViewers();
                break;

            case "prints":
                modules.tt.settings.renderPrints();
                break;

            case "printData":
                modules.tt.settings.renderPrintData(params["printId"]);
                break;

            case "printFormatter":
                modules.tt.settings.renderPrintFormatter(params["printId"]);
                break;

            default:
                modules.tt.settings.renderProjects();
                break;
        }
    },
}).init();