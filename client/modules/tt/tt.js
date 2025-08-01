({
    meta: {},

    defaultIssuesPerPage: lStore("defaultIssuesPerPage") ? lStore("defaultIssuesPerPage") : 50,
    defaultPagerItemsCount: 10,
    menuItem: false,

    specialActions: [
        "saAddComment",
        "saAddFile",
        "saAddSingleFile",
        "saAddSingleFileQuiet",
        "saAddMultipleFilesQuiet",
        "saAssignToMe",
        "saWatch",
        "saDelete",
        "saSubIssue",
        "saCoordinate",
        "saLink",
    ],

    init: function () {
        // dirty hack, don't do that!
        modules.tt = this;
        //
        if (AVAIL("tt", "tt")) {
            if (parseInt(myself.uid) == 0) {
                leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "?#tt.settings", "tt");
                this.menuItem = false;
            } else {
                if (AVAIL("tt", "issue", "POST")) {
                    leftSideClick("far fa-fw fa-plus-square", i18n("tt.createIssue"), "tt", () => {
                        modules.tt.issue.createIssue($("#ttProjectSelect").val());
                    });
                }
                this.menuItem = leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "?#tt", "tt");
            }
            GET("tt", "tt", false, true).
            done(modules.tt.tt).
            done(() => {
                loadSubModules("tt", [
                    "issue",
                    "settings",
                    "workspaces",
                    "json",
                ], this);
            }).
            fail(FAIL);
        } else {
            loadSubModules("tt", [
                "issue",
                "settings",
                "workspaces",
                "json",
            ], this);
        }
    },

    allLoaded: function () {
        if (modules.tt.menuItem && parseInt(myself.uid) && AVAIL("tt", "favoriteFilter")) {
            let h = "";
            for (let i in modules.tt.meta.favoriteFilters) {
                if (parseInt(modules.tt.meta.favoriteFilters[i].leftSide)) {
                    let title = modules.tt.meta.filters[modules.tt.meta.favoriteFilters[i].filter].shortName ? modules.tt.meta.filters[modules.tt.meta.favoriteFilters[i].filter].shortName : modules.tt.meta.filters[modules.tt.meta.favoriteFilters[i].filter].name;
                    h += `
                        <li class="nav-item" title="${escapeHTML(title)}" style="margin-top: 3px;">
                            <a href="?#tt&project=${modules.tt.meta.favoriteFilters[i].project}&filter=${modules.tt.meta.favoriteFilters[i].filter}" class="nav-link" onclick="xblur(); lStore('ttIssueFilter:${modules.tt.meta.favoriteFilters[i].project}', '${modules.tt.meta.favoriteFilters[i].filter}'); return true;">
                                <i class="nav-icon fa-fw ${modules.tt.meta.favoriteFilters[i].icon} ${modules.tt.meta.favoriteFilters[i].color}"></i>
                                <p class="text-nowrap">${title}</p>
                            </a>
                        </li>
                    `;
                }
            }
            let i = $('#' + modules.tt.menuItem);
            let f = false;
            while (i.next().length) {
                i = i.next();
                if ($.trim(i.text()) == "") {
                    $(h).insertBefore(i);
                    f = true;
                    break;
                }
            }
            if (!f && i.length) {
                $(h).insertAfter(i);
            }
        }
    },

    moduleLoaded: function () {
        //
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

    issueFieldTitle: function (field, target) {
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

                case "commentType":
                    return i18n("tt.commentType");

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
                if (target == "list") {
                    return cf.fieldDisplayList ? cf.fieldDisplayList : cf.fieldDisplay;
                } else {
                    return cf.fieldDisplay;
                }
            } else {
                return fieldId;
            }
        }
    },

    issueField2FormFieldEditor: function (issue, field, projectId, filter, prefferredValue, title) {

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

                for (let j in filter) {
                    for (let i in options) {
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.issueId) ? issue.issueId : ""),
                        hidden: true,
                    };

                case "subject":
                    return {
                        id: "subject",
                        type: "text",
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "optionalComment":
                    return {
                        id: "comment",
                        type: "area",
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        options: select2Filter(statuses, filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.status) ? issue.status : -1),
                    };

                case "tags":
                    return {
                        id: "tags",
                        type: "select2",
                        tags: true,
                        multiple: true,
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(tags, filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.tags) ? Object.values(issue.tags) : []),
                    };

                case "assigned":
                    return {
                        id: "assigned",
                        type: "select2",
                        multiple: [3, 4, 5].indexOf(parseInt(project.assigned)) >= 0,
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(peoples(project, [0, 2, 3, 5].indexOf(parseInt(project.assigned)) >= 0, [0, 1, 3, 4].indexOf(parseInt(project.assigned)) >= 0), filter),
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.assigned) ? Object.values(issue.assigned) : []),
                    };

                case "watchers":
                    return {
                        id: "watchers",
                        type: "select2",
                        multiple: true,
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
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
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(workflowsByProject(project), filter),
                        value: issue.workflow,
                    };

                case "created":
                    return {
                        id: fieldId,
                        type: "datetime-local",
                        title: title ? title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        sec: true,
                        value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue.assigned) ? Object.values(issue.assigned) : []),
                    }

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

                            let ro = parseInt(cf.readonly);

                            let val;
                            let editor = cf.editor;

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
                                editor = "text";
                            }

                            return {
                                id: "_cf_" + fieldId,
                                type: editor,
                                float: parseInt(cf.float) ? Math.pow(10, -parseInt(cf.float)) : false,
                                title: title ? title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription ? cf.fieldDescription : false,
                                sec: true,
                                value: val,
                                readonly: ro,
                                validate: validate,
                            }

                        case "select":
                            let already = {};

                            if (cf.format.indexOf("suggestions") >= 0 || !cf.required) {
                                already[""] = 1;
                                options.push({
                                    id: "",
                                    text: "",
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
                                title: title ? title: modules.tt.issueFieldTitle(field),
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
                                title: title ? title: modules.tt.issueFieldTitle(field),
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
                                title: title ? title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: true,
                                tags: true,
                                createTags: true,
                                value: (typeof prefferredValue !== "undefined") ? prefferredValue : (ax ? ax : []),
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
                                title: title ? title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                value: (typeof prefferredValue !== "undefined") ? prefferredValue : ((issue && issue["_cf_" + fieldId]) ? issue["_cf_" + fieldId] : []),
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
                                title: title ? title: modules.tt.issueFieldTitle(field),
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
                    case "commentAuthor":
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
                        val = `<span><a class='hoverable text-bold' class='' href='?#tt&issue=${val}'>${val}</a></span>`;
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
                                val = parseInt(val) ? i18n("yes") : i18n("no");
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

                        if (cf.link && cf.link != "false") {
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

    selectFilter: function (filter, skip, limit, search) {
        if (filter) {
            if (filter[0] !== "#") {
                lStore("ttIssueFilter:" + $("#ttProjectSelect").val(), filter);
            }
        } else {
            filter = lStore("ttIssueFilter:" + $("#ttProjectSelect").val());
        }
        window.location.href = navigateUrl("tt", {
            filter: filter,
            skip: skip ? skip : 0,
            limit: limit ? limit : modules.tt.defaultIssuesPerPage,
            search: ($.trim(search) && typeof search === "string") ? $.trim(search) : "",
        });
    },

    selectProject: function (project) {
        lStore("ttProject", project);
        window.location.href = navigateUrl("tt", {
            project: project,
        });
    },

    renderIssues: function (params, target, issuesListId, callback, fail) {
/*
        Этот код реализует функцию renderIssues, которая отвечает за отображение списка задач (issues) в пользовательском
        интерфейсе системы трекинга задач. Функция принимает параметры фильтрации, целевой элемент для вывода, идентификатор
        списка задач, а также колбэки для успешного и неуспешного завершения.

        В начале функция определяет текущий проект, основываясь на параметрах или сохранённых данных пользователя. Затем
        формируется выпадающий список проектов, к которым у пользователя есть доступ, и, если это необходимо, добавляются
        элементы управления пользовательскими фильтрами (например, кнопки для создания, редактирования или удаления фильтров).

        Далее определяется имя фильтра, который будет применён к списку задач. Если фильтр не задан явно, используется сохранённый
        ранее фильтр для текущего проекта. После этого происходит асинхронный запрос к серверу для получения информации о
        фильтре и его структуре.

        В зависимости от полученных данных строится дерево фильтров (с помощью вспомогательной функции hh), которое отображается
        в виде вложенного выпадающего меню. Это меню позволяет пользователю выбирать фильтры, сгруппированные по различным
        категориям: личные, групповые, общие и т.д. Каждый фильтр сопровождается иконкой, отражающей его тип.

        После выбора фильтра формируется и отправляется запрос на сервер для получения списка задач, соответствующих
        выбранному фильтру и проекту. В ответе от сервера содержится информация о задачах, их количестве, а также о возможных
        действиях (например, массовые операции, экспорт в CSV и т.д.).

        Далее функция строит таблицу задач с возможностью сортировки по различным полям, пагинацией и дополнительными действиями
        через выпадающее меню. Для каждой задачи отображаются основные поля, а также предоставляются ссылки для перехода к
        подробному просмотру или внешним ресурсам (если такие ссылки определены в метаданных).

        В случае, если фильтр поддерживает пользовательский поиск, отображается редактор фильтра на основе Ace Editor,
        позволяющий пользователю создавать и сохранять сложные фильтры в формате JSON.

        В завершение функция обрабатывает различные сценарии ошибок, отображая соответствующие сообщения пользователю и
        сбрасывая некорректные фильтры или проекты при необходимости.

        Вспомогательная функция hh строит вложенное дерево фильтров для выпадающего меню, группируя их по категориям и добавляя
        соответствующие иконки и стили для каждого типа фильтра.
*/
        if (target === "undefined") {
            target = false;
        }

        if (issuesListId === "undefined" || !issuesListId) {
            issuesListId = md5(guid());
        }

        let currentProject;

        if (target) {
            currentProject = params.project;
        } else {
            currentProject = params.project ? params.project : lStore("ttProject");
        }

        let pn = {};

        for (let i in modules.tt.meta.projects) {
            pn[modules.tt.meta.projects[i].acronym] = modules.tt.meta.projects[i].project;
        }

        let pc = Object.keys(modules.tt.meta.myRoles).length;

        let rtd = '';

        if (pc) {
            if (pc == 1) {
                rtd += `<select id="ttProjectSelect" style="display: none;">`;
            } else {
                rtd += `<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="ttProjectSelect" class="form-control select-arrow right-top-select">`;
            }

            for (let j in modules.tt.meta.myRoles) {
                if (j == currentProject) {
                    rtd += `<option selected="selected" value="${j}">${pn[j]} [${j}]</option>`;
                } else {
                    rtd += `<option value="${j}">${pn[j]} [${j}]</option>`;
                }
            }

            if (pc == 1) {
                rtd += '</select>';
            } else {
                rtd += '</select></div></div></form>';
            }

            if (AVAIL("tt", "customFilter")) {
                rtd += `<li class="nav-item nav-item-back-hover"><a href="?#tt&filter=empty&customSearch=yes&_=${Math.random()}" class="nav-link pointer" role="button" title="${i18n("tt.customSearch")}"><i class="fab fa-lg fa-fw fa-searchengin"></i></a></li>`;
            }
        }

        if (AVAIL("tt", "project", "POST")) {
            rtd += `<li class="nav-item nav-item-back-hover"><a href="?#tt.settings" class="nav-link pointer" role="button" title="${i18n("tt.settings")}"><i class="fas fa-lg fa-fw fa-cog"></i></a></li>`;
        }

        if (!target) {
            $("#rightTopDynamic").html(rtd);
            currentProject = $("#ttProjectSelect").val();
            lStore("ttProject", currentProject);
        }

        $("#ttProjectSelect").off("change").on("change", () => {
            modules.tt.selectProject($("#ttProjectSelect").val());
        });

        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].acronym == currentProject) {
                project = modules.tt.meta.projects[i];
            }
        }

        let filterName = false;

        if (target) {
            try {
                filterName = params["filter"];
            } catch (_) {
                //
            }
        } else {
            try {
                filterName = params["filter"] ? params["filter"] : lStore("ttIssueFilter:" + currentProject);
            } catch (_) {
                //
            }
        }

        GET("tt", "filter", filterName, true).
        done(r => {
            let realFilter;

            try {
                realFilter = JSON.parse(r.body);
            } catch (_) {
                realFilter = {};
            }

            let filters = '';
            let fp = -1;

            if (target || $.trim(filterName)[0] == "#") {
                filters = `<span class="text-bold ${params.class ? params.class : ''}">${params.caption ? params.caption : ((modules.tt.meta.filters[filterName] ? modules.tt.meta.filters[filterName].name : i18n("tt.filter")).replaceAll("/", "<i class='fas fa-fw fa-xs fa-angle-double-right'></i>"))}</span>`;
            } else {
                let fcount = 0;

                let filtersTree = {};
                for (let i in project.filters) {
                    let tree = (project.filters[i].filter ? modules.tt.meta.filters[project.filters[i].filter].name : project.filters[i].filter).split("/");
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

                let realFilterName = modules.tt.meta.filters[filterName] ? modules.tt.meta.filters[filterName].name : i18n("tt.filter");
                document.title = i18n("windowTitle") + " :: " + realFilterName;

                let alreadyFavorite = false;

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
                            if (filterName == t[i].filter) {
                                filters += `<li class="dropdown-item${hasSub ? ' nomenu' : ''} pointer tt_issues_filter font-weight-bold mr-3" data-filter-name="${t[i].filter}">`;
                            } else {
                                filters += `<li class="dropdown-item${hasSub ? ' nomenu' : ''} pointer tt_issues_filter mr-3" data-filter-name="${t[i].filter}">`;
                            }
                            if (parseInt(t[i].personal) > 1000000) {
                                filters += '<i class="fas fa-fw fa-users mr-2"></i>';
                            } else
                            if (parseInt(t[i].personal)) {
                                if (t[i].filter, modules.tt.meta.filters[t[i].filter].owner == myself.login) {
                                    filters += '<i class="fas fa-fw fa-user-graduate mr-2"></i>';
                            } else {
                                    filters += '<i class="fas fa-fw fa-user mr-2"></i>';
                                }
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

                let filterNames = realFilterName.split("/");

                for (let o in filterNames) {
                    filters += `<span class="dropdown">`;
                    if (o == 0) {
                        filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter-${o}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-flip="false" style="margin-left: -4px;"><i class="far fa-fw fa-caret-square-down mr-1"></i>${filterNames[o].trim()}</span>`;
                    } else {
                        filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter-${o}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false"  data-flip="false"><i class="far fa-fw fa-caret-square-down mr-1"></i>${filterNames[o].trim()}</span>`;
                    }
                    filters += `<ul class="dropdown-menu" aria-labelledby="ttFilter-${o}">`;

                    if (modules.tt.meta.favoriteFilters.length && !alreadyFavorite) {
                        alreadyFavorite = true;
                        filters += `<li class="dropdown-item pointer submenu mr-4"><i class="far fa-fw fa-bookmark mr-2"></i><span>${i18n("tt.favoriteFilters")}&nbsp;</span></li>`;
                        filters += '<ul class="dropdown-menu">';
                        for (let ff in modules.tt.meta.favoriteFilters) {
                            if (filterName == modules.tt.meta.favoriteFilters[ff].filter) {
                                filters += `<li class="dropdown-item nomenu pointer tt_issues_filter font-weight-bold mr-3" data-filter-name="${modules.tt.meta.favoriteFilters[ff].filter}">`;
                            } else {
                                filters += `<li class="dropdown-item nomenu pointer tt_issues_filter mr-3" data-filter-name="${modules.tt.meta.favoriteFilters[ff].filter}">`;
                            }
                            filters += `<i class="fa-fw mr-2 ${modules.tt.meta.favoriteFilters[ff].icon} ${modules.tt.meta.favoriteFilters[ff].color}"></i>`;
                            filters += "<span>" + $.trim(modules.tt.meta.filters[modules.tt.meta.favoriteFilters[ff].filter].shortName ? modules.tt.meta.filters[modules.tt.meta.favoriteFilters[ff].filter].shortName : modules.tt.meta.filters[modules.tt.meta.favoriteFilters[ff].filter].name) + "&nbsp;</span>";
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

                    if (filtersTree[filterNames[o].trim()]) {
                        filtersTree = filtersTree[filterNames[o].trim()];
                    }
                }

                for (let i in project.filters) {
                    if (project.filters[i].filter == filterName) {
                        fp = project.filters[i].personal;
                        break;
                    }
                }

                if (!fcount) {
                    filters = `<span class="text-bold text-warning">${i18n('tt.noFiltersAvailable')}</span>`;
                }
            }

            let skip = parseInt(params.skip ? params.skip : 0);
            let limit = parseInt(params.limit ? params.limit : modules.tt.defaultIssuesPerPage);

            let _ = Math.random();

            let query = {
                "project": currentProject,
                "filter": filterName ? filterName : '',
                "skip": skip,
                "limit": limit,
                "search": ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : '',
            };

            if (lStore("sortBy:" + filterName)) {
                query.sort = lStore("sortBy:" + filterName);
                if (query.sort._id) {
                    delete query.sort._id;
                }
            }

            let t = lStore(`_filter_form_${filterName}`);
            if (t) {
                let preprocess = {};
                let types = {};
                for (let i in t) {
                    if (i != "types") {
                        preprocess['%%' + i] = t[i];
                    } else {
                        for (let j in t[i]) {
                            types['%%' + j] = t[i][j];
                        }
                    }
                }
                query.preprocess = preprocess;
                query.types = types;
            }

            QUERY("tt", "issues", query, true).
            done(response => {
                if (response && response.issues && response.issues.all) {
                    lStore("ttIssueFilterList:" + filterName, response.issues.all);
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
                    cs += `<pre class="ace-editor mt-2" id="filterEditor"></pre>`;
                    cs += "</div>";
                    cs += `<span style='position: absolute; right: 35px; top: 35px;'>`;
                    cs += `<span id="filterRun" class="hoverable saveButton"><i class="far fa-play-circle pr-2"></i>${i18n("tt.filterRun")}</span>`;
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
                    let o = cs;
                    if (cs) {
                        o += `<table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;"><span id='${issuesListId + '-count'}'></span></td></tr></table>`;
                    } else {
                        o += `<table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;">${filters}<br/><span id='${issuesListId + '-count'}'></span></td><td>${pager(issuesListId)}</td></tr></table>`;
                    }
                    o += `<div id="${issuesListId}"></div>`;
                    if (!cs) {
                        o += `<table class="mb-2 issuesBottomPager" style="width: 100%; display: none;"><tr><td style="width: 100%;">&nbsp;</td><td>${pager(issuesListId)}</td></tr></table>`;
                    }
                    $("#mainForm").html(o);
                }

                $(".tt_issues_filter").off("click").on("click", function () {
                    let filter = $(this).attr("data-filter-name");
                    loadingStart();
                    GET("tt", "filter", filter, true).
                    done(f => {
                        try {
                            f = JSON.parse(f.body);
                        } catch (_) {
                            f = false;
                        }
                        try {
                            if (f && f.form && f.form.title && f.form.fields && f.form.fields.length > 0) {
                                let values = lStore(`_filter_form_${filter}`);
                                let fields = [];
                                let project;

                                for (let i in modules.tt.meta.projects) {
                                    if (modules.tt.meta.projects[i].acronym == lStore("ttProject")) {
                                        project = modules.tt.meta.projects[i];
                                        break;
                                    }
                                }

                                let types = {};

                                for (let i in f.form.fields) {
                                    let t = modules.tt.issueField2FormFieldEditor(null, f.form.fields[i].field, project.projectId, false, (values && values[`_form_${f.form.fields[i].id}`]) ? values[`_form_${f.form.fields[i].id}`] : "", f.form.fields[i].title);
                                    t.id = `_form_${f.form.fields[i].id}`;
                                    if (!f.form.fields[i].keepRO) {
                                        t.readonly = false;
                                    }
                                    if (f.form.fields[i].editor) {
                                        t.type = f.form.fields[i].editor;
                                    }
                                    fields.push(t);
                                    if (f.form.fields[i].cast) {
                                        types[t.id] = f.form.fields[i].cast;
                                    }
                                }

                                loadingDone();

                                cardForm({
                                    title: f.form.title,
                                    apply: f.form.apply,
                                    fields: fields,
                                    topApply: f.form.topApply,
                                    footer: f.form.footer,
                                    borderless: f.form.borderless,
                                    size: "lg",
                                    callback: r => {
                                        r.types = types;
                                        lStore(`_filter_form_${filter}`, r);
                                        modules.tt.selectFilter(filter);
                                    },
                                })
                            } else {
                                modules.tt.selectFilter(filter);
                            }
                        } catch (e) {
                            loadingDone();
                            console.error(e);
                            FAIL();
                        }
                    }).
                    fail(FAIL).
                    fail(loadingDone);
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

                if (pKeys && modules.tt.meta.filtersExt && filterName && modules.tt.meta.filters[filterName] && modules.tt.meta.filters[filterName].hide) {
                    let t = [];
                    for (let i in pKeys) {
                        if (modules.tt.meta.filters[filterName].hide.indexOf(pKeys[i]) < 0) {
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

                if (!sort || Object.keys(sort) === 0 || typeof sort.length != "undefined") {
                    sort = lStore("sortBy:" + filterName);
                }

                if (!sort || Object.keys(sort) === 0 || typeof sort.length != "undefined") {
                    sort = {};
                }

                if (sort._id) {
                    delete sort._id;
                }

                let virtuals = {};
                let links = {}

                for (let i in modules.tt.meta.customFields) {
                    if (modules.tt.meta.customFields[i].type == 'virtual') {
                        virtuals["_cf_" + modules.tt.meta.customFields[i].field] = 1;
                    }
                    if (modules.tt.meta.customFields[i].link) {
                        links["_cf_" + modules.tt.meta.customFields[i].field] = modules.tt.meta.customFields[i].link;
                    }
                }

                let sorted = false;

                for (let i = 0; i < pKeys.length; i++) {
                    if (virtuals[pKeys[i]]) continue;

                    if (sort[pKeys[i]]) {
                        sorted = true;
                        sortMenuItems.push({
                            id: pKeys[i],
                            icon: (parseInt(sort[pKeys[i]]) == 1) ? 'fas fa-sort-alpha-down' : 'fas fa-fw fa-sort-alpha-up-alt',
                            text: modules.tt.issueFieldTitle(pKeys[i]) + ' [' + (Object.keys(sort).indexOf(pKeys[i]) + 1) + ']',
                            selected: true,
                        });
                    } else {
                        sortMenuItems.push({
                            id: pKeys[i],
                            icon: 'fas',
                            text: modules.tt.issueFieldTitle(pKeys[i]),
                        });
                    }
                };

                if (!sorted) {
                    sort = {};
                    lStore("sortBy:" + filterName, sort);
                }

                sortMenuItems.push({
                    text: "-",
                });

                sortMenuItems.push({
                    id: "noSort",
                    text: i18n("tt.noSort"),
                    selected: !sorted,
                    icon: !sorted ? 'fas fa-check' : '',
                });

                let sortMenu = menu({
                    icon: 'fas fa-sort',
                    items: sortMenuItems,
                    click: id => {
                        if (id == "noSort") {
                            sort = {};
                        } else {
                            if (!sort || Object.keys(sort) === 0 || typeof sort.length != "undefined") {
                                sort = {};
                            }
                            if (sort[id]) {
                                if (sort[id] == 1) {
                                    sort[id] = -1;
                                } else
                                if (sort[id] == -1) {
                                    delete sort[id];
                                }
                            } else {
                                sort[id] = 1;
                            }
                        }
                        lStore("sortBy:" + filterName, sort);
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

                if ((modules && modules.tt && modules.tt.meta && modules.tt.meta.filtersExt && modules.tt.meta.filters[filterName] && modules.tt.meta.filters[filterName].disableCustomSort) || (params.customSearch && params.customSearch !== true)) {
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
                        title: modules.tt.issueFieldTitle(pKeys[i], "list"),
                        nowrap: true,
                        fullWidth: i == pKeys.length - 1,
                    });
                };

                if (params.customSearch && params.customSearch !== true) {
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
                    editor.clearSelection();
                    editor.focus();
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
                        let err;
                        try {
                            f = JSON.parse($.trim(editor.getValue()));
                        } catch (e) {
                            err = e.message;
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
                                "project": currentProject,
                                "body": JSON.stringify(f, true, 4),
                            }).
                            done(() => {
                                GET("tt", "tt", false, true).
                                done(modules.tt.tt).
                                done(() => {
                                    message(i18n("tt.filterWasSaved"));
                                    lStore("ttIssueFilter:" + currentProject, n);
                                    window.onbeforeunload = null;
                                    window.location.href = '?#tt&filter=' + n + '&customSearch=yes&owner=' + myself.login + '&_=' + Math.random();
                                }).
                                fail(FAIL).
                                fail(loadingDone);
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
                    if (params.filter && params.filter !== true && params.filter != "empty") {
                        if (params.owner || (modules.tt.meta.filters[params.filter] && modules.tt.meta.filters[params.filter].owner)) {
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

                let bookmarked = false;

                for (let i in modules.tt.meta.favoriteFilters) {
                    if (modules.tt.meta.favoriteFilters[i].filter == filterName) {
                        bookmarked = true;
                        break;
                    }
                }

                let currentIssuesList = [];

                if (issues.issues) {
                    $("#" + issuesListId + "-count").text("[" + filterName + "]: " + i18n("tt.showCounts", parseInt(issues.skip) ? (parseInt(issues.skip) + 1) : '0', parseInt(issues.skip) + issues.issues.length, issues.count)).addClass("small");

                    let menuItems = [];

                    if (filterName && filterName[0] != '#') {
                        menuItems.push({
                            id: "favorite",
                            icon: (bookmarked ? "fas" : "far") + " text-primary fa-bookmark",
                            text: bookmarked ? i18n("tt.removeFavoriteFilter") : i18n("tt.addFavoriteFilter"),
                            data: filterName,
                        });
                    }

                    if (pc) {
                        if (AVAIL("tt", "customFilter")) {
                            if (filterName && filterName != "#search") {
                                menuItems.push({
                                    id: "customFilterEdit",
                                    icon: "fas fa-pen",
                                    text: i18n("tt.customFilterEdit"),
                                });

                                if (modules.tt.meta.filters[filterName] && md5(md5($.trim(modules.tt.meta.filters[filterName].name)) + "-" + md5(lStore("_login"))) == filterName && fp == myself.uid) {
                                    menuItems.push({
                                        id: "customFilterDelete",
                                        icon: "fas fa-trash-alt text-danger",
                                        text: i18n("tt.customFilterDelete"),
                                    });
                                }
                            }
                        }
                    }

                    menuItems.push({
                        text: "-",
                        hint: i18n("tt.export"),
                    });

                    menuItems.push({
                        icon: "fas fa-file-csv",
                        id: "export",
                        text: i18n("tt.exportToCSV"),
                    });

                    if (!target && !(params.customSearch && params.customSearch !== true)) {
                        menuItems.push({
                            text: "-",
                            hint: i18n("tt.issuesPerPage"),
                        });

                        let ipp = [ 10, 25, 50, 100 ];

                        for (let i in ipp) {
                            menuItems.push({
                                id: "items-" + ipp[i],
                                text: i18n("tt." + ipp[i] + "Items"),
                                selected: limit == ipp[i],
                                icon: (limit == ipp[i]) ? 'fas fa-check' : '',
                                data: ipp[i],
                            });
                        }
                    }

                    if (realFilter && realFilter.bulkWorkflow && realFilter.bulkActions && realFilter.bulkActions.length) {
                        menuItems.push({
                            text: "-",
                            hint: i18n("tt.bulkActions"),
                        });

                        for (let i in realFilter.bulkActions) {
                            menuItems.push({
                                id: "action-" + md5(guid()),
                                text: realFilter.bulkActions[i],
                                data: realFilter.bulkActions[i],
                            });
                        }
                    }

                    cardTable({
                        target: "#" + issuesListId,
                        id: "table-" + issuesListId,
                        columns: columns,
                        dropDownHeader: {
                            menu: menu({
                                icon: "fas fa-bars",
                                right: true,
                                items: menuItems,
                                click: (id, data) => {
                                    if (id == "favorite") {
                                        if (!bookmarked) {
                                            let icons = [];
                                            for (let i in faIcons) {
                                                icons.push({
                                                    icon: faIcons[i].title,
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
                                                        id: "leftSide",
                                                        title: i18n("tt.filterLeftSide"),
                                                        type: "noyes",
                                                    },
                                                    {
                                                        id: "color",
                                                        title: i18n("tt.filterColor"),
                                                        type: "themeColor",
                                                        value: ""
                                                    },
                                                ],
                                                callback: r => {
                                                    loadingStart();
                                                    POST("tt", "favoriteFilter", filterName, {
                                                        project: lStore("ttProject"),
                                                        icon: r.icon,
                                                        color: r.color ? ("text-" + r.color) : "",
                                                        leftSide: r.leftSide,
                                                    }).
                                                    done(() => {
                                                        window.location.reload();
                                                    }).
                                                    fail(FAIL).
                                                    fail(loadingDone);
                                                },
                                            });
                                        } else {
                                            mConfirm(i18n("tt.removeFavoriteFilter") + "?", modules.tt.meta.filters[filterName].shortName ? modules.tt.meta.filters[filterName].shortName : modules.tt.meta.filters[filterName].name, i18n("remove"), () => {
                                                loadingStart();
                                                DELETE("tt", "favoriteFilter", filterName).
                                                done(() => {
                                                    window.location.reload();
                                                }).
                                                fail(FAIL).
                                                fail(loadingDone);
                                            });
                                        }
                                    }
                                    if (id == "export") {
                                        modules.tt.exportCSV("table-" + issuesListId, filterName);
                                    }
                                    if (id.substring(0, 7) == "action-") {
                                        modules.tt.bulkAction(currentProject, filterName, realFilter.bulkWorkflow, currentIssuesList, data);
                                    }
                                    if (id.substring(0, 6) == "items-") {
                                        modules.tt.defaultIssuesPerPage = parseInt(data);
                                        lStore("defaultIssuesPerPage", parseInt(data));
                                        window.location.href = '?#tt&_=' + Math.random();
                                    }
                                    if (id == "customFilterEdit") {
                                        window.location.href = '?#tt&filter=' + filterName + '&customSearch=yes&_=' + Math.random();
                                    }
                                    if (id == "customFilterDelete") {
                                        mConfirm(i18n("tt.filterDelete", modules.tt.meta.filters[filterName].name), i18n("confirm"), i18n("delete"), () => {
                                            loadingStart();
                                            DELETE("tt", "customFilter", filterName, { "project": currentProject }).
                                            done(() => {
                                                message(i18n("tt.filterWasDeleted"));
                                                lStore("ttIssueFilter:" + currentProject, null);
                                                window.location.href = '?#tt&_=' + Math.random();
                                            }).
                                            fail(FAIL).
                                            fail(loadingDone);
                                        });
                                    }
                                }
                            }),
                        },
                        rows: () => {
                            let rows = [];

                            for (let i = 0; i < issues.issues.length; i++) {
                                currentIssuesList.push(issues.issues[i]["issueId"]);

                                let cols = [
                                    {
                                        data: i + skip + 1,
                                        nowrap: true,
                                        click: navigateUrl("tt", {
                                            issue: issues.issues[i]["issueId"],
                                            filter: filterName ? filterName : "",
                                            search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                        }),
                                    }
                                ];

                                for (let j = 0; j < pKeys.length; j++) {
                                    if (links[pKeys[j]]) {
                                        if (links[pKeys[j]] == "false") {
                                            cols.push({
                                                data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list", filterName),
                                                nowrap: true,
                                                fullWidth: j == pKeys.length - 1,
                                                ellipses: j == pKeys.length - 1,
                                            });
                                        } else {
                                            let l = links[pKeys[j]];
                                            if (issues.issues[i][pKeys[j]]) {
                                                cols.push({
                                                    data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list", filterName),
                                                    nowrap: true,
                                                    click: () => {
                                                        window.open(l.replaceAll('%value%', issues.issues[i][pKeys[j]]));
                                                    },
                                                    fullWidth: j == pKeys.length - 1,
                                                    ellipses: j == pKeys.length - 1,
                                                });
                                            } else {
                                                cols.push({
                                                    data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list", filterName),
                                                    nowrap: true,
                                                    fullWidth: j == pKeys.length - 1,
                                                    ellipses: j == pKeys.length - 1,
                                                });
                                            }
                                        }
                                    } else {
                                        cols.push({
                                            data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list", filterName),
                                            nowrap: true,
                                            click: navigateUrl("tt", {
                                                issue: issues.issues[i]["issueId"],
                                                filter: filterName ? filterName : "",
                                                search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                            }),
                                            fullWidth: j == pKeys.length - 1,
                                            ellipses: j == pKeys.length - 1,
                                        });
                                    }
                                }

                                rows.push({
                                    uid: utf8_to_b64(JSON.stringify({
                                        id: issues.issues[i]["issueId"],
                                        filter: filterName ? filterName : "",
                                        search: ($.trim(params.search) && params.search !== true) ? $.trim(params.search) : "",
                                    })),
                                    cols: cols,
                                });
                            }

                            return rows;
                        },
                        append: (!issues.issues || !issues.issues.length) ? i18n("tt.noIssuesAvailable") : '',
                    });
                    if ($("#" + issuesListId).height() > $(window).height()) {
                        $(".issuesBottomPager").show();
                    }
                    if (!target) {
                        $('html').scrollTop(0);
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
            fail(response => {
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
                        if (response.responseJSON.error != "tokenNotFound") {
                            lStore("ttIssueFilter:" + $("#ttProjectSelect").val(), null);
                            lStore("ttIssueFilter:" + lStore("ttProject"), null);
                            lStore("ttProject", null);
                            if (params["_"] != _) {
                                window.location.href = `?#tt&_=${_}`;
                            }
                        }
                    }
                }
            });
        }).
        fail(FAIL).
        fail(response => {
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
                    if (response.responseJSON.error != "tokenNotFound") {
                        lStore("ttIssueFilter:" + $("#ttProjectSelect").val(), null);
                        lStore("ttIssueFilter:" + lStore("ttProject"), null);
                        lStore("ttProject", null);
                        if (params["_"] != _) {
                            window.location.href = `?#tt&_=${_}`;
                        }
                    }
                }
            }
        });
    },

    bulkAction: function (project, filterName, workflow, currentIssuesList, action) {
        loadingStart();
        QUERYID("tt", "bulkAction", filterName, {
            workflow,
            action
        }, true).
        done(r => {
            if (r && r.template && r.template == "!") {
                loadingDone();
                mConfirm(modules.tt.displayAction(action) + "?", i18n("tt.bulkActionConfirm"), modules.tt.displayAction(action), () => {
                    loadingStart();
                    PUT("tt", "bulkAction", false, {
                        project,
                        query: {
                            "issueId": {
                                "$in": currentIssuesList,
                            }
                        },
                        set: {},
                        action,
                    }).
                    done(r => {
                        message(i18n("tt.bulkActionDone", r));
                    }).
                    fail(FAIL).
                    always(loadingDone);
                });
            }

            if (r && r.template && r.template != "!") {
                let fields = [];

                let kx = [];
                let ky = {};

                for (let i in r.template) {
                    let fx = ((typeof r.template[i] == "string") ? r.template[i] : i).toString();
                    if (fx.charAt(0) == '%') {
                        fx = fx.split('%');
                        kx[fx[1]] = fx[2];
                        ky[fx[2]] = (typeof r.template[i] == "string") ? false : r.template[i];
                    } else {
                        kx.push(fx);
                        ky[fx] = (typeof r.template[i] == "string") ? false : r.template[i];
                    }
                }

                let projectId = -1;
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].acronym == project) {
                        projectId = modules.tt.meta.projects[i].projectId;
                        break;
                    }
                }

                for (let i in kx) {
                    let fi = modules.tt.issueField2FormFieldEditor(null, kx[i], projectId, ky[kx[i]]);
                    if (fi) {
                        fields.push(fi);
                        if (kx[i] == "comment" || kx[i] == "optionalComment") {
                            fields.push({
                                id: "commentPrivate",
                                type: "yesno",
                                title: i18n("tt.commentPrivate"),
                                value: "1",
                            });
                        }
                    }
                }

                loadingDone();

                cardForm({
                    title: i18n("tt.bulkActionConfirm"),
                    apply: modules.tt.displayAction(action),
                    fields: fields,
                    footer: true,
                    borderless: true,
                    size: "lg",
                    callback: r => {
                        loadingStart();
                        PUT("tt", "bulkAction", false, {
                            project,
                            query: {
                                "issueId": {
                                    "$in": currentIssuesList,
                                }
                            },
                            set: r,
                            action,
                        }).
                        done(r => {
                            message(i18n("tt.bulkActionDone", r));
                            window.location.href = '?#tt&_=' + Math.random();
                        }).
                        fail(FAIL).
                        always(loadingDone);
                    },
                });
            }
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    exportCSV: function (issuesTableId, filterName) {
        let tableToCSV = new TableToCSV("#" + issuesTableId, {
            filename: filterName + ".csv",
            delimiter: ",",
            ignoreColumns: [
                0,
                $("#" + issuesTableId + " > thead > tr > th").length - 1,
            ],
        });
        tableToCSV.download();
    },

    route: function (params) {
        if (params.project) {
            lStore("ttProject", params.project);
        }

        loadingStart();

        subTop();

        if ($("#altForm:visible").length > 0) {
            $("#mainForm").html("");
            $("#altForm").hide();
        }

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
                    $("#" + modules.tt.menuItem).children().first().attr("href", navigateUrl("tt", false, {
                        exclude: [
                            "customSearch"
                        ]
                    }));
                } else {
                    $("#" + modules.tt.menuItem).children().first().attr("href", refreshUrl({
                        exclude: [
                            "customSearch"
                        ]
                    }));
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
    },

    search: function (s) {
        s = $.trim(s);
        if (s) {
            let i = new RegExp("^[a-zA-Z]{2,}-[0-9]{1,}$");
            if (i.test(s)) {
                window.location.href = "?#tt&issue=" + s.toUpperCase() + "&search=" + s.toUpperCase() + "&_=" + Math.random();
            } else {
                modules.tt.selectFilter("#search", 0, modules.tt.defaultIssuesPerPage, s);
            }
        }
    },
}).init();