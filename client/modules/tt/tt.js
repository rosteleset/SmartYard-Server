({
    meta: {},

    defaultIssuesPerPage: 50,
    defaultPagerItemsCount: 10,
    menuItem: false,

    specialActions: [
        "saAddComment",
        "saAddFile",
        "saAddSingleFile",
        "saAddSingleFileQuiet",
        "saAssignToMe",
        "saWatch",
        "saDelete",
        "saSubIssue",
        "saCoordinate",
        "saLink",
    ],

    init: function () {
        if (AVAIL("tt", "tt")) {
            if (parseInt(myself.uid) == 0) {
                leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "?#tt.settings", "tt");
                this.menuItem = false;
            } else {
                this.menuItem = leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "?#tt", "tt");
            }
        }
        loadSubModules("tt", [
            "issue",
            "settings",
            "workspaces",
        ], this);
    },

    allLoaded: function () {
        if (parseInt(myself.uid) && AVAIL("tt", "favoriteFilter")) {
            GET("tt", "tt", false, true).
            done(modules.tt.tt).
            done(() => {
                let h = "";
                for (let i in modules.tt.meta.favoriteFilters) {
                    if (parseInt(modules.tt.meta.favoriteFilters[i].rightSide)) {
                        let title = modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[i].filter].shortName ? modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[i].filter].shortName : modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[i].filter].name;
                        h += `
                            <li class="nav-item" title="${escapeHTML(title)}">
                                <a href="?#tt&filter=${modules.tt.meta.favoriteFilters[i].filter}" class="nav-link">
                                    <i class="nav-icon fa fa-fw ${modules.tt.meta.favoriteFilters[i].icon} ${modules.tt.meta.favoriteFilters[i].color}"></i>
                                    <p class="text-nowrap">${title}</p>
                                </a>
                            </li>
                        `;
                    }
                }
                if (modules.tt.menuItem) {
                    let i = $('#' + modules.tt.menuItem);
                    while (i.next().length) {
                        i = i.next();
                        if ($.trim(i.text()) == "") {
                            $(h).insertBefore(i);
                            f = true;
                            return;
                        }
                    }
                    if (i.length) {
                        $(h).insertAfter(i);
                    }
                }
            }).
            fail(FAIL);
        }
    },

    moduleLoaded: function () {
        //
    },

    issueFieldTitle: function (field) {
        let fieldId;

        if (typeof field === "object") {
            fieldId = field.field;
        } else{
            fieldId = field;
        }

        if (fieldId.toString().substring(0, 4) !== "_cf_") {
            // regular issue fields
            switch (fieldId) {
                case "issueId":
                    return i18n("tt.issueId");

                case "subject":
                    return i18n("tt.subject");

                case "description":
                    return i18n("tt.description");

                case "comment":
                    return i18n("tt.comment");

                case "optionalComment":
                    return i18n("tt.comment");

                case "resolution":
                    return i18n("tt.resolution");

                case "status":
                    return i18n("tt.status");

                case "tags":
                    return i18n("tt.tags");

                case "assigned":
                    return i18n("tt.assigned");

                case "watchers":
                    return i18n("tt.watchers");

                case "links":
                    return i18n("tt.links");

                case "attachments":
                    return i18n("tt.attachments");

                case "created":
                    return i18n("tt.created");

                case "updated":
                    return i18n("tt.updated");

                case "author":
                    return i18n("tt.author");

                case "project":
                    return i18n("tt.project");

                case "workflow":
                    return i18n("tt.workflowName");

                case "commentAuthor":
                    return i18n("tt.commentAuthor");

                case "commentBody":
                    return i18n("tt.commentBody");

                case "commentCreated":
                    return i18n("tt.commentCreated");

                case "commentPrivate":
                    return i18n("tt.commentPrivate");

                case "attachmentFilename":
                    return i18n("tt.attachmentFilename");

                case "catalog":
                    return i18n("tt.catalog");

                case "parent":
                    return i18n("tt.parent");

                // virtual journal field
                case "workflowAction":
                    return i18n("tt.workflowAction");

                default:
                    return fieldId;
            }
        } else {
            // custom field
            fieldId = fieldId.substring(4);

            let cf = false;
            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].field === fieldId) {
                    cf = modules.tt.meta.customFields[i];
                    break;
                }
            }

            if (cf) {
                return cf.fieldDisplay;
            } else {
                return fieldId;
            }
        }
    },

    issueField2FormFieldEditor: function (issue, field, projectId, filter, prefferredValue) {

        function peoples(project, withGroups, withUsers) {
            let p = [];

            let already = {
                "admin": true,
            };

            if (withGroups && modules.groups) {
                for (let i in project.groups) {
                    for (let j in modules.groups.meta) {
                        if (modules.groups.meta[j].gid == project.groups[i].gid && !already[modules.groups.meta[j].acronym]) {
                            already[modules.groups.meta[j].acronym] = true;
                            p.push({
                                id: modules.groups.meta[j].acronym,
                                text: modules.groups.meta[j].name ? (modules.groups.meta[j].name + " [" + modules.groups.meta[j].acronym + "]") : modules.groups.meta[j].acronym,
                                icon: "fas fa-fw fa-users",
                            });
                        }
                    }
                }
            }

            if (withUsers) {
                for (let i in project.users) {
                    for (let j in modules.users.meta) {
                        if (modules.users.meta[j].uid == project.users[i].uid && !already[modules.users.meta[j].login]) {
                            already[modules.users.meta[j].login] = true;
                            if (project.users[i].level > 0) {
                                p.push({
                                    id: modules.users.meta[j].login,
                                    text: modules.users.meta[j].realName ? (modules.users.meta[j].realName + " [" + modules.users.meta[j].login + "]") : modules.users.meta[j].login,
                                    icon: "fas fa-fw fa-user",
                                });
                            }
                        }
                    }
                }
            }

            return p;
        }

        function select2Filter(options, filter) {
            if (filter) {
                let o = [];

                for (let i in options) {
                    for (let j in filter) {
                        if (options[i].id == filter[j] || options[i].text == filter[j]) {
                            o.push(options[i]);
                            break;
                        }
                    }
                }

                return o;
            } else {
                return options;
            }
        }

        let fieldId;

        if (typeof field === "object") {
            fieldId = field.field;
        } else{
            fieldId = field;
        }

        let tags = [];
        for (let i in modules.tt.meta.tags) {
            if (modules.tt.meta.tags[i].projectId == projectId) {
                tags.push({
                    id: modules.tt.meta.tags[i].tag,
                    text: modules.tt.meta.tags[i].tag,
                    icon: "fas fa-fw fa-tag",
                });
            }
        }

        let project;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].projectId == projectId) {
                project = modules.tt.meta.projects[i];
            }
        }

        if (fieldId && fieldId.substring(0, 4) !== "_cf_") {
            // regular issue fields
            switch (fieldId) {
                case "issueId":
                    return {
                        id: "issueId",
                        type: "text",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.issueId) ? issue.issueId : ""),
                        hidden: true,
                    };

                case "subject":
                    return {
                        id: "subject",
                        type: "text",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.subject) ? issue.subject : ""),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "description":
                    return {
                        id: "description",
                        type: "area",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.description) ? issue.description : ""),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "comment":
                    return {
                        id: "comment",
                        type: "area",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "optionalComment":
                    return {
                        id: "comment",
                        type: "area",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                    };

                case "resolution":
                    let resolutions = [];

                    for (let i in modules.tt.meta.resolutions) {
                        if (project.resolutions.indexOf(modules.tt.meta.resolutions[i].resolutionId) >= 0) {
                            resolutions.push({
                                id: modules.tt.meta.resolutions[i].resolution,
                                text: modules.tt.meta.resolutions[i].resolution,
                            });
                        }
                    }

                return {
                    id: "resolution",
                    type: "select2",
                    title: modules.tt.issueFieldTitle(field),
                    options: select2Filter(resolutions, filter),
                    value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.resolution) ? issue.resolution : -1),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                };

                case "status":
                    let statuses = [];

                    for (let i in modules.tt.meta.statuses) {
                        statuses.push({
                            id: modules.tt.meta.statuses[i].status,
                            text: modules.tt.meta.statuses[i].status,
                        });
                    }

                    return {
                        id: "status",
                        type: "select2",
                        title: modules.tt.issueFieldTitle(field),
                        options: select2Filter(statuses, filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.status) ? issue.status : -1),
                    };

                case "tags":
                    return {
                        id: "tags",
                        type: "select2",
                        tags: true,
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(tags, filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.tags) ? Object.values(issue.tags) : []),
                    };

                case "assigned":
                    return {
                        id: "assigned",
                        type: "select2",
                        multiple: [3, 4, 5].indexOf(parseInt(project.assigned)) >= 0,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(peoples(project, [0, 2, 3, 5].indexOf(parseInt(project.assigned)) >= 0, [0, 1, 3, 4].indexOf(parseInt(project.assigned)) >= 0), filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.assigned) ? Object.values(issue.assigned) : []),
                    };

                case "watchers":
                    return {
                        id: "watchers",
                        type: "select2",
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(peoples(project, false, true), filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue:((issue && issue.watchers) ? Object.values(issue.watchers) : []),
                    };

                case "links":
                    let vi = [];
                    options = [];

                    if (issue && issue[fieldId]) {
                        let va;

                        if (typeof issue[fieldId] == "string") {
                            va = [ issue[fieldId] ];
                        } else {
                            va = issue[fieldId];
                        }
                        for (let i in va) {
                            vi.push(va[i]);
                            options.push({
                                id: va[i],
                                text: va[i],
                            });
                        }
                    }

                    return {
                        id: fieldId,
                        type: "select2",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(options, filter),
                        multiple: true,
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : vi,
                        ajax: {
                            delay: 1000,
                            transport: function (params, success) {
                                if (params.data.term) {
                                    QUERY("tt", "issues", {
                                        project: project.acronym,
                                        filter: "#issueSearch",
                                        skip: 0,
                                        limit: 32768,
                                        search: params.data.term,
                                    }).
                                    then(success).
                                    fail(response => {
                                        FAIL(response);
                                        success({
                                            issues: {
                                                issues: [],
                                            }
                                        });
                                    });
                                } else {
                                    success({
                                        issues: {
                                            issues: [],
                                        }
                                    });
                                }
                            },
                            processResults: function (data) {
                                let suggestions = options;
                                for (let i in data.issues.issues) {
                                    let vl = "[ " + data.issues.issues[i].issueId + " ] " + data.issues.issues[i].subject;
                                    if (vi.indexOf(vl) < 0) {
                                        suggestions.push({
                                            id: data.issues.issues[i].issueId,
                                            text: vl,
                                        });
                                    }
                                }
                                return {
                                    results: suggestions,
                                };
                            },
                        },
                    }

                case "attachments":
                    return {
                        id: "attachments",
                        type: "files",
                        title: modules.tt.issueFieldTitle(field),
                        maxSize: project.maxFileSize,
                    };

                case "workflow":
                    let workflows = [];

                    for (let i in modules.tt.meta.workflows) {
                        workflows[i] = modules.tt.meta.workflows[i].name ? modules.tt.meta.workflows[i].name : i;
                    }

                    function workflowsByProject(project) {
                        let w;

                        if (project) {
                            for (let i in modules.tt.meta.projects) {
                                if (modules.tt.meta.projects[i].acronym == project) {
                                    for (let j in modules.tt.meta.projects[i].workflows) {
                                        let wn = $.trim(workflows[modules.tt.meta.projects[i].workflows[j]] ? workflows[modules.tt.meta.projects[i].workflows[j]] : modules.tt.meta.projects[i].workflows[j]);
                                        if (wn.charAt(0) == "#") {
                                            wn = wn.substring(1);
                                        }
                                        w.push({
                                            id: modules.tt.meta.projects[i].workflows[j],
                                            text: wn,
                                        });
                                    }
                                    break;
                                }
                            }
                        }

                        return w;
                    }

                    return {
                        id: "workflow",
                        type: "select2",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(workflowsByProject(project), filter),
                        value: issue.workflow,
                    };
            }
        } else {
            if (fieldId) {
                // custom field
                fieldId = fieldId.substring(4);

                let cf = false;
                for (let i in modules.tt.meta.customFields) {
                    if (modules.tt.meta.customFields[i].field === fieldId) {
                        cf = modules.tt.meta.customFields[i];
                        break;
                    }
                }

                if (cf && cf.type !== "virtual") {
                    let validate = false;

                    if (cf.required && !cf.regex) {
                        validate = new Function ("v", `return v && $.trim(v) !== "";`);
                    } else
                    if (!cf.required && cf.regex) {
                        validate = new Function ("v", `return /${cf.regex}/.test(v);`);
                    } else
                    if (cf.required && cf.regex) {
                        validate = new Function ("v", `return v && $.trim(v) !== "" && /${cf.regex}/.test(v);`);
                    }

                    let options = [];

                    switch (cf.type) {
                        case "text":
                            if (cf.editor == "yesno") {
                                prefferredValue = 1;
                            }

                            if (cf.editor == "noyes") {
                                prefferredValue = 0;
                            }

                            let ro = cf.editor == "text-ro";
                            let val;

                            if (ro && typeof prefferredValue !== "undefined") {
                                val = prefferredValue;
                            } else {
                                if (issue && issue["_cf_" + fieldId]) {
                                    val = issue["_cf_" + fieldId];
                                } else {
                                    if (typeof prefferredValue !== "undefined") {
                                        val = prefferredValue;
                                    } else {
                                        val = "";
                                    }
                                }
                            }

                            if (cf.editor == "date" || cf.editor == "datetime-local") {
                                if (parseInt(val) <= 0) {
                                    val = '';
                                }
                            }

                            if ([ "text", "number", "area", "email", "tel", "date", "time", "datetime-local", "yesno", "noyes", "json" ].indexOf(cf.editor) < 0) {
                                cf.editor = "text";
                            }

                            return {
                                id: "_cf_" + fieldId,
                                type: cf.editor,
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription ? cf.fieldDescription : false,
                                sec: true,
                                value: val,
                                readonly: ro,
                                validate: validate,
                            }

                        case "select":
                            let already = {};

                            if (cf.format.indexOf("suggestions") >= 0) {
                                already[""] = 1;
                                options.push({
                                    id: "",
                                    text: "-",
                                });
                            }

                            for (let i in cf.options) {
                                if (!already[cf.options[i].option]) {
                                    already[cf.options[i].option] = 1;
                                    options.push({
                                        id: cf.options[i].option,
                                        text: cf.options[i].optionDisplay,
                                    });
                                }
                            }

                            let value = (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue["_cf_" + fieldId]) ? issue["_cf_" + fieldId] : []);

                            if (value && $.trim(value) && !already[value]) {
                                options.push({
                                    id: value,
                                    text: value,
                                });
                            }

                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: cf.format.indexOf("multiple") >= 0,
                                tags: cf.format.indexOf("editable") >= 0,
                                createTags: cf.format.indexOf("editable") >= 0,
                                value: value,
                                validate: validate,
                                ajax: (cf.format.indexOf("suggestions") >= 0) ? {
                                    delay: 1000,
                                    transport: function (params, success) {
                                        if (params.data.term) {
                                            QUERY("tt", "suggestions", {
                                                project: project.acronym,
                                                field: "_cf_" + fieldId,
                                                query: params.data.term,
                                            }).
                                            then(success).
                                            fail(response => {
                                                FAIL(response);
                                                success(false);
                                            });
                                        } else {
                                            success(false);
                                        }
                                    },
                                    processResults: function (data) {
                                        let suggestions = []; //= options;
                                        if (data && data.suggestions) {
                                            for (let i in data.suggestions) {
                                                let a = false;
                                                for (let j in options) {
                                                    if (data.suggestions[i] == options[j].id) {
                                                        a = true;
                                                        break;
                                                    }
                                                }
                                                if (!a) {
                                                    suggestions.push({
                                                        id: data.suggestions[i],
                                                        text: data.suggestions[i],
                                                    });
                                                }
                                            }
                                        } else {
                                            suggestions = options;
                                        }
                                        return {
                                            results: suggestions,
                                        };
                                    },
                                } : undefined,
                            }

                        case "users":
                            if (cf.format.split(" ").includes("users")) {
                                options = peoples(project, false, true);
                            } else
                            if (cf.format.split(" ").includes("groups")) {
                                options = peoples(project, true, false);
                            } else
                            if (cf.format.split(" ").includes("usersAndGroups")) {
                                options = peoples(project, true, true);
                            }

                            let vu = (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue["_cf_" + fieldId]) ? issue["_cf_" + fieldId] : []);

                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: cf.format.indexOf("multiple") >= 0,
                                value: object2array(vu),
                                validate: validate,
                            }

                        case "array":
                            let ax = [];

                            for (let i in issue["_cf_" + fieldId]) {
                                options.push({
                                    id: issue["_cf_" + fieldId][i],
                                    text: issue["_cf_" + fieldId][i],
                                });
                                ax.push(issue["_cf_" + fieldId][i]);
                            }

                            options.sort((a, b) => {
                                if (a.id > b.id) {
                                    return 1;
                                }
                                if (a.id < b.id) {
                                    return -1;
                                }
                                return 0;
                            });

                            ax.sort((a, b) => {
                                if (a > b) {
                                    return 1;
                                }
                                if (a < b) {
                                    return -1;
                                }
                                return 0;
                            });

                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: true,
                                tags: true,
                                createTags: true,
                                value: (typeof prefferredValue !== "undefined")?prefferredValue:(ax?ax:[]),
                                validate: validate,
                            }

                        case "geo":
                            let vx;

                            if (issue && issue["_cf_" + fieldId]) {
                                vx = issue["_cf_" + fieldId];
                                options = [
                                    {
                                        id: issue["_cf_" + fieldId],
                                        text: issue["_cf_" + fieldId],
                                    },
                                ];
                            }

                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[]),
                                validate: validate,
                                ajax: {
                                    delay: 1000,
                                    transport: function (params, success) {
                                        if (params.data.term) {
                                            QUERY("geo", "suggestions", {
                                                search: params.data.term,
                                            }).
                                            then(success).
                                            fail(response => {
                                                FAIL(response);
                                                success({
                                                    suggestions: [],
                                                });
                                            });
                                        } else {
                                            success({
                                                suggestions: [],
                                            });
                                        }
                                    },
                                    processResults: function (data) {
                                        let suggestions = options;
                                        for (let i in data.suggestions) {
                                            let vl = " [ " + data.suggestions[i].data.geo_lon + ", " + data.suggestions[i].data.geo_lat + " ]: " + data.suggestions[i].value;
                                            if ((parseInt(data.suggestions[i].data.fias_level) === 8 || (parseInt(data.suggestions[i].data.fias_level) === -1 && data.suggestions[i].data.house)) && vx !== vl) {
                                                suggestions.push({
                                                    id: vl,
                                                    text: vl,
                                                });
                                            }
                                        }
                                        return {
                                            results: suggestions,
                                        };
                                    },
                                },
                            }

                        case "issues":
                            let vi = [];
                            options = [];

                            if (issue && issue["_cf_" + fieldId]) {
                                let va;

                                if (typeof issue["_cf_" + fieldId] == "string") {
                                    va = [ issue["_cf_" + fieldId] ];
                                } else {
                                    va = issue["_cf_" + fieldId];
                                }
                                for (let i in va) {
                                    vi.push(va[i]);
                                    options.push({
                                        id: va[i],
                                        text: va[i],
                                    });
                                }
                            }

                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: cf.format.indexOf("multiple") >= 0,
                                value: (typeof prefferredValue !== "undefined")?prefferredValue:vi,
                                validate: validate,
                                ajax: {
                                    delay: 1000,
                                    transport: function (params, success) {
                                        if (params.data.term) {
                                            QUERY("tt", "issues", {
                                                project: project.acronym,
                                                filter: "#issueSearch",
                                                skip: 0,
                                                limit: 32768,
                                                search: params.data.term,
                                            }).
                                            then(success).
                                            fail(response => {
                                                FAIL(response);
                                                success({
                                                    issues: {
                                                        issues: [],
                                                    }
                                                });
                                            });
                                        } else {
                                            success({
                                                issues: {
                                                    issues: [],
                                                }
                                            });
                                        }
                                    },
                                    processResults: function (data) {
                                        let suggestions = options;
                                        for (let i in data.issues.issues) {
                                            let vl = "[ " + data.issues.issues[i].issueId + " ] " + data.issues.issues[i].subject;
                                            if (vi.indexOf(vl) < 0) {
                                                suggestions.push({
                                                    id: data.issues.issues[i].issueId,
                                                    text: vl,
                                                });
                                            }
                                        }
                                        return {
                                            results: suggestions,
                                        };
                                    },
                                },
                            }
                    }
                }
            }
        }
    },

    issueField2Html: function (issue, field, val, target, filter) {
        let members = {};
        let escaped = false;

        if (modules.groups) {
            for (let i in modules.groups.meta) {
                members[modules.groups.meta[i].acronym] = modules.groups.meta[i].name?modules.groups.meta[i].name:modules.groups.meta[i].acronym;
            }
        }

        for (let i in modules.users.meta) {
            members[modules.users.meta[i].login] = modules.users.meta[i].realName?modules.users.meta[i].realName:modules.users.meta[i].login;
        }

        let project;
        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].acronym == issue.project || modules.tt.meta.projects[i].acronym == issue.issueId.split("-")[0]) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        let v = false;
        for (let i in project.viewers) {
            if (project.viewers[i].field == field) {
                v = project.viewers[i].name;
                break;
            }
        }

        if (typeof val === "undefined") {
            val = issue[field];
        }

        if (v && modules.tt.viewers[field] && typeof modules.tt.viewers[field][v] == "function") {
            val = modules.tt.viewers[field][v](val, issue, field, target, filter);
        } else {
            if (val == null || val == "&nbsp;") {
                return "";
            }

            if (field.substring(0, 4) !== "_cf_") {

                switch (field) {
                    case "description":
                    case "subject":
                    case "commentBody":
                        escaped = true;
                        val = convertLinks(nl2br(escapeHTML(val)));
                        break;

                    case "assigned":
                    case "watchers":
                        let m = [];

                        for (let i in val) {
                            m.push('<span style="white-space: nowrap!important;">' + escapeHTML(members[val[i]] ? members[val[i]] : val[i]) + '</span>');
                        }

                        if (m.length) {
                            val = m.join(", ");
                            escaped = true;
                        } else {
                            val = "";
                        }

                        break;

                    case "author":
                        val = escapeHTML(members[val] ? members[val] : val);
                        break;

                    case "commentPrivate":
                        val = val ? i18n("yes") : i18n("no");
                        break;

                    case "status":
                        if (val) {
                            val = escapeHTML(val);
                            escaped = true;
                        } else {
                            val = '';
                        }
                        break;

                    case "resolution":
                        if (val) {
                            val = escapeHTML(val);
                            escaped = true;
                        } else {
                            val = '';
                        }
                        break;

                    case "project":
                        for (let i in modules.tt.meta.projects) {
                            if (modules.tt.meta.projects[i].acronym == val) {
                                val = modules.tt.meta.projects[i].project?modules.tt.meta.projects[i].project:val;
                                break;
                            }
                        }
                        break;

                    case "workflow":
                        for (let i in modules.tt.meta.workflows) {
                            if (i == val) {
                                val = modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:val;
                                if (val.charAt(0) == "#") {
                                    val = val.substring(1);
                                }
                                break;
                            }
                        }
                        break;

                    case "parent":
                        val = `<span class='hoverable ttIssue text-bold'>${val}</span>`;
                        break;

                    case "created":
                    case "updated":
                    case "commentCreated":
                        val = '<span style="white-space: nowrap!important;">' + ttDate(val) + '</span>';
                        escaped = true;
                        break;

                    case "workflowAction":
                        val = modules.tt.displayAction(val);
                        break;
                }
            } else {
                field = field.substring(4);

                let cf = {};
                let multiple = false;

                for (let i in modules.tt.meta.customFields) {
                    if (modules.tt.meta.customFields[i].field == field) {
                        cf = modules.tt.meta.customFields[i];
                        multiple = cf && cf.format && cf.format.indexOf("multiple") >= 0;
                    }
                }

                switch (cf.type) {
                    case "geo":
                        if (val) {
                            let lon = $.trim(val.split("[")[1].split(",")[0]);
                            let lat = $.trim(val.split("[")[1].split(",")[1].split("]")[0]);
                            val = `<a target="_blank" class="hoverable" href="https://maps.yandex.ru/?text=${lon}%2C${lat}">${escapeHTML(val)}</a>`;
                        } else {
                            val = '';
                        }
                        break;

                    case "issues":
                        if (val) {
                            if (typeof val == "string") {
                                val = [ val ];
                            }

                            let t = "";

                            for (let i in val) {
                                let issueId = $.trim(val[i].split("[")[1].split("]")[0]);
                                let subject = $.trim(val[i].substring(val[i].indexOf("]") + 1));
                                t += `<a href="?#tt&issue=${encodeURIComponent(issueId)}" class="hoverable">[ ${issueId} ]: ${escapeHTML(subject)}</a><br/>`;
                            }

                            val = t;
                        } else {
                            val = '';
                        }
                        break;

                    case "array":
                        if (val) {
                            if (typeof val == "string") {
                                val = [ val ];
                            }

                            let vt = [];

                            for (let i in val) {
                                vt.push(val[i]);
                            }

                            vt.sort((a, b) => {
                                if (a > b) {
                                    return 1;
                                }
                                if (a < b) {
                                    return -1;
                                }
                                return 0;
                            });

                            let t = "";

                            if (target != "journal") {
                                t = "<ul class='mb-1'>";

                                for (let i in vt) {
                                    t += `<li>${escapeHTML(vt[i])}</li>`;
                                }

                                t += "</ul>";
                            } else {
                                for (let i in vt) {
                                    t += escapeHTML(vt[i]) + ", ";
                                }

                                if (t) {
                                    t = t.substring(0, t.length - 2);
                                }
                            }

                            val = t;
                        } else {
                            val = '';
                        }
                        break;

                    case "text":
                        if (cf.format) {
                            val = sprintf(cf.format, val);
                        }

                        switch (cf.editor) {
                            case "yesno":
                            case "noyes":
                                val = parseInt(val)?i18n("yes"):i18n("no");
                                break;

                            case "json":
                                return "<pre style='padding: 0px!important; margin: 0px!important;'>" + escapeHTML(JSON.stringify(val, null, 2)) + "</pre>";

                            case "datetime-local":
                                val = '<span style="white-space: nowrap!important;">' + ttDate(val) + '</span>';
                                escaped = true;
                                break;

                            case "date":
                                val = '<span style="white-space: nowrap!important;">' + ttDate(val, true) + '</span>';
                                escaped = true;
                                break;
                        }

                        if (!escaped) {
                            val = nl2br(escapeHTML(val));
                        }

                        if (cf.link) {
                            val = "<a href='" + cf.link.replaceAll('%value%', val) + "' target='_blank' class='hover'>" + val + "</a>";
                        }

                        break;

                    case "select":
                        if (multiple) {
                            let s = "";
                            let x = [];

                            for (let i in val) {
                                x.push(val[i]);
                            }

                            for (let i in cf.options) {
                                if (x.indexOf(cf.options[i].option) >= 0) {
                                    s += '<i class="far fa-fw fa-check-square mr-1"></i>';
                                } else {
                                    s += '<i class="far fa-fw fa-square mr-1"></i>';
                                }
                                s += cf.options[i].optionDisplay + "<br/>";
                            }

                            val = s;
                        }
                        break;

                    case "users":
                        if (typeof val == "array" || typeof val == "object") {
                            let m = "";

                            for (let i in val) {
                                m += members[val[i]] ? members[val[i]] : val[i];
                                m += ", ";
                            }

                            if (m) {
                                m = m.substring(0, m.length - 2);
                            }

                            val = escapeHTML(m);
                        } else {
                            val = escapeHTML(members[val] ? members[val] : val);
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        if (val === false || typeof val === "undefined") {
            val = "";
        }

        return val;
    },

    displayAction: function (action) {
        if (modules.tt.specialActions.indexOf(action) >= 0) {
            return i18n("tt." + action);
        } else {
            return action
        }
    },

    tt: function (tt) {
        modules.tt.meta = tt["meta"];

        modules.tt.meta.finalStatus = {};

        if (!modules.tt.viewers) {
            modules.tt.viewers = {};
        }

        for (let i in modules.tt.meta.viewers) {
            if (!modules.tt.viewers[modules.tt.meta.viewers[i].field]) {
                modules.tt.viewers[modules.tt.meta.viewers[i].field] = {};
            }
            try {
                modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'issue', 'field', 'target', 'filter', modules.tt.meta.viewers[i].code);
            } catch (e) {
                console.error(e);
                modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'issue', 'field', 'target', 'filter', "//function $name (value, field, issue, terget, filter) {\n\treturn value;\n//}\n");
            }
        }

        for (let i in modules.tt.meta.statuses) {
            if (parseInt(modules.tt.meta.statuses[i].final)) {
                modules.tt.meta.finalStatus[modules.tt.meta.statuses[i].status] = true;
            }
        }
    },

    selectFilter: function (filter, skip, limit, search) {
        if (filter) {
            if (filter[0] !== "#") {
                lStore("tt_issue_filter_" + $("#ttProjectSelect").val(), filter);
            }
        } else {
            filter = lStore("tt_issue_filter_" + $("#ttProjectSelect").val());
        }
        window.location.href = navigateUrl("tt", {
            filter: filter,
            skip: skip ? skip : 0,
            limit: limit ? limit : modules.tt.defaultIssuesPerPage,
            search: ($.trim(search) && typeof search === "string") ? $.trim(search) : "",
        });
    },

    selectProject: function (project) {
        lStore("tt_project", project);
        window.location.href = navigateUrl("tt", {
            project: project,
        });
    },

    renderIssues: function (params, target, issuesListId, callback, fail) {
        if (target === "undefined") {
            target = false;
        }

        if (issuesListId === "undefined" || !issuesListId) {
            issuesListId = md5(guid());
        }

        let rtd = '';

        let current_project;

        if (target) {
            current_project = params.project;
        } else {
            current_project = params.project ? params.project : lStore("tt_project");
        }

        let pn = {};

        for (let i in modules.tt.meta.projects) {
            pn[modules.tt.meta.projects[i].acronym] = modules.tt.meta.projects[i].project;
        }

        let pc = Object.keys(modules.tt.meta.myRoles).length;

        if (pc) {
            let cog = "mt-1";
            if (AVAIL("tt", "project", "POST")) {
                cog = "";
            }
            if (pc == 1) {
                rtd += `<div class="form-inline"><div class="input-group input-group-sm mr-2 ${cog}"><select id="ttProjectSelect" class="form-control select-arrow" style="display: none;">`;
            } else {
                rtd += `<div class="form-inline"><div class="input-group input-group-sm mr-2 ${cog}"><select id="ttProjectSelect" class="form-control select-arrow">`;
            }
            for (let j in modules.tt.meta.myRoles) {
                if (j == current_project) {
                    rtd += `<option selected="selected" value="${j}">${pn[j]} [${j}]</option>`;
                } else {
                    rtd += `<option value="${j}">${pn[j]} [${j}]</option>`;
                }
            }
            rtd += `</select></div>`;
            rtd += '<form autocomplete="off" onsubmit="return false;" method="post" action="">';
            rtd += '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
            rtd += `<div class="input-group input-group-sm ${cog} ttSearchInputGroup">`;
            rtd += `<input id="ttSearch" class="form-control" type="search" aria-label="Search" autocomplete="off"><div class="input-group-append"><button class="btn btn-default" id="ttSearchButton" title="${i18n("tt.search")}"><i class="fas fa-search"></i></button></div>`;
            rtd += `</div>`;
            rtd += '</form>';
            if (AVAIL("tt", "project", "POST")) {
                rtd += `<div class="nav-item mr-0 pr-0"><a href="?#tt.settings" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.settings")}"><i class="fas fa-lg fa-fw fa-cog"></i></a></div>`;
            }
        } else {
            if (AVAIL("tt", "project", "POST")) {
                rtd += `<div class="nav-item mr-0 pr-0"><a href="?#tt.settings" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.settings")}"><i class="fas fa-lg fa-fw fa-cog"></i></a></div>`;
            }
        }

        if (!target) {
            $("#rightTopDynamic").html(rtd);
            current_project = $("#ttProjectSelect").val();
        }

        if (AVAIL("tt", "customFilter") && current_project && current_project !== true) {
            $(".ttSearchInputGroup").prepend(`<div class="input-group-prepend"><span class="input-group-text pointer-input-group ttFilterCustom" title="${i18n("tt.customSearch")}"><i class="fas fa-fw fa-running"></i></span></div>`);
        }

        $("#ttProjectSelect").off("change").on("change", () => {
            modules.tt.selectProject($("#ttProjectSelect").val());
        });

        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].acronym == current_project) {
                project = modules.tt.meta.projects[i];
            }
        }

        if (Object.keys(modules.tt.meta.myRoles).length) {
            $("#ttProjectSelect").css("width", $("#ttSearch").parent().css("width"));
        }

        $(".ttFilterCustom").off("click").on("click", () => {
            window.location.href = '?#tt&filter=empty&customSearch=yes&_=' + Math.random();
        });

        $("#ttSearch").off("keypress").on("keypress", ev => {
            if (ev.keyCode == 13) {
                $("#ttSearchButton").click();
                ev.preventDefault();
                return false;
            }
        });

        $("#ttSearchButton").off("click").on("click", () => {
            let s = $.trim($("#ttSearch").val());
            if (s) {
                let i = new RegExp("^[a-zA-Z]{2,}-[0-9]{1,}$");
                if (i.test(s)) {
                    window.location.href = "?#tt&issue=" + s.toUpperCase() + "&search=" + s.toUpperCase() + "&_=" + Math.random();
                } else {
                    modules.tt.selectFilter("#search", 0, modules.tt.defaultIssuesPerPage, s);
                }
            }
        });

        if ($.trim(params.search) && params.search !== true) {
            $("#ttSearch").val($.trim(params.search));
        }

        let x = false;

        if (target) {
            try {
                x = params["filter"];
            } catch (e) {
                //
            }
        } else {
            try {
                x = params["filter"] ? params["filter"] : lStore("tt_issue_filter_" + current_project);
            } catch (e) {
                //
            }
        }

        let filters = '';

        if (target || $.trim(x)[0] == "#") {
            filters = `<span class="text-bold ${params.class ? params.class : ''}">${params.caption ? params.caption : ((modules.tt.meta.filters[x] ? modules.tt.meta.filters[x] : i18n("tt.filter")).replaceAll("/", "<i class='fas fa-fw fa-xs fa-angle-double-right'></i>"))}</span>`;
        } else {
            let fcount = 0;

            let filtersTree = {};
            for (let i in project.filters) {
                let tree = (project.filters[i].filter ? modules.tt.meta.filters[project.filters[i].filter] : project.filters[i].filter).split("/");
                let f = filtersTree;
                for (let j = 0; j < tree.length - 1; j++) {
                    tree[j] = tree[j].trim();
                    if (!f[tree[j]]) {
                        f[tree[j]] = {};
                    }
                    f = f[tree[j]];
                }
                f[tree[tree.length - 1].trim()] = project.filters[i];
            }

            let filterName = modules.tt.meta.filters[x] ? modules.tt.meta.filters[x] : i18n("tt.filter");
            document.title = i18n("windowTitle") + " :: " + filterName;

            function hh(t) {
                let filters = '';

                let fMy = [];
                let fFolders = [];
                let fPersonal = [];
                let fGroups = [];
                let fOthers = [];
                let ts = [];

                for (let i in t) {
                    if ($.trim(i.split("/")[0]) == myself.realName) {
                        fMy.push(i);
                    } else {
                        if (!t[i].filter) {
                            fFolders.push(i);
                        } else {
                            if (parseInt(t[i].personal) > 1000000) {
                                fPersonal.push(i);
                            } else
                            if (parseInt(t[i].personal)) {
                                fGroups.push(i);
                            } else {
                                fOthers.push(i);
                            }
                        }
                    }
                }

                ts = ts.concat(fMy.sort());
                ts = ts.concat(fFolders.sort());
                ts = ts.concat(fPersonal.sort());
                ts = ts.concat(fGroups.sort());
                ts = ts.concat(fOthers.sort());

                let hasSub = fMy.length || fFolders.length;

                for (let sk in ts) {
                    let i = ts[sk];
                    if (t[i].filter) {
                        if (x == t[i].filter) {
                            filters += `<li class="dropdown-item${hasSub?' nomenu':''} pointer tt_issues_filter font-weight-bold mr-3" data-filter-name="${t[i].filter}">`;
                        } else {
                            filters += `<li class="dropdown-item${hasSub?' nomenu':''} pointer tt_issues_filter mr-3" data-filter-name="${t[i].filter}">`;
                        }
                        if (parseInt(t[i].personal) > 1000000) {
                            filters += '<i class="fas fa-fw fa-users mr-2"></i>';
                        } else
                        if (parseInt(t[i].personal)) {
                            filters += '<i class="fas fa-fw fa-user mr-2"></i>';
                        } else {
                            filters += '<i class="fas fa-fw fa-globe-americas mr-2"></i>';
                        }
                        filters += "<span>" + $.trim(i) + "&nbsp;</span>";
                        filters += "</li>";
                        fcount++;
                    } else {
                        filters += `<li class="dropdown-item pointer submenu mr-4"><i class="far fa-fw fa-folder mr-2"></i><span>${i}&nbsp;</span></li>`;
                        filters += '<ul class="dropdown-menu">';
                        filters += hh(t[i]);
                        filters += '</ul>';
                        filters += `</li>`;
                    }
                }

                return filters;
            }

            filterNames = filterName.split("/");

            for (let o in filterNames) {
                filters += `<span class="dropdown">`;
                if (o == 0) {
                    filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter-${o}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" style="margin-left: -4px;"><i class="far fa-fw fa-caret-square-down mr-1"></i>${filterNames[o].trim()}</span>`;
                } else {
                    filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter-${o}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false"><i class="far fa-fw fa-caret-square-down mr-1"></i>${filterNames[o].trim()}</span>`;
                }
                filters += `<ul class="dropdown-menu" aria-labelledby="ttFilter-${o}">`;

                if (modules.tt.meta.favoriteFilters.length) {
                    filters += `<li class="dropdown-item pointer submenu mr-4"><i class="far fa-fw fa-folder mr-2"></i><span>${i18n("tt.favoriteFilters")}&nbsp;</span></li>`;
                    filters += '<ul class="dropdown-menu">';
                    for (let ff in modules.tt.meta.favoriteFilters) {
                        if (x == modules.tt.meta.favoriteFilters[ff].filter) {
                            filters += `<li class="dropdown-item nomenu pointer tt_issues_filter font-weight-bold mr-3" data-filter-name="${modules.tt.meta.favoriteFilters[ff].filter}">`;
                        } else {
                            filters += `<li class="dropdown-item nomenu pointer tt_issues_filter mr-3" data-filter-name="${modules.tt.meta.favoriteFilters[ff].filter}">`;
                        }
                        filters += `<i class="fa-fw mr-2 ${modules.tt.meta.favoriteFilters[ff].icon} ${modules.tt.meta.favoriteFilters[ff].color}"></i>`;
                        filters += "<span>" + $.trim(modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[ff].filter].shortName ? modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[ff].filter].shortName : modules.tt.meta.filtersExt[modules.tt.meta.favoriteFilters[ff].filter].name) + "&nbsp;</span>";
                        filters += "</li>";
                    }
                    filters += '</ul>';
                    filters += `</li>`;
                }

                filters += hh(filtersTree);
                filters += "</ul></span>";

                if (o < filterNames.length - 1) {
                    filters += "<i class='fas fa-fw fa-xs fa-angle-double-right ml-2 mr-2'></i>";
                }

                filtersTree = filtersTree[filterNames[o].trim()];
            }

            let fp = -1;
            for (let i in project.filters) {
                if (project.filters[i].filter == x) {
                    fp = project.filters[i].personal;
                    break;
                }
            }

            if (x != "#search") {
                if (md5(md5($.trim(modules.tt.meta.filters[x])) + "-" + md5(lStore("_login"))) == x && fp == myself.uid) {
                    filters += '<span class="ml-4 hoverable customFilterEdit text-info" data-filter="' + x + '"><i class="far fa-fw fa-edit"></i></span><span class="ml-1 hoverable customFilterEdit text-info" data-filter="' + x + '">' + i18n("tt.customFilterEdit") + '</span>';
                    filters += '<span class="ml-2 hoverable customFilterDelete text-danger" data-filter="' + x + '"><i class="far fa-fw fa-trash-alt"></i></span><span class="ml-1 hoverable customFilterDelete text-danger" data-filter="' + x + '">' + i18n("tt.customFilterDelete") + '</span>';
                } else {
                    if (AVAIL("tt", "customFilter") && x) {
                        filters += '<span class="ml-4 hoverable customFilterEdit text-info" data-filter="' + x + '"><i class="far fa-fw fa-edit"></i></span><span class="ml-1 hoverable customFilterEdit text-info" data-filter="' + x + '">' + i18n("tt.customFilterEdit") + '</span>';
                    }
                }
            }

            if (!fcount) {
                filters = `<span class="text-bold text-warning">${i18n('tt.noFiltersAvailable')}</span>`;
            }
        }

        if (myself.uid && !target) {
            $("#leftTopDynamic").html(`<li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-success text-bold createIssue">${i18n("tt.createIssue")}</span></li>`);
        }

        $(".createIssue").off("click").on("click", () => {
            modules.tt.issue.createIssue($("#ttProjectSelect").val());
        });

        let skip = parseInt(params.skip?params.skip:0);
        let limit = parseInt(params.limit?params.limit:modules.tt.defaultIssuesPerPage);

        let _ = Math.random();

        let query = {
            "project": current_project,
            "filter": x ? x : '',
            "skip": skip,
            "limit": limit,
            "search": ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : '',
        };

        if (lStore("sortBy:" + x)) {
            query.sort = lStore("sortBy:" + x);
        }

        QUERY("tt", "issues", query, true).
        done(response => {
            if (response && response.issues && response.issues.all) {
                lStore("tt_issue_filter_list:" + x, response.issues.all);
            }

            if (response.issues.exception) {
                error(i18n("errors." + response.issues.exception), i18n("error"), 30);
            }

            let issues = response.issues;

            limit = parseInt(issues.limit);
            skip = parseInt(issues.skip);

            let page = Math.floor(skip / limit) + 1;

            function pager(issuesListId) {
                let h = '';

                let pages = Math.ceil(issues.count / limit);
                let delta = Math.floor(modules.tt.defaultPagerItemsCount / 2);

                let first, last;

                if (pages <= modules.tt.defaultPagerItemsCount) {
                    first = 1;
                    last = pages;
                } else {
                    if (page <= delta) {
                        first = 1;
                        last = modules.tt.defaultPagerItemsCount;
                    } else {
                        first = page - delta + 1;
                        last = first + modules.tt.defaultPagerItemsCount - 1;
                        if (last > pages) {
                            last = pages;
                            first = last - modules.tt.defaultPagerItemsCount + 1;
                        }
                    }
                }

                h += `<nav class="pager" data-target="${issuesListId}">`;
                h += '<ul class="pagination mb-0 ml-0" style="margin-right: -2px!important;">';

                if (first > 1) {
                    h += `<li class="page-item pointer tt_pager" data-page="1" data-target="${issuesListId}"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                } else {
                    h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                }

                for (let i = first; i <= last; i++) {
                    if (page == i) {
                        h += `<li class="page-item font-weight-bold disabled" data-page="${i}" data-target="${issuesListId}"><span class="page-link">${i}</span></li>`;
                    } else {
                        h += `<li class="page-item pointer tt_pager" data-page="${i}" data-target="${issuesListId}"><span class="page-link">${i}</span></li>`;
                    }
                }

                if (last < pages) {
                    h += `<li class="page-item pointer tt_pager" data-page="${pages}" data-target="${issuesListId}"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
                } else {
                    h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
                }

                h += '</ul>';
                h += '</nav>';

                return h;
            }

            let cs = '';

            if (!target && params.customSearch && params.customSearch !== true) {
                let height = 400;
                cs += '<div>';
                cs += `<div id='editorContainer' style='width: 100%; height: ${height}px;' data-fh="true">`;
                cs += `<pre class="ace-editor mt-2" id="filterEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
                cs += "</div>";
                cs += `<span style='position: absolute; right: 35px; top: 35px;'>`;
                cs += `<span id="filterRun" class="hoverable saveButton"><i class="fas fa-running pr-2"></i>${i18n("tt.filterRun")}</span>`;
                cs += `</span>`;
                cs += '</div>';
            }

            if (target) {
                if (!$("#" + issuesListId).length) {
                    target.append(`<table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;">${filters}<br/><span id='${issuesListId + '-count'}'></span></td><td>${pager(issuesListId)}</td></tr></table><div id="${issuesListId}"></div>`);
                } else {
                    $(`.pager[data-target="${issuesListId}"]`).html(pager(issuesListId));
                }
            } else {
                $("#mainForm").html(`${cs}<table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;">${cs ? '' : (filters + '<br/>')}<span id='${issuesListId + '-count'}'></span></td><td>${pager(issuesListId)}</td></tr></table><div id="${issuesListId}"></div>`);
            }

            $(".tt_issues_filter").off("click").on("click", function () {
                modules.tt.selectFilter($(this).attr("data-filter-name"));
            });

            $(`.tt_pager[data-target="${issuesListId}"]`).off("click").on("click", function () {
                params.skip = Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit);
                params.limit = limit ? limit : modules.tt.defaultIssuesPerPage;
                if (params.pager && typeof params.pager == "function") {
                    params.pager(params);
                } else {
                    if (target) {
                        // for workspaces
                        loadingStart();
                        modules.tt.renderIssues(params, target, issuesListId, callback);
                    } else {
                        modules.tt.selectFilter(params.filter ? params.filter : false, params.skip, params.limit, params.search);
                    }
                }
            });

            let pKeys = [];

            if (issues.projection) {
                pKeys = Object.keys(issues.projection);
            }

            if (pKeys && modules.tt.meta.filtersExt && x && modules.tt.meta.filtersExt[x] && modules.tt.meta.filtersExt[x].hide) {
                let t = [];
                for (let i in pKeys) {
                    if (modules.tt.meta.filtersExt[x].hide.indexOf(pKeys[i]) < 0) {
                        t.push(pKeys[i]);
                    }
                }
                pKeys = t;
            }

            let sortMenuItems = [
                {
                    text: "-",
                    hint: i18n("tt.sortBy"),
                },
            ];

            let sort = response.issues.sort;

            if (!sort) {
                sort = lStore("sortBy:" + x);
            }

            if (!sort) {
                sort = {};
            }

            let virtuals = {};

            for (let i in modules.tt.meta.customFields) {
                if (modules.tt.meta.customFields[i].type == 'virtual') {
                    virtuals["_cf_" + modules.tt.meta.customFields[i].field] = 1;
                }
            }

            for (let i = 0; i < pKeys.length; i++) {
                if (virtuals[pKeys[i]]) continue;

                if (sort[pKeys[i]]) {
                    sortMenuItems.push({
                        id: pKeys[i],
                        text: ((parseInt(sort[pKeys[i]]) == 1) ? '<i class="fas fa-fw fa-sort-alpha-down mr-2"></i>' : '<i class="fas fa-fw fa-sort-alpha-up-alt mr-2"></i>') + modules.tt.issueFieldTitle(pKeys[i]),
                        selected: true,
                    });
                } else {
                    sortMenuItems.push({
                        id: pKeys[i],
                        text: '<i class="fas fa-fw mr-2"></i>' + modules.tt.issueFieldTitle(pKeys[i]),
                    });
                }
            };

            let sortMenu = menu({
                button: "<i class='fas fa-fw fa-sort pointer'></i>",
                items: sortMenuItems,
                click: function (id) {
                    if (sort && sort[id]) {
                        if (sort[id] == 1) {
                            sort = {};
                            sort[id] = -1;
                        } else
                        if (sort[id] == -1) {
                            sort = null;
                        }
                    } else {
                        sort = {};
                        sort[id] = 1;
                    }
                    lStore("sortBy:" + x, sort);
                    if (target) {
                        // for workspaces
                        loadingStart();
                        params.skip = 0;
                        params.limit = limit ? limit : modules.tt.defaultIssuesPerPage;
                        modules.tt.renderIssues(params, target, issuesListId, callback);
                    } else {
                        modules.tt.selectFilter(params.filter ? params.filter : false, params.skip, params.limit, params.search);
                    }
                }
            });

            let columns = [];

            if (modules && modules.tt && modules.tt.meta && modules.tt.meta.filtersExt && modules.tt.meta.filtersExt[x] && modules.tt.meta.filtersExt[x].disableCustomSort) {
                columns.push({
                    title: i18n("tt.issueIndx"),
                    nowrap: true,
                });
            } else {
                columns.push({
                    title: sortMenu,
                    nowrap: true,
                });
            }

            for (let i = 0; i < pKeys.length; i++) {
                columns.push({
                    title: modules.tt.issueFieldTitle(pKeys[i]),
                    nowrap: true,
                    fullWidth: i == pKeys.length - 1,
                });
            };

            if (params.customSearch && params.customSearch !== true) {
                let editor = ace.edit("filterEditor");
                editor.setTheme("ace/theme/chrome");
                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: true,
                });
                editor.session.setMode("ace/mode/json");
                editor.clearSelection();
                editor.setFontSize(14);
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
                editor.setValue(JSON.stringify(template, null, "\t"));
                currentAceEditor = editor;
                currentAceEditorOriginalValue = currentAceEditor.getValue();
                editor.getSession().getUndoManager().reset();
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
                        $("#filterRun").click();
                    }),
                });
                editor.commands.addCommand({
                    name: 'run',
                    bindKey: {
                        win: "Ctrl-R",
                        mac: "Cmd-R"
                    },
                    exec: (() => {
                        $("#filterRun").click();
                    }),
                });
                $("#filterRun").off("click").on("click", () => {
                    let f = false;
                    try {
                        f = JSON.parse($.trim(editor.getValue()));
                    } catch (e) {
                        f = false;
                    }
                    if (f && $.trim(f.name) && f.fields) {
                        let t = $.trim($.trim(f.name).split("/")[0]);
                        if (t != myself.realName) {
                            f.name = myself.realName + " / " + $.trim(f.name);
                        }
                        let n = md5(md5($.trim(f.name)) + "-" + md5(lStore("_login")));
                        f.fileName = n;
                        loadingStart();
                        PUT("tt", "customFilter", n, {
                            "project": current_project,
                            "body": JSON.stringify(f, true, 4),
                        }).
                        done(() => {
                            message(i18n("tt.filterWasSaved"));
                            lStore("tt_issue_filter_" + current_project, n);
                            currentAceEditorOriginalValue = currentAceEditor.getValue();
                            window.location.href = '?#tt&filter=' + n + '&customSearch=yes&_=' + Math.random();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    } else {
                        error(i18n("errors.invalidFilter"), i18n("error"), 30);
                    }
                });
                if (params.filter && params.filter !== true && params.filter != "empty") {
                    if (modules.tt.meta.filtersExt[params.filter].owner) {
                        GET("tt", "customFilter", params.filter).
                        done(response => {
                            editor.setValue(response.body, -1);
                            currentAceEditorOriginalValue = currentAceEditor.getValue();
                            loadingDone();
                        });
                    } else {
                        GET("tt", "filter", params.filter).
                        done(response => {
                            editor.setValue(response.body, -1);
                            currentAceEditorOriginalValue = currentAceEditor.getValue();
                            loadingDone();
                        });
                    }
                }
            }

            $(".customFilterEdit").off("click").on("click", function () {
                window.location.href = '?#tt&filter=' + $(this).attr("data-filter") + '&customSearch=yes&_=' + Math.random();
            });

            $(".customFilterDelete").off("click").on("click", function () {
                let f = $(this).attr("data-filter");
                mConfirm(i18n("tt.filterDelete", modules.tt.meta.filters[f]), i18n("confirm"), i18n("delete"), () => {
                    loadingStart();
                    DELETE("tt", "customFilter", f, { "project": current_project }).
                    done(() => {
                        message(i18n("tt.filterWasDeleted"));
                        lStore("tt_issue_filter_" + current_project, null);
                        window.location.href = '?#tt&_=' + Math.random();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                });
            });

            let bookmarked = false;

            for (let i in modules.tt.meta.favoriteFilters) {
                if (modules.tt.meta.favoriteFilters[i].filter == x) {
                    bookmarked = true;
                    break;
                }
            }

            if (issues.issues && issues.issues.length) {
                $("#" + issuesListId + "-count").text("[" + x + "]: " + i18n("tt.showCounts", parseInt(issues.skip) + 1, parseInt(issues.skip) + issues.issues.length, issues.count)).addClass("small");
                cardTable({
                    target: "#" + issuesListId,
                    columns: columns,
                    dropDownHeader: (x && x[0] != '#') ? {
                        icon: (bookmarked ? "fas" : "far") + " text-primary fa-bookmark",
                        title: bookmarked ? i18n("tt.removeFavoriteFilter") : i18n("tt.addFavoriteFilter"),
                        click: () => {
                            if (!bookmarked) {
                                let icons = [];
                                for (let i in faIcons) {
                                    icons.push({
                                        icon: faIcons[i].title + " fa-fw",
                                        text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                                        value: faIcons[i].title,
                                    });
                                }
                                cardForm({
                                    title: i18n("tt.addFavoriteFilter"),
                                    footer: true,
                                    borderless: true,
                                    topApply: true,
                                    apply: i18n("add"),
                                    size: "lg",
                                    fields: [
                                        {
                                            id: "icon",
                                            title: i18n("tt.filterIcon"),
                                            type: "select2",
                                            options: icons,
                                            value: "far fa-bookmark",
                                        },
                                        {
                                            id: "color",
                                            title: i18n("tt.filterColor"),
                                            type: "select2",
                                            options: [
                                                {
                                                    text: "По умолчанию",
                                                    value: "",
                                                    class: "",
                                                },
                                                {
                                                    text: "Primary",
                                                    value: "text-primary",
                                                    class: "text-primary",
                                                },
                                                {
                                                    text: "Secondary",
                                                    value: "text-secondary",
                                                    class: "text-secondary",
                                                },
                                                {
                                                    text: "Success",
                                                    value: "text-success",
                                                    class: "text-success",
                                                },
                                                {
                                                    text: "Danger",
                                                    value: "text-danger",
                                                    class: "text-danger",
                                                },
                                                {
                                                    text: "Warning",
                                                    value: "text-warning",
                                                    class: "text-warning",
                                                },
                                                {
                                                    text: "Info",
                                                    value: "text-info",
                                                    class: "text-info",
                                                },
                                            ],
                                            value: ""
                                        },
                                        {
                                            id: "rightSide",
                                            title: i18n("tt.filterRightSide"),
                                            type: "noyes",
                                        },
                                    ],
                                    callback: r => {
                                        loadingStart();
                                        POST("tt", "favoriteFilter", x, {
                                            icon: r.icon,
                                            color: r.color,
                                            rightSide: r.rightSide,
                                        }).
                                        done(() => {
                                            window.location.reload();
                                        }).
                                        fail(FAIL).
                                        fail(loadingDone);
                                    },
                                    done: id => {
                                        $(`
                                            <button class="btn btn-primary back-to-top" role="button" aria-label="${i18n("tt.scrollToTop")}" title="${i18n("tt.scrollToTop")}" onclick="$('html').scrollTop(0);" disabled="disabled">
                                                <i class="fas fa-chevron-up"></i>
                                            </button>
                                        `).append(id);
                                    },
                                });
                            } else {
                                mConfirm(i18n("tt.removeFavoriteFilter") + "?", modules.tt.meta.filtersExt[x].shortName ? modules.tt.meta.filtersExt[x].shortName : modules.tt.meta.filtersExt[x].name, i18n("remove"), () => {
                                    loadingStart();
                                    DELETE("tt", "favoriteFilter", x).
                                    done(() => {
                                        window.location.reload();
                                    }).
                                    fail(FAIL).
                                    fail(loadingDone);
                                });
                            }
                        },
                    } : false,
                    rows: () => {
                        let rows = [];

                        for (let i = 0; i < issues.issues.length; i++) {

                            let cols = [ {
                                data: i + skip + 1,
                                nowrap: true,
                                click: navigateUrl("tt", {
                                    issue: issues.issues[i]["issueId"],
                                    filter: x ? x : "",
                                    search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                }),
                            } ];

                            for (let j = 0; j < pKeys.length; j++) {
                                cols.push({
                                    data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list", x),
                                    nowrap: true,
                                    click: navigateUrl("tt", {
                                        issue: issues.issues[i]["issueId"],
                                        filter: x ? x : "",
                                        search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                    }),
                                    fullWidth: j == pKeys.length - 1,
                                });
                            }

                            rows.push({
                                uid: utf8_to_b64(JSON.stringify({
                                    id: issues.issues[i]["issueId"],
                                    filter: x ? x : "",
                                    search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                })),
                                cols: cols,
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-external-link-alt text-primary",
                                            title: i18n("tt.openIssueNewWindow"),
                                            click: uid => {
                                                let i = JSON.parse(b64_to_utf8(uid));
                                                window.open(navigateUrl("tt", { issue: i.id }), '_blank');
                                            }
                                        },
                                    ],
                                },
                            });
                        }

                        return rows;
                    },
                });
            } else {
                if (x) {
                    $("#" + issuesListId + "-count").text("[" + x + "]: " + i18n("tt.noIssuesAvailable")).addClass("small");
                }  else {
                    $("#" + issuesListId + "-count").text(i18n("tt.noIssuesAvailable")).addClass("small");
                }
            }
            if (!params.customSearch || params.customSearch === true || !params.filter || params.filter === true || params.filter == "empty") {
                if (typeof callback === "undefined") {
                    loadingDone();
                } else {
                    callback();
                }
            }
        }).
        fail(FAIL).
        fail((response) => {
            if (typeof fail == "function") {
                fail(response);
            } else {
                if (target) {
                    let e = i18n("errors.unknown");
                    if (response && response.responseJSON && response.responseJSON.error) {
                        e = i18n("errors." + response.responseJSON.error);
                    }
                    e = `<span class="text-danger text-bold">${e} [${params.filter}]<span/>`;
                    if (target !== true) {
                        target.append(`<table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;">${e}</td></tr></table>`);
                    } else {
                        $("#" + issuesListId).html(e);
                    }
                    if (typeof callback === "undefined") {
                        loadingDone();
                    } else {
                        callback();
                    }
                } else {
                    lStore("tt_issue_filter_" + $("#ttProjectSelect").val(), null);
                    lStore("tt_issue_filter_" + lStore("tt_project"), null);
                    lStore("tt_project", null);
                    if (params["_"] != _) {
                        window.location.href = `?#tt&_=${_}`;
                    }
                }
            }
        });
    },

    route: function (params) {
        loadingStart();

        subTop();

        if ($("#altForm:visible").length > 0) {
            $("#mainForm").html("");
            $("#altForm").hide();
        }

        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (params["issue"]) {
                GET("tt", "issue", params["issue"], true).
                done(r => {
                    if (modules.groups) {
                        modules.users.loadUsers(() => {
                            modules.groups.loadGroups(() => {
                                modules.tt.issue.renderIssue(r.issue, params["filter"], params["search"]);
                            });
                        });
                    } else {
                        modules.users.loadUsers(() => {
                            modules.tt.issue.renderIssue(r.issue, params["filter"], params["search"]);
                        });
                    }
                }).
                fail(FAILPAGE);
            } else {
                if (modules.tt.menuItem) {
                    if (params  && params.filter && params.filter[0] == "#") {
                        $("#" + modules.tt.menuItem).children().first().attr("href", navigateUrl("tt"));
                    } else {
                        $("#" + modules.tt.menuItem).children().first().attr("href", refreshUrl());
                    }
                }

                document.title = i18n("windowTitle") + " :: " + i18n("tt.filters");

                if (parseInt(myself.uid)) {
                    if (modules.groups) {
                        modules.users.loadUsers(() => {
                            modules.groups.loadGroups(() => {
                                modules.tt.renderIssues(params);
                            });
                        });
                    } else {
                        modules.users.loadUsers(() => {
                            modules.tt.renderIssues(params);
                        });
                    }
                } else {
                    window.location.href = "?#tt.settings";
                }
            }
        }).
        fail(FAIL).
        fail(loadingDone);
    },
}).init();