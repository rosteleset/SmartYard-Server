({
    meta: {},

    defaultIssuesPerPage: 50,
    defaultPagerItemsCount: 10,
    menuItem: false,

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
                                text: modules.groups.meta[j].name?modules.groups.meta[j].name:modules.groups.meta[j].acronym,
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
                                    text: modules.users.meta[j].realName?modules.users.meta[j].realName:modules.users.meta[j].login,
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
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.issueId)?issue.issueId:""),
                        hidden: true,
                    };

                case "subject":
                    return {
                        id: "subject",
                        type: "text",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.subject)?issue.subject:""),
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
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.description)?issue.description:""),
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
                    value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.resolution)?issue.resolution:-1),
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
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.status)?issue.status:-1),
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
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.tags)?Object.values(issue.tags):[]),
                    };

                case "assigned":
                    return {
                        id: "assigned",
                        type: "select2",
                        multiple: [3, 4, 5].indexOf(parseInt(project.assigned)) >= 0,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(peoples(project, [0, 2, 3, 5].indexOf(parseInt(project.assigned)) >= 0, [0, 1, 3, 4].indexOf(parseInt(project.assigned)) >= 0), filter),
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.assigned)?Object.values(issue.assigned):[]),
                    };

                case "watchers":
                    return {
                        id: "watchers",
                        type: "select2",
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: select2Filter(peoples(project, false, true), filter),
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue.watchers)?Object.values(issue.watchers):[]),
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
                        value: (typeof prefferredValue !== "undefined")?prefferredValue:vi,
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
                        workflows[i] = modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i;
                    }
        
                    function workflowsByProject(project) {
                        let w;
        
                        if (project) {
                            for (let i in modules.tt.meta.projects) {
                                if (modules.tt.meta.projects[i].acronym == project) {
                                    for (let j in modules.tt.meta.projects[i].workflows) {
                                        let wn = $.trim(workflows[modules.tt.meta.projects[i].workflows[j]]?workflows[modules.tt.meta.projects[i].workflows[j]]:modules.tt.meta.projects[i].workflows[j]);
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

                if (cf) {
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
                            let val = (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:"");

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
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                sec: true,
                                value: val,
                                readonly: ro,
                                validate: validate,
                            }

                        case "select":
                            for (let i in cf.options) {
                                options.push({
                                    id: cf.options[i].option,
                                    text: cf.options[i].optionDisplay,
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
                                value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[]),
                                validate: validate,
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
                            return {
                                id: "_cf_" + fieldId,
                                type: "select2",
                                title: modules.tt.issueFieldTitle(field),
                                placeholder: modules.tt.issueFieldTitle(field),
                                hint: cf.fieldDescription?cf.fieldDescription:false,
                                options: select2Filter(options, filter),
                                multiple: cf.format.indexOf("multiple") >= 0,
                                value: (typeof prefferredValue !== "undefined")?prefferredValue:((issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[]),
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
                            })
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

    issueField2Html: function (issue, field, val, target) {
        let members = {};

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
            val = modules.tt.viewers[field][v](val, issue, field, target);
        } else {
            if (val == null || val == "&nbsp;") {
                return "";
            }
    
            if (field.substring(0, 4) !== "_cf_") {
                switch (field) {
                    case "description":
                    case "subject":
                    case "commentBody":
                        val = nl2br(escapeHTML(val));
                        break;
    
                    case "assigned":
                    case "watchers":
                        let m = "";
    
                        for (let i in val) {
                            m += members[val[i]]?members[val[i]]:val[i];
                            m += ", ";
                        }
    
                        if (m) {
                            m = m.substring(0, m.length - 2);
                        }
    
                        val = escapeHTML(m);
                        break;
    
                    case "author":
                        val = escapeHTML(members[val]?members[val]:val);
                        break;
    
                    case "commentPrivate":
                        val = val ? i18n("yes") : i18n("no");
                        break;
        
                    case "status":
                        if (val) {
                            val = escapeHTML(val);
                        } else {
                            val = '';
                        }
                        break;
    
                    case "resolution":
                        if (val) {
                            val = escapeHTML(val);
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
                        val = ttDate(val);
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
                                val = ttDate(val);
                                break;

                            case "date":
                                val = ttDate(val, true);
                                break;
                        }
                        
                        val = nl2br(escapeHTML(val));

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
                                m += members[val[i]]?members[val[i]]:val[i];
                                m += ", ";
                            }
        
                            if (m) {
                                m = m.substring(0, m.length - 2);
                            }
        
                            val = escapeHTML(m);
                        } else {
                            val = escapeHTML(members[val]?members[val]:val);
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        if (val === false) {
            val = "";
        }

        return val;
    },

    tt: function (tt) {
        modules.tt.meta = tt["meta"];

        if (!modules.tt.viewers) {
            modules.tt.viewers = {};
        }

        for (let i in modules.tt.meta.viewers) {
            if (!modules.tt.viewers[modules.tt.meta.viewers[i].field]) {
                modules.tt.viewers[modules.tt.meta.viewers[i].field] = {};
            }
            try {
                modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'issue', 'field', 'target', modules.tt.meta.viewers[i].code);
            } catch (e) {
                modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'issue', 'field', 'target', "//function $name (value, field, issue, terget) {\n\treturn value;\n//}\n");
            }
        }
    },

    selectFilter: function (filter, skip, limit, search) {
        if (filter) {
            if (filter !== "#search") {
                lStore("_tt_issue_filter_" + $("#ttProjectSelect").val(), filter);
            }
        } else {
            filter = lStore("_tt_issue_filter_" + $("#ttProjectSelect").val());
        }
        window.location.href = `?#tt&filter=${encodeURIComponent(filter)}&skip=${skip?skip:0}&limit=${limit?limit:modules.tt.defaultIssuesPerPage}&search=${encodeURIComponent(($.trim(search) && typeof search === "string")?$.trim(search):"")}&_=${Math.random()}`;
    },

    selectProject: function (project) {
        lStore("_project", project);
        window.location.href = `?#tt&project=${encodeURIComponent(project)}`;
    },

    renderIssues: function (params, target, issuesListId, callback) {
        if (target === "undefined") {
            target = false;
        }
        
        if (issuesListId === "undefined") {
            issuesListId = md5(guid());
        }

        let rtd = '';

        let current_project;
        
        if (target) {
            current_project = params.project;
        } else {
            current_project = params.project?params.project:lStore("_project");
        }

        let pn = {};

        for (let i in modules.tt.meta.projects) {
            pn[modules.tt.meta.projects[i].acronym] = modules.tt.meta.projects[i].project;
        }

        if (Object.keys(modules.tt.meta.myRoles).length) {
            let cog = "mt-1";
            if (AVAIL("tt", "project", "POST")) {
                cog = "";
            }
            rtd += `<div class="form-inline"><div class="input-group input-group-sm mr-2 ${cog}"><select id="ttProjectSelect" class="form-control select-arrow">`;
            for (let j in modules.tt.meta.myRoles) {
                if (j == current_project) {
                    rtd += `<option selected="selected" value="${j}">${pn[j]} [${j}]</option>`;
                } else {
                    rtd += `<option value="${j}">${pn[j]} [${j}]</option>`;
                }
            }
            rtd += `</select></div>`;
            rtd += '<form autocomplete="off">';
            rtd += `<div class="input-group input-group-sm ${cog} ttSearchInputGroup">`;
            rtd += `<input id="ttSearch" class="form-control" type="search" aria-label="Search" autocomplete="off"><div class="input-group-append"><button class="btn btn-default" id="ttSearchButton" title="${i18n("tt.search")}"><i class="fas fa-search"></i></button></div></div>`;
            if (AVAIL("tt", "project", "POST")) {
                rtd += `<div class="nav-item mr-0 pr-0"><a href="?#tt.settings" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.settings")}"><i class="fas fa-lg fa-fw fa-cog"></i></a></div>`;
            }
            rtd += `</div>`;
            rtd += '</form>';
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

        $("#ttSearch").off("keypress").on("keypress", function (e) {
            if (e.keyCode == 13) {
                $("#ttSearchButton").click();
            }
        });

        $(".ttFilterCustom").off("click").on("click", () => {
            location.href = '?#tt&filter=empty&customSearch=yes&_=' + Math.random();
        });

        $("#ttSearchButton").off("click").on("click", () => {
            let s = $.trim($("#ttSearch").val());
            if (s) {
                modules.tt.selectFilter("#search", 0, modules.tt.defaultIssuesPerPage, s);
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
                x = params["filter"]?params["filter"]:lStore("_tt_issue_filter_" + current_project);
            } catch (e) {
                //
            }
        }

        let filters;

        if (target) {
            filters = `<span class="text-bold">${(modules.tt.meta.filters[x]?modules.tt.meta.filters[x]:i18n("tt.filter")).replaceAll("/", "<i class='fas fa-fw fa-xs fa-angle-double-right'></i>")}</span>`;
        } else {
            let fcount = 0;
            filters = `<span class="dropdown">`;
    
            let filtersTree = {};
            for (let i in project.filters) {
                let tree = (project.filters[i].filter?modules.tt.meta.filters[project.filters[i].filter]:project.filters[i].filter).split("/");
                let f = filtersTree;
                for (let j = 0; j < tree.length - 1; j++) {
                    tree[j] = tree[j].trim();
                    if (!f[tree[j]]) {
                        f[tree[j]] = {};
                    }
                    f = f[tree[j]];
                }
                f[tree[tree.length - 1]] = project.filters[i];
            }

            filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" style="margin-left: -4px;"><i class="far fa-fw fa-caret-square-down mr-1 ml-1"></i>${(modules.tt.meta.filters[x]?modules.tt.meta.filters[x]:i18n("tt.filter")).replaceAll("/", "<i class='fas fa-fw fa-xs fa-angle-double-right'></i>")}</span>`;
            filters += `<ul class="dropdown-menu" aria-labelledby="ttFilter">`;
    
            (function hh(t) {
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
                            filters += `<li class="dropdown-item${hasSub?' nomenu':''} pointer tt_issues_filter font-weight-bold" data-filter-name="${t[i].filter}">`;
                        } else {
                            filters += `<li class="dropdown-item${hasSub?' nomenu':''} pointer tt_issues_filter" data-filter-name="${t[i].filter}">`;
                        }
                        if (parseInt(t[i].personal) > 1000000) {
                            filters += '<i class="fas fa-fw fa-users mr-2"></i>';
                        } else
                        if (parseInt(t[i].personal)) {
                            filters += '<i class="fas fa-fw fa-user mr-2"></i>';
                        } else {
                            filters += '<i class="fas fa-fw fa-globe-americas mr-2"></i>';
                        }
                        filters += i + "</li>";
                        fcount++;
                    } else {
                        filters += `<li class="dropdown-item pointer submenu" style="width: 300px;"><i class="far fa-fw fa-folder mr-2"></i>${i}</li>`;
                        filters += '<div class="dropdown-menu">';
                        hh(t[i]);
                        filters += '</div>';
                        filters += `</li>`;
                    }
                }
            })(filtersTree);
    
            filters += "</ul></span>";
    
            let fp = -1;
            for (let i in project.filters) {
                if (project.filters[i].filter == x) {
                    fp = project.filters[i].personal;
                    break;
                }
            }
    
            if (md5(md5($.trim(modules.tt.meta.filters[x])) + "-" + md5(lStore("_login"))) == x && fp == myself.uid) {
                filters += '<span class="ml-4 hoverable customFilterEdit text-info" data-filter="' + x + '"><i class="far fa-fw fa-edit"></i></span><span class="ml-1 hoverable customFilterEdit text-info" data-filter="' + x + '">' + i18n("tt.customFilterEdit") + '</span>';
                filters += '<span class="ml-2 hoverable customFilterDelete text-danger" data-filter="' + x + '"><i class="far fa-fw fa-trash-alt"></i></span><span class="ml-1 hoverable customFilterDelete text-danger" data-filter="' + x + '">' + i18n("tt.customFilterDelete") + '</span>';
            } else {
                if (AVAIL("tt", "customFilter") && x) {
                    filters += '<span class="ml-4 hoverable customFilterEdit text-info" data-filter="' + x + '"><i class="far fa-fw fa-edit"></i></span><span class="ml-1 hoverable customFilterEdit text-info" data-filter="' + x + '">' + i18n("tt.customFilterEdit") + '</span>';
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

        QUERY("tt", "issues", {
            "project": current_project,
            "filter": x?x:'',
            "skip": skip,
            "limit": limit,
            "search": ($.trim(params.search) && params.search !== true && !target)?$.trim(params.search):'',
        }, true).
        done(response => {
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
                let first = Math.max(page - delta, 1);
                let preFirst = Math.max(0, 1 - page + delta);
                let last = Math.min(page + delta, pages);
                let postLast = Math.max(pages, page + delta) - pages;

                if (last + preFirst - first + postLast >= modules.tt.defaultPagerItemsCount) {
                    if (first > 1) {
                        first++;
                    } else {
                        last--;
                    }
                }

                h += `<nav class="pager" data-target="${issuesListId}">`;
                h += '<ul class="pagination mb-0 ml-0">';

                if (page > 1) {
                    h += `<li class="page-item pointer tt_pager" data-page="1" data-target="${issuesListId}"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                } else {
                    h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                }
                for (let i = Math.max(first - postLast, 1); i <= Math.min(last + preFirst, pages); i++) {
                    if (page == i) {
                        h += `<li class="page-item font-weight-bold disabled" data-page="${i}" data-target="${issuesListId}"><span class="page-link">${i}</span></li>`;
                    } else {
                        h += `<li class="page-item pointer tt_pager" data-page="${i}" data-target="${issuesListId}"><span class="page-link">${i}</span></li>`;
                    }
                }
                if (page < pages) {
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
                cs += `<div id='filterEditorContainer' style='width: 100%; height: ${height}px;'>`;
                cs += `<pre class="ace-editor mt-2" id="filterEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
                cs += "</div>";
                cs += `<span style='position: absolute; right: 35px; top: 35px;'>`;
                cs += `<span id="filterRun" class="hoverable"><i class="fas fa-running pr-2"></i>${i18n("tt.filterRun")}</span>`;
                cs += `</span>`;
                cs += '</div>';
            }

            if (target) {
                if (target !== true) {
                    target.append(`<table class="mt-2 ml-2" style="width: 100%;"><tr><td style="width: 100%;">${filters}</td><td style="padding-right: 15px;">${pager(issuesListId)}</td></tr></table><div class="ml-2 mr-2" id="${issuesListId}"></div>`);
                } else {
                    $(`.pager[data-target="${issuesListId}"]`).html(pager(issuesListId));
                }
            } else {
                $("#mainForm").html(`${cs}<table class="mt-2 ml-2" style="width: 100%;"><tr><td style="width: 100%;">${cs?'&nbsp;':filters}</td><td style="padding-right: 15px;">${pager(issuesListId)}</td></tr></table><div class="ml-2 mr-2" id="${issuesListId}"></div>`);
            }

            $(".tt_issues_filter").off("click").on("click", function () {
                modules.tt.selectFilter($(this).attr("data-filter-name"));
            });

            $(".tt_pager").off("click").on("click", function () {
                if (target) {
                    loadingStart();
                    params.skip = Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit);
                    params.limit = limit?limit:modules.tt.defaultIssuesPerPage;
                    modules.tt.renderIssues(params, true, $(this).attr("data-target"), loadingDone);
                } else {
                    modules.tt.selectFilter(false, Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit));
                }
            });

            new Clipboard('.cc');
            $(".viewFilter").off("click").on("click", () => {
                console.log(x);
            });

            let columns = [ {
                title: i18n("tt.issueIndx"),
                nowrap: true,
            } ];

            let pKeys = [];

            if (issues.projection) {
                pKeys = Object.keys(issues.projection);
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
                            lStore("_tt_issue_filter_" + current_project, n);
                            location.href = '?#tt&filter=' + n + '&customSearch=yes&_=' + Math.random();                        
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
                            loadingDone();
                        });
                    } else {
                        GET("tt", "filter", params.filter).
                        done(response => {
                            editor.setValue(response.body, -1);
                            loadingDone();
                        });
                    }
                }
            }

            $(".customFilterEdit").off("click").on("click", function () {
                location.href = '?#tt&filter=' + $(this).attr("data-filter") + '&customSearch=yes&_=' + Math.random();
            });

            $(".customFilterDelete").off("click").on("click", function () {
                let f = $(this).attr("data-filter");
                mConfirm(i18n("tt.filterDelete", modules.tt.meta.filters[f]), i18n("confirm"), i18n("delete"), () => {
                    loadingStart();
                    DELETE("tt", "customFilter", f, { "project": current_project }).
                    done(() => {
                        message(i18n("tt.filterWasDeleted"));
                        lStore("_tt_issue_filter_" + current_project, null);
                        location.href = '?#tt&_=' + Math.random();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                });
            });

            if (issues.issues) {
                cardTable({
                    target: "#" + issuesListId,
                    columns: columns,
                    rows: () => {
                        let rows = [];

                        for (let i = 0; i < issues.issues.length; i++) {

                            let cols = [ {
                                data: i + skip + 1,
                                nowrap: true,
                                click: modules.tt.issue.viewIssue,
                            } ];

                            for (let j = 0; j < pKeys.length; j++) {
                                cols.push({
                                    data: modules.tt.issueField2Html(issues.issues[i], pKeys[j], undefined, "list"),
                                    nowrap: true,
                                    click: modules.tt.issue.viewIssue,
                                    fullWidth: j == pKeys.length - 1,
                                });
                            }

                            rows.push({
                                uid: utf8_to_b64(JSON.stringify({
                                    id: issues.issues[i]["issueId"],
                                    filter: x?x:"",
                                    index: i + skip + 1,
                                    count: parseInt(issues.count)?parseInt(issues.count):modules.tt.defaultIssuesPerPage,
                                    search: ($.trim(params.search) && params.search !== true)?$.trim(params.search):"",
                                })),
                                cols: cols,
                            });
                        }

                        return rows;
                    },
                });
            } else {
                $("#issuesList").append(`<span class="ml-1 text-bold">${i18n("tt.noIssuesAvailable")}</span>`);
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
            if (target) {
                let e = i18n("errors.unknown");
                if (response && response.responseJSON && response.responseJSON.error) {
                    e = i18n("errors." + response.responseJSON.error);
                }
                e = `<span class="text-danger text-bold">${e} [${params.filter}]<span/>`;
                if (target !== true) {
                    target.append(`<table class="mt-2 ml-2" style="width: 100%;"><tr><td style="width: 100%;">${e}</td></tr></table>`);
                } else {
                    $("#" + issuesListId).html(e);
                }
                if (typeof callback === "undefined") {
                    loadingDone();
                } else {
                    callback();
                }
            } else {
                lStore("_tt_issue_filter_" + $("#ttProjectSelect").val(), null);
                lStore("_tt_issue_filter_" + lStore("_project"), null);
                lStore("_project", null);
                if (params["_"] != _) {
                    window.location.href = `?#tt&_=${_}`;
                }
            }
        });
    },

    route: function (params) {
        loadingStart();

        $("#subTop").html("");

        if ($("#altForm:visible").length > 0) {
            $("#mainForm").html("");
            $("#altForm").hide();
        }

        if (modules.tt.menuItem) {
            $("#" + modules.tt.menuItem).children().first().attr("href", "?#tt&_=" + Math.random());
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
                                modules.tt.issue.renderIssue(r.issue, params["filter"], params["index"], params["count"], params["search"]);
                            });
                        });
                    } else {
                        modules.users.loadUsers(() => {
                            modules.tt.issue.renderIssue(r.issue, params["filter"], params["index"], params["count"], params["search"]);
                        });
                    }
                }).
                fail(FAILPAGE);
            } else {
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