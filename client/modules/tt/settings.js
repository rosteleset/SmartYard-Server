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
        always(modules.tt.settings.renderProjects);
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
        always(modules.tt.settings.renderResolutions);
    },

    doAddCustomField: function (type, field, fieldDisplay) {
        loadingStart();
        POST("tt", "customField", false, {
            type: type,
            field: field,
            fieldDisplay: fieldDisplay,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.customFieldWasAdded"));
        }).
        always(modules.tt.settings.renderCustomFields);
    },

    doAddProjectFilter: function (projectId, filter, personal) {
        loadingStart();
        POST("tt", "project", projectId, {
            filter: filter,
            personal: personal,
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

    doAddProjectUser: function (projectId, uid, roleId) {
        loadingStart();
        POST("tt", "role", false, {
            projectId: projectId,
            uid: uid,
            roleId: roleId,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        done(() => {
            modules.tt.settings.projectUsers(projectId);
        });
    },

    doAddProjectGroup: function (projectId, gid, roleId) {
        loadingStart();
        POST("tt", "role", false, {
            projectId: projectId,
            gid: gid,
            roleId: roleId,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        done(() => {
            modules.tt.settings.projectGroups(projectId);
        });
    },

    doAddTag: function (projectId, tag, foreground, background) {
        loadingStart();
        POST("tt", "tag", false, {
            projectId: projectId,
            tag: tag,
            foreground,
            background,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        done(() => {
            modules.tt.settings.projectTags(projectId);
        });
    },

    doAddCrontab: function (crontab) {
        loadingStart();
        POST("tt", "crontab", false, crontab).
        fail(FAIL).
        done(() => {
            message(i18n("tt.crontabWasAdded"));
        }).
        always(modules.tt.settings.renderCrontabs);
    },

    doDeleteCrontab: function (crontabId) {
        loadingStart();
        DELETE("tt", "crontab", crontabId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.crontabWasDeleted"));
        }).
        always(modules.tt.settings.renderCrontabs);
    },

    doModifyProject: function (project) {
        loadingStart();
        PUT("tt", "project", project["projectId"], project).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(modules.tt.settings.renderProjects);
    },

    doDeleteProject: function (projectId) {
        loadingStart();
        DELETE("tt", "project", projectId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasDeleted"));
        }).
        always(modules.tt.settings.renderProjects);
    },

    doDeleteProjectFilter: function (projectId, projectFilterId) {
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
    },

    doDeleteCustomField: function (customFieldId) {
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
    },

    doDeleteViewer: function (field, name) {
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
        always(modules.tt.settings.renderProjects);
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
        always(modules.tt.settings.renderProjects);
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
        always(modules.tt.settings.renderProjects);
    },

    doSetProjectViewers: function (projectId, viewers) {
        loadingStart();
        PUT("tt", "project", projectId, {
            viewers: viewers,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        always(modules.tt.settings.renderProjects);
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
        always(modules.tt.settings.renderStatuses);
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
        always(modules.tt.settings.renderResolutions);
    },

    doModifyTag: function (tagId, tag, foreground, background, projectId) {
        loadingStart();
        PUT("tt", "tag", tagId, {
            tag: tag,
            foreground: foreground,
            background: background,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        done(() => {
            modules.tt.settings.projectTags(projectId);
        });
    },

    doDeleteResolution: function (resolutionId) {
        loadingStart();
        DELETE("tt", "resolution", resolutionId).
        fail(FAIL).
        done(() => {
            message(i18n("tt.resolutionWasDeleted"));
        }).
        always(modules.tt.settings.renderResolutions);
    },

    doProjectDeleteRole: function (projectRoleId, projectId, user) {
        loadingStart();
        DELETE("tt", "role", projectRoleId).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.projectWasChanged"));
        }).
        done(() => {
            if (user) {
                modules.tt.settings.projectUsers(projectId);
            } else {
                modules.tt.settings.projectGroups(projectId);
            }
        });
    },

    doModifyRole: function (roleId, display) {
        loadingStart();
        PUT("tt", "role", roleId, {
            display: display,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            message(i18n("tt.roleWasChanged"));
        }).
        done(modules.tt.settings.renderRoles);
    },

    doModifyCustomField: function (customFieldId, field) {
        loadingStart();
        PUT("tt", "customField", customFieldId, field).
        fail(FAIL).
        done(() => {
            message(i18n("tt.customFieldWasChanged"));
        }).
        done(() => {
            $("#altForm").hide();
        }).
        always(modules.tt.settings.renderCustomFields);
    },

    doDeleteTag: function (tagId, projectId) {
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
    },

    /*
        UI functions
     */

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
                modules.tt.settings.doAddProject(result.acronym, result.project);
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
                modules.tt.settings.doAddResolution(result.resolution);
            },
        }).show();
    },

    addCustomField: function () {
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
                    ]
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
            ],
            callback: function (result) {
                modules.tt.settings.doAddCustomField(result.type, result.field, result.fieldDisplay);
            },
        }).show();
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
                modules.tt.settings.doAddProjectUser(projectId, result.uid, result.roleId);
            },
        }).show();
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
                modules.tt.settings.doAddProjectGroup(projectId, result.gid, result.roleId);
            },
        }).show();
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
                    validate: (v) => {
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
            ],
            delete: i18n("tt.projectDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteProject(result.projectId);
                } else {
                    modules.tt.settings.doModifyProject(result);
                }
            },
        }).show();
    },

    modifyStatus: function (statusId) {
        let display = '';
        let status = '';

        for (let i in modules.tt.meta.statuses) {
            if (modules.tt.meta.statuses[i].statusId == statusId) {
                status = modules.tt.meta.statuses[i].status;
                display = modules.tt.meta.statuses[i].statusDisplay;
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
                modules.tt.settings.doModifyStatus(statusId, result.display);
            },
        }).show();
    },

    modifyResolution: function (resolutionId) {
        let resolution = '';
        let protcted = false;

        for (let i in modules.tt.meta.resolutions) {
            if (modules.tt.meta.resolutions[i].resolutionId == resolutionId) {
                resolution = modules.tt.meta.resolutions[i].resolution;
                protcted = modules.tt.meta.resolutions[i].protected;
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
                    title: i18n("tt.resolution"),
                    value: resolution,
                },
            ],
            delete: protcted?false:i18n("tt.resolutionDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteResolution(resolutionId);
                } else {
                    modules.tt.settings.doModifyResolution(resolutionId, result.resolution);
                }
            },
        }).show();
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
                modules.tt.settings.doModifyRole(roleId, result.display);
            },
        }).show();
    },

    modifyCustomField: function (customFieldId) {
        loadingStart();
        GET("tt", "tt", false, true).
        fail(FAIL).
        done(modules.tt.tt).
        done(() => {
            let cf = {};
            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].customFieldId == customFieldId) {
                    cf = modules.tt.meta.customFields[i];
                }
            }

            if (cf.workflow) {
                let fields = [
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
                        id: "fieldDescription",
                        type: "text",
                        title: i18n("tt.customFieldDescription"),
                        placeholder: i18n("tt.customFieldDescription"),
                        value: cf.fieldDescription,
                    },
                    {
                        id: "link",
                        type: "text",
                        title: i18n("tt.customFieldLink"),
                        placeholder: i18n("tt.customFieldLink"),
                        value: cf.link,
                        hint: i18n("forExample") + " https://example.com/?search=%value%",
                        hidden: cf.type == "issues" || cf.type == "geo",
                    },
                ];

                if (cf.type === "select") {
                    fields.push({
                        id: "-",
                    });
                    for (let i in cf.options) {
                        let t = cf.options[i].option;
                        fields.push({
                            id: "_cfWorkflowOption_" + cf.options[i].customFieldOptionId,
                            type: "text",
                            title: t,
                            placeholder: t,
                            value: cf.options[i].optionDisplay?cf.options[i].optionDisplay:cf.options[i].option,
                            validate: (v, prefix) => {
                                return $(`#${prefix}delete`).val() === "yes" || $.trim(v) !== "";
                            }
                        });
                    }
                }

                cardForm({
                    title: i18n("tt.customFieldField") + " " + i18n("tt.customFieldId") + customFieldId,
                    footer: true,
                    borderless: true,
                    topApply: true,
                    target: "#altForm",
                    fields: fields,
                    callback: function (result) {
                        let options = {};
                        if (cf.type === "select") {
                            for (let i in result) {
                                if (i.indexOf("_cfWorkflowOption_") === 0) {
                                    options[i.split("_cfWorkflowOption_")[1]] = result[i];
                                }
                            }
                        }
                        modules.tt.settings.doModifyCustomField(customFieldId, result.fieldDisplay, result.fieldDescription, false, false, result.link, options);
                    },
                    cancel: function () {
                        $("#altForm").hide();
                    }
                }).show();
            } else {
                let options = "";

                for (let i in cf.options) {
                    options += cf.options[i].optionDisplay + "\n";
                }

                cardForm({
                    title: i18n("tt.customFieldField") + " " + i18n("tt.customFieldId") + customFieldId,
                    footer: true,
                    borderless: true,
                    topApply: true,
                    target: "#altForm",
                    fields: [
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
                            id: "fieldDescription",
                            type: "text",
                            title: i18n("tt.customFieldDescription"),
                            placeholder: i18n("tt.customFieldDescription"),
                            value: cf.fieldDescription,
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
                            ],
                            hidden: cf.type !== "text",
                        },
                        {
                            id: "link",
                            type: "text",
                            title: i18n("tt.customFieldLink"),
                            placeholder: i18n("tt.customFieldLink"),
                            value: cf.link,
                            hint: i18n("forExample") + " https://example.com/?search=%value%",
                            hidden: cf.type === "issues" || cf.type === "geo",
                        },
                        {
                            id: "options",
                            type: "area",
                            title: i18n("tt.customFieldOptions"),
                            placeholder: i18n("tt.customFieldOptions"),
                            value: options,
                            hidden: cf.type !== "select",
                            validate: (v, prefix) => {
                                return $(`#${prefix}delete`).val() === "yes" || $.trim(v) !== "";
                            }
                        },
                        {
                            id: "multiple",
                            type: "yesno",
                            title: i18n("tt.multiple"),
                            value: (cf.format && cf.format.split(" ").includes("multiple"))?"1":"0",
                            hidden: cf.type === "text" || cf.type === "geo",
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
                            type: "yesno",
                            title: i18n("tt.customFieldIndex"),
                            value: cf.indx,
                        },
                        {
                            id: "search",
                            type: "yesno",
                            title: i18n("tt.customFieldSearch"),
                            value: cf.search,
                            hidden: cf.type !== "text",
                        },
                        {
                            id: "required",
                            type: "yesno",
                            title: i18n("tt.required"),
                            value: cf.required,
                        },
                    ],
                    delete: i18n("tt.customFieldDelete"),
                    callback: function (result) {
                        if (result.delete === "yes") {
                            modules.tt.settings.deleteCustomField(customFieldId);
                        } else {
                            result.format = "";
                            if (result.multiple === "1") {
                                result.format += " multiple";
                            }
                            if (cf.type === "users") {
                                result.format += " " + result.usersAndGroups;
                            }
                            result.format = $.trim(result.format);
                            modules.tt.settings.doModifyCustomField(customFieldId, result);
                        }
                    },
                    cancel: function () {
                        $("#altForm").hide();
                    }
                }).show();
            }
        }).
        always(loadingDone);
    },

    deleteProject: function (projectId) {
        mConfirm(i18n("tt.confirmProjectDelete", projectId.toString()), i18n("confirm"), `danger:${i18n("tt.projectDelete")}`, () => {
            modules.tt.settings.doDeleteProject(projectId);
        });
    },

    deleteCustomField: function (customFieldId) {
        mConfirm(i18n("tt.confirmCustomFieldDelete", customFieldId.toString()), i18n("confirm"), `danger:${i18n("tt.customFieldDelete")}`, () => {
            modules.tt.settings.doDeleteCustomField(customFieldId);
        });
    },

    deleteResolution: function (resolutionId) {
        mConfirm(i18n("tt.confirmResolutionDelete", resolutionId.toString()), i18n("confirm"), `danger:${i18n("tt.resolutionDelete")}`, () => {
            modules.tt.settings.doDeleteResolution(resolutionId);
        });
    },

    deleteViewer: function (field, name) {
        mConfirm(i18n("tt.confirmViewerDelete", field + " [" + name + "]"), i18n("confirm"), `danger:${i18n("tt.viewerDelete")}`, () => {
            modules.tt.settings.doDeleteViewer(field, name);
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
                text: "<span class='text-monospace'>[" + i + "]</span> " + (modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i),
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
                modules.tt.settings.doSetProjectWorkflows(projectId, result.workflows);
            },
        }).show();
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
            if (i.charAt(0) !== "#") {
                f.push({
                    id: i,
                    text: modules.tt.meta.filters[i]?(modules.tt.meta.filters[i] + " [" + i + "]"):i,
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
                modules.tt.settings.doAddProjectFilter(projectId, result.filter, result.personal)
            },
        }).show();
    },

    deleteProjectFilter: function (projectFilterId, projectId) {
        mConfirm(i18n("tt.confirmFilterDelete", projectFilterId), i18n("confirm"), `danger:${i18n("delete")}`, () => {
            modules.tt.settings.doDeleteProjectFilter(projectId, projectFilterId);
        });
    },

    projectFilters: function (projectId) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            GET("accounts", "groups").
            done(response => {
                let personals = {};

                for (let i in response.groups) {
                    if (response.groups[i].gid) {
                        personals[1000000 + parseInt(response.groups[i].gid)] = $.trim(response.groups[i].name + " [" + response.groups[i].acronym + "]");
                    }
                }

                GET("accounts", "users").
                done(response => {
                    let project = false;

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
                        },
                        columns: [
                            {
                                title: i18n("tt.projectFilterId"),
                            },
                            {
                                title: i18n("tt.projectFilter"),
                                nowrap: true,
                                fullWidth: true,
                            },
                            {
                                title: i18n("tt.filterPersonal"),
                                nowrap: true,
                            },
                        ],
                        rows: () => {
                            let rows = [];
    
                            for (let i in project.filters) {
                                rows.push({
                                    uid: project.filters[i].projectFilterId,
                                    cols: [
                                        {
                                            data: project.filters[i].projectFilterId,
                                        },
                                        {
                                            data: project.filters[i].filter?(modules.tt.meta.filters[project.filters[i].filter] + " [" + project.filters[i].filter + "]"):project.filters[i].filter,
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
                modules.tt.settings.doSetProjectResolutions(projectId, result.resolutions);
            },
        }).show();
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
                modules.tt.settings.doSetProjectCustomFields(projectId, result.customFields);
            },
        }).show();
    },

    projectDeleteUser: function (projectRoleId, projectId) {
        mConfirm(i18n("users.confirmDelete", projectRoleId.toString()), i18n("confirm"), `warning:${i18n("tt.removeUserFromProject")}`, () => {
            modules.tt.settings.doProjectDeleteRole(projectRoleId, projectId, true);
        });
    },

    projectDeleteGroup: function (projectRoleId, projectId) {
        mConfirm(i18n("groups.confirmDelete", projectRoleId.toString()), i18n("confirm"), `warning:${i18n("tt.removeGroupFromProject")}`, () => {
            modules.tt.settings.doProjectDeleteRole(projectRoleId, projectId);
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
            GET("accounts", "groups").
            done(response => {
                let project = false;
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].projectId == projectId) {
                        project = modules.tt.meta.projects[i];
                        break;
                    }
                }

                let groups = {};
                for (let i in response.groups) {
                    if (response.groups[i].gid) {
                        groups[response.groups[i].gid] = $.trim(response.groups[i].name + " [" + response.groups[i].acronym + "]");
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
            }).
            fail(FAIL).
            always(loadingDone);
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
                                    modules.tt.settings.doAddTag(projectId, f.tag, f.foreground, f.background);
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
                                    modules.tt.settings.doDeleteTag(tagId, projectId);
                                });
                            } else {
                                modules.tt.settings.doModifyTag(tagId, f.tag, f.foreground, f.background, projectId);
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

        cardForm({
            title: i18n("tt.projectViewers"),
            footer: true,
            borderless: true,
            noHover: true,
            topApply: true,
            singleColumn: true,
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
                let vo = [];
                for (let i in result.viewers) {
                     vo.push(vi[result.viewers[i]]);
                }
                modules.tt.settings.doSetProjectViewers(projectId, vo);
            },
        }).show();
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
                                        disabled: !AVAIL("accounts", "group", "POST"),
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
            // TODO f..ck!
            let top = 75;
            let height = $(window).height() - top;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="workflowEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="workflowSave" class="hoverable"><i class="fas fa-save pr-2"></i>${i18n("tt.worflowSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("workflowEditor");
            editor.setTheme("ace/theme/chrome");
            editor.session.setMode("ace/mode/lua");
            editor.setValue(w.body, -1);
            editor.clearSelection();
            editor.setFontSize(14);
            $("#workflowSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "workflow", workflow, { "body": $.trim(editor.getValue()) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.workflowWasSaved"));
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
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: f => {
                location.href = "?#tt.settings&section=workflow&workflow=" + encodeURIComponent(f.file);
            },
        }).show();
    },

    renderWorkflows: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("tt.workflows"),
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
                    location.href = "?#tt.settings&section=workflow&workflow=" + encodeURIComponent(workflow);
                },
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.workflows) {
                        rows.push({
                            uid: i,
                            cols: [
                                {
                                    data: i,
                                },
                                {
                                    data: modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i,
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

    renderStatuses: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
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
                                    data: modules.tt.meta.statuses[i].statusDisplay,
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
                        title: i18n("tt.customFieldField"),
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
                edit: modules.tt.settings.modifyCustomField,
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < modules.tt.meta.customFields.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.customFields[i].customFieldId,
                            cols: [
                                {
                                    data: modules.tt.meta.customFields[i].customFieldId,
                                },
                                {
                                    data: modules.tt.meta.customFields[i].field,
                                },
                                {
                                    data: i18n("tt.customFieldType" + modules.tt.meta.customFields[i].type.charAt(0).toUpperCase() + modules.tt.meta.customFields[i].type.slice(1)),
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.customFields[i].workflow?i18n("yes"):i18n("no"),
                                },
                                {
                                    data: modules.tt.meta.customFields[i].fieldDisplay,
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

    renderFilter: function (filter) {
        loadingStart();
        GET("tt", "filter", filter, true).
        done(f => {
            // TODO f..ck!
            let top = 75;
            let height = $(window).height() - top;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="filterEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="filterSave" class="hoverable"><i class="fas fa-save pr-2"></i>${i18n("tt.filterSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("filterEditor");
            editor.setTheme("ace/theme/chrome");
            editor.session.setMode("ace/mode/json");
            editor.setValue(f.body, -1);
            editor.clearSelection();
            editor.setFontSize(14);
            $("#filterSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "filter", filter, { "body": $.trim(editor.getValue()) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.filterWasSaved"));
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
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: f => {
                location.href = "?#tt.settings&section=filter&filter=" + encodeURIComponent(f.file);
            },
        }).show();
    },

    renderFilters: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
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
                        title: i18n("tt.filterName"),
                        fullWidth: true,
                    },
                ],
                edit: filter => {
                    location.href = "?#tt.settings&section=filter&filter=" + encodeURIComponent(filter);
                },
                rows: () => {
                    let rows = [];

                    for (let i in modules.tt.meta.filters) {
                        rows.push({
                            uid: i,
                            cols: [
                                {
                                    data: i,
                                    nowrap: true,
                                },
                                {
                                    data: modules.tt.meta.filters[i]?modules.tt.meta.filters[i]:i,
                                },
                            ],
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
            });
        }).
        fail(FAIL).
        always(loadingDone);
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

                let projectsOptions = [];
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
                    let f = [];

                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].projectId == projectId) {
                            for (let j in modules.tt.meta.projects[i].filters) {
                                for (let k in modules.tt.meta.filters) {
                                    if (k == modules.tt.meta.projects[i].filters[j]) {
                                        f.push({
                                            id: k,
                                            text: $.trim((modules.tt.meta.filters[k]?modules.tt.meta.filters[k]:k) + " [" + k + "]"),
                                        });
                                    }
                                }
                            }
                        }
                    }

                    return f;
                }

                function uidsByProject(projectId) {
                    let u = [];

                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].projectId == projectId) {
                            for (let j in modules.tt.meta.projects[i].users) {
                                for (let k in users) {
                                    if (users[k].uid == modules.tt.meta.projects[i].users[j].uid) {
                                        u.push({
                                            id: users[k].uid,
                                            text: $.trim((users[k].realName?users[k].realName:users[k].login) + " [" + users[k].login + "]"),
                                        });
                                    }
                                }
                            }
                        }
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
                            validate: (v) => {
                                return $.trim(v) !== "";
                            },
                        },
                        {
                            id: "projectId",
                            type: "select2",
                            title: i18n("tt.project"),
                            placeholder: i18n("tt.project"),
                            options: projectsOptions,
                            validate: v => {
                                return parseInt(v);
                            },
                            select: (el, id, prefix) => {
                                $(`#${prefix}filter`).html("").select2({
                                    data: filtersByProject(el.val()),
                                    minimumResultsForSearch: Infinity,
                                    language: lang["_code"],
                                });
                                $(`#${prefix}uid`).html("").select2({
                                    data: uidsByProject(el.val()),
                                    minimumResultsForSearch: Infinity,
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
                                return $.trim(v) !== "";
                            },
                        },
                        {
                            id: "uid",
                            type: "select2",
                            title: i18n("tt.crontabUser"),
                            placeholder: i18n("tt.crontabUser"),
                            options: uidsByProject(project),
                            validate: v => {
                                return $.trim(v) !== "";
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
                    callback: modules.tt.settings.doAddCrontab,
                }).show();
            }).
            fail(FAIL).
            always(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    deleteCrontab: function (crontabId) {
        mConfirm(i18n("tt.confirmCrontabDelete", crontabId), i18n("confirm"), `warning:${i18n("tt.crontabDelete")}`, () => {
            modules.tt.settings.doDeleteCrontab(crontabId);
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
                    filters[i] = modules.tt.meta.filters[i] + " [" + i + "]";
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
                    id: "subject",
                    text: i18n("tt.subject"),
                },
                {
                    id: "description",
                    text: i18n("tt.description"),
                },
            ];

            for (let i in modules.tt.meta.customFields) {
                fields.push({
                    id: "_cf_" + modules.tt.meta.customFields[i].field,
                    text: modules.tt.meta.customFields[i].fieldDisplay,
                });
            }

            cardForm({
                title: i18n("tt.addViewer"),
                footer: true,
                borderless: true,
                topApply: true,
                fields: [
                    {
                        id: "field",
                        type: "select2",
                        title: i18n("tt.viewerField"),
                        placeholder: i18n("tt.viewerField"),
                        options: fields,
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "name",
                        type: "text",
                        title: i18n("tt.viewerName"),
                        placeholder: i18n("tt.viewerName"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                ],
                callback: r => {
                    location.href = `?#tt.settings&section=viewer&field=${encodeURIComponent(r.field)}&name=${encodeURIComponent(r.name)}`;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderViewer: function (field, name) {
        loadingStart();
        GET("tt", "viewer", false, true).
        done(v => {
            let code = `//function ${name} (value, issue, field) {\n\treturn value;\n//}\n`;
            for (let i in v.viewers) {
                if (v.viewers[i].field == field && v.viewers[i].name == name) {
                    code = v.viewers[i].code?v.viewers[i].code:`//function ${name} (value, issue, field) {\n\treturn value;\n//}\n`;
                    break;
                }
            }
            // TODO f..ck!
            let top = 75;
            let height = $(window).height() - top;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="viewerEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="viewerSave" class="hoverable"><i class="fas fa-save pr-2"></i>${i18n("tt.viewerSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("viewerEditor");
            editor.setTheme("ace/theme/chrome");
            editor.session.setMode("ace/mode/javascript");
            editor.setValue(code, -1);
            editor.clearSelection();
            editor.setFontSize(14);
            $("#viewerSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "viewer", false, { field: field, name: name, code: $.trim(editor.getValue()) }).
                fail(FAIL).
                done(() => {
                    message(i18n("tt.viewerWasSaved"));
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
                        location.href = `?#tt.settings&section=viewer&field=${encodeURIComponent(v[k].field)}&name=${encodeURIComponent(v[k].name)}`;
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
                                    },
                                    {
                                        data: (r.viewers[i].field.substring(0, 4) == "_cf_")?cf[r.viewers[i].field]:i18n("tt." + r.viewers[i].field),
                                    },
                                    {
                                        data: r.viewers[i].name,
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

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "workflowsMenu",
            "filters",
            "crontabs",
            "statuses",
            "resolutions",
            "roles",
            "customFieldsMenu",
            "viewersMenu",
        ];

        let section = params["section"]?params["section"]:"projects";

        let top = '';

        for (let i in sections) {
            top += `<li class="nav-item d-none d-sm-inline-block">`;
            if (sections[i] === section) {
                top += `<a href="#tt.settings&section=${sections[i]}" class="nav-link text-primary nav-item-back-selected">${i18n("tt." + sections[i])}</a>`;
            } else {
                top += `<a href="#tt.settings&section=${sections[i]}" class="nav-link text-dark nav-item-back-hover">${i18n("tt." + sections[i])}</a>`;
            }
            top += `</li>`;
        }

        $("#leftTopDynamic").html(top);

        switch (section) {
            case "workflowsMenu":
                modules.tt.settings.renderWorkflows();
                break;

            case "workflow":
                modules.tt.settings.renderWorkflow(params["workflow"]);
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

            case "customFieldsMenu":
                modules.tt.settings.renderCustomFields();
                break;

            case "viewer":
                modules.tt.settings.renderViewer(params["field"], params["name"]);
                break;

            case "viewersMenu":
                modules.tt.settings.renderViewers();
                break;

            default:
                modules.tt.settings.renderProjects();
                break;
        }
    },
}).init();