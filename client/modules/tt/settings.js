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

    doAddTag: function (projectId, tag) {
        loadingStart();
        POST("tt", "tag", false, {
            projectId: projectId,
            tag: tag,
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
        always(modules.tt.settings.renderWorkflows);
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

    doModifyTag: function (tagId, tag, projectId) {
        loadingStart();
        PUT("tt", "tag", tagId, {
            tag: tag,
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
            message(i18n("tt.projectWasChanged"));
        }).
        done(modules.tt.settings.renderRoles);
    },

    doModifyCustomField: function (customFieldId, fieldDisplay, fieldDescription, regex, format, link, options, indexes, required) {
        loadingStart();
        PUT("tt", "customField", customFieldId, {
            fieldDisplay: fieldDisplay,
            fieldDescription: fieldDescription,
            regex: regex,
            format: format,
            link: link,
            options: options,
            indexes: indexes,
            required: required,
        }).
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
                            id: "TextString",
                            text: i18n("tt.customFieldTypeTextString"),
                        },
                        {
                            id: "TextArea",
                            text: i18n("tt.customFieldTypeTextArea"),
                        },
                        {
                            id: "Integer",
                            text: i18n("tt.customFieldTypeInteger"),
                        },
                        {
                            id: "Real",
                            text: i18n("tt.customFieldTypeReal"),
                        },
                        {
                            id: "Select",
                            text: i18n("tt.customFieldTypeSelect"),
                        },
                        {
                            id: "MultiSelect",
                            text: i18n("tt.customFieldTypeMultiSelect"),
                        },
                        {
                            id: "Users",
                            text: i18n("tt.customFieldTypeUsers"),
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
            ],
            delete: i18n("tt.projectDelete"),
            callback: function (result) {
                if (result.delete === "yes") {
                    modules.tt.settings.deleteProject(result.projectId);
                } else {
                    modules.tt.settings.doModifyProject(result.projectId, result.acronym, result.project);
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
                modules.tt.settings.doModifyStatus(statusId, result.display);
            },
        }).show();
    },

    modifyResolution: function (resolutionId) {
        let resolution = '';

        for (let i in modules.tt.meta.resolutions) {
            if (modules.tt.meta.resolutions[i].resolutionId == resolutionId) {
                resolution = modules.tt.meta.resolutions[i].resolution;
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
            ],
            delete: i18n("tt.resolutionDelete"),
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
            title: i18n("tt.roles"),
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
                        value: i18n("tt.customFieldType" + cf.type),
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
                        type: "area",
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
                    },
                ];

                if (cf.type === "Select" || cf.type === "MultiSelect") {
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
                        if (cf.type === "Select" || cf.type === "MultiSelect") {
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
                            value: i18n("tt.customFieldType" + cf.type),
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
                            type: "area",
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
                            hidden: cf.type === "Select" || cf.type === "MultiSelect" || cf.type === "Users",
                        },
                        {
                            id: "format",
                            type: "text",
                            title: i18n("tt.customFieldDisplayFormat"),
                            placeholder: i18n("tt.customFieldDisplayFormat"),
                            value: cf.format,
                            hint: i18n("forExample") + " %.02d",
                            hidden: cf.type === "Text" || cf.type === "MultiSelect" || cf.type === "Users",
                        },
                        {
                            id: "link",
                            type: "text",
                            title: i18n("tt.customFieldLink"),
                            placeholder: i18n("tt.customFieldLink"),
                            value: cf.link,
                            hint: i18n("forExample") + " https://example.com/?search=%value%",
                        },
                        {
                            id: "options",
                            type: "area",
                            title: i18n("tt.customFieldOptions"),
                            placeholder: i18n("tt.customFieldOptions"),
                            value: options,
                            hidden: cf.type !== "Select" && cf.type !== "MultiSelect",
                            validate: (v, prefix) => {
                                return $(`#${prefix}delete`).val() === "yes" || $.trim(v) !== "";
                            }
                        },
                        {
                            id: "usersMultiple",
                            type: "select",
                            title: i18n("tt.usersMultiple"),
                            value: (cf.format && cf.format.indexOf("usersMultiple") >= 0)?"1":"0",
                            options: [
                                {
                                    id: "1",
                                    text: i18n("yes"),
                                },
                                {
                                    id: "0",
                                    text: i18n("no"),
                                },
                            ],
                            hidden: cf.type !== "Users",
                        },
                        {
                            id: "usersWithGroups",
                            type: "select",
                            title: i18n("tt.usersWithGroups"),
                            value: (cf.format && cf.format.indexOf("usersWithGroups") >= 0)?"1":"0",
                            options: [
                                {
                                    id: "1",
                                    text: i18n("yes"),
                                },
                                {
                                    id: "0",
                                    text: i18n("no"),
                                },
                            ],
                            hidden: cf.type !== "Users",
                        },
                        {
                            id: "indexes",
                            type: "select",
                            title: i18n("tt.indexes"),
                            value: cf.indexes,
                            options: [
                                {
                                    id: "0",
                                    text: i18n("no"),
                                },
                                {
                                    id: "1",
                                    text: i18n("tt.index"),
                                },
                                {
                                    id: "2",
                                    text: i18n("tt.fullText"),
                                },
                            ],
                        },
                        {
                            id: "required",
                            type: "select",
                            title: i18n("tt.required"),
                            value: cf.required,
                            options: [
                                {
                                    id: "0",
                                    text: i18n("no"),
                                },
                                {
                                    id: "1",
                                    text: i18n("yes"),
                                },
                            ],
                        },
                    ],
                    delete: i18n("tt.customFieldDelete"),
                    callback: function (result) {
                        if (result.delete === "yes") {
                            modules.tt.settings.deleteCustomField(customFieldId);
                        } else {
                            if (cf.type === "Users") {
                                result.format = "";
                                if (result.usersMultiple === "1") {
                                    result.format += " usersMultiple";
                                }
                                if (result.usersWithGroups === "1") {
                                    result.format += " usersWithGroups";
                                }
                                result.format = $.trim(result.format);
                            }
                            modules.tt.settings.doModifyCustomField(customFieldId, result.fieldDisplay, result.fieldDescription, result.regex, result.format, result.link, result.options, result.indexes, result.required);
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

    setWorkflowAlias: function (workflow) {
        let w = '';

        for (let i in modules.tt.meta.workflowAliases) {
            if (modules.tt.meta.workflowAliases[i].workflow === workflow) {
                w = modules.tt.meta.workflowAliases[i].alias;
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
                modules.tt.settings.doSetWorkflowAlias(workflow, result.alias);
            },
        }).show();
    },

    projectWorkflows: function (projectId) {
        let project = false;
        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let w = {};
        for (let i in modules.tt.meta.workflows) {
            w[modules.tt.meta.workflows[i]] = modules.tt.meta.workflows[i];
        }

        for (let i in modules.tt.meta.workflowAliases) {
            if (w[modules.tt.meta.workflowAliases[i].workflow]) {
                w[modules.tt.meta.workflowAliases[i].workflow] = modules.tt.meta.workflowAliases[i].alias;
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
                modules.tt.settings.doSetProjectWorkflows(projectId, result.workflows);
            },
        }).show();
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
                        users[response.users[i].uid] = $.trim(response.users[i].realName + " [" + response.users[i].login + "]");
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
                                                class: "text-warning",
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
                    roles[modules.tt.meta.roles[i].roleId] = $.trim((modules.tt.meta.roles[i].nameDisplay?modules.tt.meta.roles[i].nameDisplay:i18n("tt." + modules.tt.meta.roles[i].name)) + " [" + modules.tt.meta.roles[i].level + "]");
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
                                            class: "text-warning",
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
                                ],
                                callback: fields => {
                                    modules.tt.settings.doAddTag(projectId, fields.tag);
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
                    for (let i in modules.tt.meta.tags) {
                        if (modules.tt.meta.tags[i].projectId == projectId && modules.tt.meta.tags[i].tagId == tagId) {
                            tag = modules.tt.meta.tags[i].tag;
                        }
                    }
                    cardForm({
                        title: i18n("tt.addTag"),
                        footer: true,
                        borderless: true,
                        topApply: true,
                        apply: i18n("add"),
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
                        ],
                        callback: result => {
                            if (result.delete === "yes") {
                                mConfirm(i18n("tt.confirmDeleteTag", tagId), i18n("confirm"), `danger:${i18n("tt.deleteTag")}`, () => {
                                    modules.tt.settings.doDeleteTag(tagId, projectId);
                                });
                            } else {
                                modules.tt.settings.doModifyTag(tagId, fields.tag, projectId);
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
                                        data: modules.tt.meta.tags[i].tag,
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
        GET("tt", "customWorkflow", workflow, true).
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
            editor.session.setMode("ace/mode/php");
            editor.setValue(w.body, -1);
            editor.clearSelection();
            editor.setFontSize(14);
            $("#workflowSave").off("click").on("click", () => {
                loadingStart();
                PUT("tt", "customWorkflow", workflow, { "body": $.trim(editor.getValue()) }).
                fail(FAIL).
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
            DELETE("tt", "customWorkflow", workflow, false).
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
                location.href = "#tt.settings&section=workflow&workflow=" + f.file;
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
                        title: i18n("tt.workflowAlias"),
                        fullWidth: true,
                    },
                ],
                edit: modules.tt.settings.setWorkflowAlias,
                rows: () => {
                    let rows = [];

                    let w = {};
                    for (let i = 0; i < modules.tt.meta.workflowAliases.length; i++) {
                        w[modules.tt.meta.workflowAliases[i].workflow] = modules.tt.meta.workflowAliases[i].alias;
                    }

                    for (let i = 0; i < modules.tt.meta.workflows.length; i++) {
                        rows.push({
                            uid: modules.tt.meta.workflows[i].file,
                            cols: [
                                {
                                    data: modules.tt.meta.workflows[i].file,
                                },
                                {
                                    data: w[modules.tt.meta.workflows[i].file]?w[modules.tt.meta.workflows[i].file]:modules.tt.meta.workflows[i].file,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "far fa-file-code",
                                        title: i18n("tt.editWorkflow"),
                                        click: workflow => {
                                            location.href = "#tt.settings&section=workflow&workflow=" + workflow;
                                        },
                                    },
                                    {
                                        icon: "far fa-trash-alt",
                                        title: i18n("tt.deleteWorkflow"),
                                        click: workflow => {
                                            modules.tt.settings.deleteWorkflow(workflow);
                                        },
                                        disabled: modules.tt.meta.workflows[i].type === "builtIn",
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
                                    data: i18n("tt.customFieldType" + modules.tt.meta.customFields[i].type),
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

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        let sections = [
            "projects",
            "workflows",
            "filters",
            "statuses",
            "resolutions",
            "roles",
            "customFields",
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
            case "workflows":
                modules.tt.settings.renderWorkflows();
                break;

            case "workflow":
                modules.tt.settings.renderWorkflow(params["workflow"]);
                break;

            case "filters":
                modules.tt.settings.renderFilters();
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

            case "customFields":
                modules.tt.settings.renderCustomFields();
                break;

            default:
                modules.tt.settings.renderProjects();
                break;
        }
    },
}).init();