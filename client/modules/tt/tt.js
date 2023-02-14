({
    meta: {},

    defaultIssuesPerPage: 5,
    defaultPagerItemsCount: 10,

    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt", "tt");
        }
        loadSubModules("tt", [
            "issue",
            "settings",
        ], this);
    },
    
    issueFieldTitle: function (field) {
        let fieldId;

        if (typeof field === "object") {
            fieldId = field.field;
        } else{
            fieldId = field;
        }

        if (fieldId.substring(0, 4) !== "_cf_") {
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

                case "attachments":
                    return i18n("tt.attachments");

                case "created":
                    return i18n("tt.created");

                case "updated":
                    return i18n("tt.updated");

                case "author":
                    return i18n("tt.author");

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

    issueField2FormFieldEditor: function (issue, field, projectId) {

        function peoples(project, withGroups, withUsers) {
            let p = [];

            let already = {
                "admin": true,
            };

            if (withGroups) {
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

        if (fieldId.substring(0, 4) !== "_cf_") {
            // regular issue fields
            switch (fieldId) {
                case "issueId":
                    return {
                        id: "issueId",
                        type: "text",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (issue && issue.issueId)?issue.issueId:"",
                        hidden: true,
                    };

                case "subject":
                    return {
                        id: "subject",
                        type: "text",
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        value: (issue && issue.subject)?issue.subject:"",
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
                        value: (issue && issue.description)?issue.description:"",
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

                case "resolution":
                    let resolutions = [];

                    for (let i in modules.tt.meta.resolutions) {
                        if (project.resolutions.indexOf(modules.tt.meta.resolutions[i].resolutionId) >= 0) {
                            resolutions.push({
                                id: modules.tt.meta.resolutions[i].alias,
                                text: modules.tt.meta.resolutions[i].resolution,
                            });
                        }
                    }

                    return {
                        id: "resolution",
                        type: "select2",
                        title: modules.tt.issueFieldTitle(field),
                        options: resolutions,
                        value: (issue && issue.resolution)?issue.resolution:-1,
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    };

                case "status":
                    let statuses = [];

                    for (let i in modules.tt.meta.statuses) {
                        statuses.push({
                            id: modules.tt.meta.statuses[i].status,
                            text: modules.tt.meta.statuses[i].statusDisplay?modules.tt.meta.statuses[i].statusDisplay:modules.tt.meta.statuses[i].status,
                        });
                    }

                    return {
                        id: "status",
                        type: "select2",
                        title: modules.tt.issueFieldTitle(field),
                        options: statuses,
                        value: (issue && issue.status)?issue.status:-1,
                    };

                case "tags":
                    return {
                        id: "tags",
                        type: "select2",
                        tags: true,
                        createTags: false,
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: tags,
                        value: (issue && issue.tags)?Object.values(issue.tags):[],
                    };

                case "assigned":
                    return {
                        id: "assigned",
                        type: "select2",
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: peoples(project, true, true),
                        value: (issue && issue.assigned)?Object.values(issue.assigned):[],
                    };

                case "watchers":
                    return {
                        id: "watchers",
                        type: "select2",
                        multiple: true,
                        title: modules.tt.issueFieldTitle(field),
                        placeholder: modules.tt.issueFieldTitle(field),
                        options: peoples(project, false, true),
                        value: (issue && issue.watchers)?Object.values(issue.watchers):[],
                    };

                case "attachments":
                    return {
                        id: "attachments",
                        type: "files",
                        title: modules.tt.issueFieldTitle(field),
                        maxSize: project.maxFileSize,
                    };
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
                        if ([ "text", "number", "area", "email", "tel", "date", "time", "datetime-local", "yesno" ].indexOf(cf.editor) < 0) {
                            cf.editor = "text";
                        }

                        return {
                            id: "_cf_" + fieldId,
                            type: cf.editor,
                            title: modules.tt.issueFieldTitle(field),
                            placeholder: modules.tt.issueFieldTitle(field),
                            hint: cf.fieldDescription?cf.fieldDescription:false,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:"",
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
                            options: options,
                            multiple: cf.format.indexOf("multiple") >= 0,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[],
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
                            options: options,
                            multiple: cf.format.indexOf("multiple") >= 0,
                            value: (issue && issue["_cf_" + fieldId])?issue["_cf_" + fieldId]:[],
                            validate: validate,
                        }
                }
            }
        }
    },

    issueField2Html: function (issue, field) {
        let members = {};

        if (modules.groups) {
            for (let i in modules.groups.meta) {
                members[modules.groups.meta[i].acronym] = modules.groups.meta[i].name?modules.groups.meta[i].name:modules.groups.meta[i].acronym;
            }
        }

        for (let i in modules.users.meta) {
            members[modules.users.meta[i].login] = modules.users.meta[i].realName?modules.users.meta[i].realName:modules.users.meta[i].login;
        }

        let val = issue[field];

        if (field.substring(0, 4) !== "_cf_") {
            switch (field) {
                case "description":
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

                    val = m;
                    break;

                case "author":
                    val = members[val]?members[val]:val;
                    break;

                case "status":
                    for (let i in modules.tt.meta.statuses) {
                        if (val == modules.tt.meta.statuses[i].status) {
                            val = modules.tt.meta.statuses[i].statusDisplay?modules.tt.meta.statuses[i].statusDisplay:modules.tt.meta.statuses[i].status;
                            break;
                        }
                    }
                    break;

                case "resolution":
                    for (let i in modules.tt.meta.resolutions) {
                        if (val == modules.tt.meta.resolutions[i].alias) {
                            val = modules.tt.meta.resolutions[i].resolution?modules.tt.meta.resolutions[i].resolution:modules.tt.meta.resolution[i].alias;
                            break;
                        }
                    }
                    break;

                case "created":
                case "updated":
                    val = ttDate(val);
                    break;
            }
        } else {
            // TODO: add viewer functions and formatting for custom fields
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
            modules.tt.viewers[modules.tt.meta.viewers[i].field][modules.tt.meta.viewers[i].name] = new Function('value', 'field', 'issue', modules.tt.meta.viewers[i].code);
        }
    },

    selectFilter: function (filter, skip, limit) {
        if (filter) {
            $.cookie("_tt_issue_filter_" + $("#ttProjectSelect").val(), filter, { expires: 3650, insecure: config.insecureCookie });
        } else {
            filter = $.cookie("_tt_issue_filter_" + $("#ttProjectSelect").val());
        }
        window.location.href = `#tt&filter=${filter}&skip=${skip?skip:0}&limit=${limit?limit:modules.tt.defaultIssuesPerPage}`;
    },

    selectProject: function (project) {
        $.cookie("_project", project, { expires: 3650, insecure: config.insecureCookie });
        window.location.href = `#tt&project=${project}`;
    },

    viewIssue: function (issue) {
        issue = JSON.parse(b64_to_utf8(issue));
        window.location.href = `#tt&issue=${issue.id}&filter=${issue.filter}&index=${issue.index}&count=${issue.count}`;
    },

    doAction: function (issue, action) {
        loadingStart();
        QUERY("tt", "workflowActionTemplate", {
            issue: issue.issue.issueId,
            action: action,
        }, true).done(r => {
            let fields = [
                {
                    id: "issueId",
                    type: "text",
                    readonly: true,
                    title: i18n("tt.issue"),
                    value: issue.issue.issueId,
                    hidden: true,
                },
            ];

            let project;

            for (let i in modules.tt.meta.projects) {
                if (modules.tt.meta.projects[i].acronym == issue.issue.project) {
                    project = modules.tt.meta.projects[i];
                }
            }

            let n = 0;
            for (let i in r.template) {
                fields.push(this.issueField2FormFieldEditor(issue.issue, r.template[i], project.projectId));
                if (r.template[i] == "comment") {
                    fields.push({
                        id: "commentPrivate",
                        type: "yesno",
                        title: i18n("tt.commentPrivate"),
                        value: "1",
                    });
                }
                n++;
            }

            if (n) {
                cardForm({
                    title: action,
                    apply: action,
                    fields: fields,
                    footer: true,
                    borderless: true,
                    size: "lg",
                    callback: r => {
                        loadingStart();
                        PUT("tt", "workflowProgressAction", false, {
                            set: r,
                            action: action,
                        }).
                        fail(FAIL).
                        always(() => {
                            modules.tt.route({
                                "issue": issue.issue.issueId,
                            });
                        });
                    },
                });
            } else {
                mConfirm(action + " \"" + issue.issue.issueId + "\"?", i18n("confirm"), action, () => {
                    loadingStart();
                    PUT("tt", "workflowProgressAction", false, {
                        set: {
                            issueId: issue.issue.issueId,
                        },
                        action: action,
                    }).
                    fail(FAIL).
                    always(() => {
                        modules.tt.route({
                            "issue": issue.issue.issueId,
                        });
                    });
                });
            }
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderIssue: function (issue, filter, index, count) {

        function fieldRow(i) {
            let h = '';

            if (![ "issueId", "comments", "attachments", "journal", "project", "workflow", "tags" ].includes(issue.fields[i]) && !isEmpty(issue.issue[issue.fields[i]])) {
                h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${modules.tt.issueFieldTitle(issue.fields[i])}' style="font-size: 11pt;"/></td></tr>`;
                h += "<tr>";
                h += "<td colspan='2' style='width: 100%; font-size: 12pt;' class='pl-1'>";
                h += modules.tt.issueField2Html(issue.issue, issue.fields[i]);
                h += "<td>";
                h += "</tr>";
            }

            return h;
        }

        console.log(filter, index, count);
        console.log(issue);
        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt") + " :: " + issue.issue["issueId"];

        let rightFields = [ "status", "resolution", "assigned", "watchers", "created", "updated", "author" ];

        let tags = {};
        let project = false;

        for (let i in modules.tt.meta.projects) {
            if (modules.tt.meta.projects[i].acronym === issue.issue.project) {
                project = modules.tt.meta.projects[i];
                break;
            }
        }

        for (let i in project.tags) {
            tags[project.tags[i].tag] = project.tags[i];
        }

        let h = "";

        h += "<table class='mt-2 ml-2' style='width: 100%;'>";
        h += "<tr>";
        h += "<td style='vertical-align: top; width: 100%;'>";
        h += "<div class='text-bold pt-1 pb-1'>";
        h += "<span class='mr-3'>";
        h += issue.issue["issueId"];
        if (!isEmpty(issue.actions)) {
            h += ":";
        }
        h += "</span>";

        let specialActions = [
            "saAddComment",
            "saAddFile",
            "saAssignToMe",
            "saWatch",
            "saDelete",
            "saLink",
            "saSubTask",
        ];

        if (!isEmpty(issue.actions)) {
            let t = 0;
            let la = false;
            for (let i in issue.actions) {
                if (issue.actions[i].substring(0, 1) === "!") {
                    if (specialActions.indexOf(issue.actions[i].substring(1)) >= 0) {
                        let a = issue.actions[i].substring(1);
                        h += `<span class="hoverable text-primary mr-3 tt${a.charAt(0).toUpperCase() + a.substring(1)}">${i18n("tt." + a)}</span>`;
                    } else {
                        h += `<span class="hoverable text-primary mr-3 ttIssueAction">${issue.actions[i].substring(1)}</span>`;
                    }
                    t++;
                } else
                if (issue.actions[i] == "-") {
                    t++;
                } else {
                    la = issue.actions[i];
                }
            }
            if (Object.keys(issue.actions).length - t === 1) {
                h += `<span class="hoverable text-primary mr-3 ttIssueAction">${la}</span>`;
            } else
            if (t < Object.keys(issue.actions).length) {
                h += `<span class="dropdown">`;
                h += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary" id="ttIssueAllActions" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">${i18n("tt.allActions")}</span>`;
                h += `<ul class="dropdown-menu" aria-labelledby="ttIssueAllActions">`;
                let hr = true;
                for (let i = Object.keys(issue.actions).length - 1; i >= 0; i--) {
                    if (issue.actions[Object.keys(issue.actions)[i]] == "-") {
                        delete issue.actions[Object.keys(issue.actions)[i]];
                    } else {
                        break;
                    }
                }
                for (let i in issue.actions) {
                    let a = issue.actions[i];
                    if (a.substring(0, 1) === "!") {
                        a = a.substring(1);
                    }
                    if (specialActions.indexOf(a) >= 0) {
                        h += `<li class="pointer dropdown-item tt${a.charAt(0).toUpperCase() + a.substring(1)}">${i18n("tt." + a)}</li>`;
                        hr = false;
                    } else {
                        if (a == "-") {
                            if (!hr) {
                                h += `<li class="dropdown-divider"></li>`;
                                hr = true;
                            }
                        } else {
                            h += `<li class="pointer dropdown-item ttIssueAction">${a}</li>`;
                            hr = false;
                        }
                    }
                }
                h += `</ul></span>`;
            }
        }
        h += "</div>";
        h += "</td>";
        h += "<td style='text-align: right;' class='pr-2'>";
        h += "<i id='stepPrev' class='fas fa-fw fa-chevron-left text-muted'></i>"
        h += "<i id='stepNext' class='fas fa-fw fa-chevron-right text-muted'></i>"
        h += "</td>";
        h += "</tr>";

        if (!isEmpty(issue.issue.tags)) {
            h += "<tr>";
            h += "<td style='vertical-align: top; width: 100%;'>";
            h += "<div class='pt-1 pb-1 small'>";
            let t = "";
            for (let i in issue.issue.tags) {
                let fg = (tags[issue.issue.tags[i]] && tags[issue.issue.tags[i]].foreground)?tags[issue.issue.tags[i]].foreground:"#666666";
                let bg = (tags[issue.issue.tags[i]] && tags[issue.issue.tags[i]].background)?tags[issue.issue.tags[i]].background:"#ffffff";
                t += `<span class="mr-1 text-bold" style='border: solid thin #cbccce; padding-left: 6px; padding-right: 5px; padding-top: 2px; padding-bottom: 2px; color: ${fg}; border-radius: 4px; background: ${bg};'><i class="fas fa-tag mr-2"></i>${issue.issue.tags[i]}</span>`;
            }
            h += $.trim(t);
            h += "</div>";
            h += "</td>";
            h += "</tr>";
        }

        h += "<tr>";
        h += "<td style='vertical-align: top;'>";
        h += "<table style='width: 100%;'>";
        for (let i in issue.fields) {
            if (!rightFields.includes(issue.fields[i])) {
                h += fieldRow(i);
            }
        }
        h += "</table>";
        h += "</td>";
        h += "<td style='vertical-align: top;'>";
        h += "<table style='width: 300px;'>";
        for (let i in issue.fields) {
            if (rightFields.includes(issue.fields[i])) {
                h += fieldRow(i);
            }
        }
        h += "</table>";
        h += "</td>";
        h += "</tr>";
        let members = {};

        for (let i in modules.users.meta) {
            members[modules.users.meta[i].login] = modules.users.meta[i].realName?modules.users.meta[i].realName:modules.users.meta[i].login;
        }

        if (issue.issue.attachments && Object.keys(issue.issue.attachments).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.attachments")}' style="font-size: 11pt;"/></td></tr>`;
            for (let i in issue.issue.attachments) {
                h += "<tr>";
                h += "<td colspan='2' class='pl-1' style='font-size: 14px;'>";
                h += "<div>";
                h += "#" + (parseInt(i) + 1) + " ";
                h += ttDate(issue.issue.attachments[i].metadata.added);
                h += "<span class='ml-2 text-info text-bold'>";
                h += members[issue.issue.attachments[i].metadata.attachman]?members[issue.issue.attachments[i].metadata.attachman]:issue.issue.attachments[i].metadata.attachman;
                h += "</span>";
                if (modules.tt.meta.myRoles[issue.issue.project] >= 20 && issue.issue.status != "closed") {
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.attachments[i].metadata.attachman == $.cookie("_login")) {
                        h += "<i class='far fa-trash-alt ml-2 pointer text-danger deleteAttachment'></i>";
                    }
                }
                h += "</div>";
                h += "<div class='ml-2 mb-2 mt-1'>";
                h += "<a class='hoverable' href='" + $.cookie("_server") + "/tt/file?issueId=" + encodeURIComponent(issue.issue["issueId"]) + "&filename=" + encodeURIComponent(issue.issue.attachments[i].filename) + "&_token=" + encodeURIComponent($.cookie("_token")) + "' target='_blank'>";
                h += $.trim(issue.issue.attachments[i].filename);
                h += "</a>";
                h += "</div>";
                h += "</td>";
                h += "</tr>";
            }
        }

        if (issue.issue.comments && Object.keys(issue.issue.comments).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.comments")}' style="font-size: 11pt;"/></td></tr>`;
            for (let i in issue.issue.comments) {
                h += "<tr>";
                h += "<td colspan='2' class='pl-1' style='font-size: 14px;'>";
                h += "<div>";
                h += "#" + (parseInt(i) + 1) + " ";
                h += ttDate(issue.issue.comments[i].created);
                h += "<span class='ml-2 text-info text-bold'>";
                h += members[issue.issue.comments[i].author]?members[issue.issue.comments[i].author]:issue.issue.comments[i].author;
                h += "</span>";
                if (issue.issue.comments[i].private) {
                    h += "<i class='fas fa-fw fa-eye-slash ml-2 text-warning'></i>";
                } else {
                    h += "<i class='fas fa-fw fa-eye ml-2 text-success'></i>";
                }
                if (modules.tt.meta.myRoles[issue.issue.project] >= 20 && issue.issue.status != "closed") {
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.comments[i].author == $.cookie("_login")) {
                        h += `<i class='far fa-fw fa-edit ml-2 pointer text-primary modifyComment' data-index='${i}'></i>`;
                    }
                }
                h += "</div>";
                h += "<div class='ml-2 mb-2 mt-1'>";
                h += nl2br($.trim(issue.issue.comments[i].body));
                h += "</div>";
                h += "</td>";
                h += "</tr>";
            }
        }
        h += "</table>";
        $("#mainForm").html(h);

        $(".ttIssueAction").off("click").on("click", function () {
            modules.tt.doAction(issue, $(this).text())
        });

        $(".ttSaAddComment").off("click").on("click", () => {
            cardForm({
                title: i18n("tt.addComment"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "lg",
                fields: [
                    {
                        id: "issueId",
                        type: "text",
                        readonly: true,
                        value: issue.issue["issueId"],
                        title: i18n("tt.issue"),
                        hidden: true,
                    },
                    {
                        id: "comment",
                        type: "area",
                        title: i18n("tt.comment"),
                        placeholder: i18n("tt.comment"),
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    },
                    {
                        id: "commentPrivate",
                        type: "yesno",
                        title: i18n("tt.commentPrivate"),
                        value: "1",
                    },
                ],
                callback: function (result) {
                    loadingStart();
                    POST("tt", "comment", false, result).
                    fail(FAIL).
                    done(() => {
                        modules.tt.route({
                            "issue": issue.issue.issueId,
                        });
                    }).
                    always(loadingDone);
                },
            }).show();
        });

        $(".modifyComment").off("click").on("click", function () {
            let i = $(this).attr("data-index");
            cardForm({
                title: i18n("tt.modifyComment"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "lg",
                fields: [
                    {
                        id: "issueId",
                        type: "text",
                        readonly: true,
                        value: issue.issue["issueId"],
                        title: i18n("tt.issue"),
                        hidden: true,
                    },
                    {
                        id: "commentIndex",
                        type: "text",
                        readonly: true,
                        value: i,
                        title: i18n("tt.commentIndex"),
                        hidden: true,
                    },
                    {
                        id: "comment",
                        type: "area",
                        title: i18n("tt.comment"),
                        placeholder: i18n("tt.comment"),
                        value: issue.issue.comments[i].body,
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                    },
                    {
                        id: "commentPrivate",
                        type: "yesno",
                        title: i18n("tt.commentPrivate"),
                        value: issue.issue.comments[i].private?"1":"0",
                    },
                ],
                delete: i18n("tt.deleteComment"),
                callback: function (result) {
                    if (result.delete) {
                        loadingStart();
                        DELETE("tt", "comment", false, result).
                        fail(FAIL).
                        done(() => {
                            modules.tt.route({
                                "issue": issue.issue.issueId,
                            });
                        }).
                        always(loadingDone);
                    } else {
                        loadingStart();
                        PUT("tt", "comment", false, result).
                        fail(FAIL).
                        done(() => {
                            modules.tt.route({
                                "issue": issue.issue.issueId,
                            });
                        }).
                        always(loadingDone);
                    }
                },
            }).show();
        });

        $(".ttSaAddFile").off("click").on("click", () => {
            cardForm({
                title: i18n("tt.addFile"),
                footer: true,
                borderless: true,
                topApply: true,
                size: "lg",
                apply: i18n("tt.addFile"),
                fields: [
                    {
                        id: "issueId",
                        type: "text",
                        readonly: true,
                        value: issue.issue["issueId"],
                        title: i18n("tt.issue"),
                        hidden: true,
                    },
                    {
                        id: "attachments",
                        type: "files",
                        title: i18n("tt.attachments"),
                        maxSize: project.maxFileSize,
                        autoload: true,
                    },
                ],
                callback: function (result) {
                    if (result.attachments.length) {
                        loadingStart();
                        POST("tt", "file", false, result).
                        fail(FAIL).
                        done(() => {
                            modules.tt.route({
                                "issue": issue.issue.issueId,
                            });
                        }).
                        always(loadingDone);
                    }
                },
            }).show();
        });

        $(".deleteAttachment").off("click").on("click", function () {
            let file = $(this).parent().next().text();
            mConfirm(i18n("tt.deleteFile", file), i18n("confirm"), i18n("delete"), () => {
                loadingStart();
                DELETE("tt", "file", false, {
                    issueId: issue.issue.issueId,
                    filename: file,
                }).
                fail(FAIL).
                done(() => {
                    modules.tt.route({
                        "issue": issue.issue.issueId,
                    });
                }).
                always(loadingDone);
            });
        });

        $(".ttSaAssignToMe").off("click").on("click", () => {
            console.log("assignToMe");
        });

        $(".ttSaWatch").off("click").on("click", () => {
            console.log("watch");
        });

        $(".ttSaDelete").off("click").on("click", () => {
            console.log("delete");
        });

        $(".ttSaLink").off("click").on("click", () => {
            console.log("link");
        });

        $(".ttSaSubTask").off("click").on("click", () => {
            console.log("subTask");
        });

        $("#stepPrev").off("click").on("click", () => {
            console.log("stepPrev");
        });

        $("#stepNext").off("click").on("click", () => {
            console.log("stepNext");
        });
    },

    route: function (params) {
        loadingStart();

        $("#subTop").html("");
        $("#altForm").hide();

        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (params["issue"]) {
                GET("tt", "issue", params["issue"], true).
                done(r => {
                    if (modules.groups) {
                        modules.users.loadUsers(() => {
                            modules.groups.loadGroups(() => {
                                modules.tt.renderIssue(r.issue, params["filter"], params["index"], params["count"]);
                            });
                        });
                    } else {
                        modules.users.loadUsers(() => {
                            modules.tt.renderIssue(r.issue, params["filter"], params["index"], params["count"]);
                        });
                    }
                }).
                fail(FAIL).
                always(loadingDone);
            } else {
                if (myself.uid) {
                    let rtd = '';

                    let current_project = params["project"]?params["project"]:$.cookie("_project");

                    let pn = {};

                    for (let i in modules.tt.meta.projects) {
                        pn[modules.tt.meta.projects[i].acronym] = modules.tt.meta.projects[i].project;
                    }

                    if (Object.keys(modules.tt.meta.myRoles).length) {
                        let cog = "mt-1";
                        if (AVAIL("tt", "project", "POST")) {
                            cog = "";
                        }
                        rtd += `
                            <div class="form-inline">
                                <div class="input-group input-group-sm mr-2 ${cog}">
                                    <select id="ttProjectSelect" class="form-control">
                        `;
                        for (let j in modules.tt.meta.myRoles) {
                            if (j == current_project) {
                                rtd += `<option selected="selected" value="${j}">${pn[j]} [${j}]</option>`;
                            } else {
                                rtd += `<option value="${j}">${pn[j]} [${j}]</option>`;
                            }
                        }
                        rtd += `
                                </select>
                                </div>
                                <div class="input-group input-group-sm ${cog}">
                                    <input id="ttSearch" class="form-control" type="search" aria-label="Search">
                                    <div class="input-group-append">
                                        <button class="btn btn-default" id="ttSearchButton">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>`;
                        if (AVAIL("tt", "project", "POST")) {
                            rtd += `
                                <div class="nav-item mr-0 pr-0">
                                    <a href="#tt.settings&edit=projects" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.settings")}">
                                        <i class="fas fa-lg fa-fw fa-cog"></i>
                                    </a>
                                </div>
                            `;
                        }
                        rtd += `
                            </div>
                        `;
                    } else {
                        if (AVAIL("tt", "project", "POST")) {
                            rtd += `
                                <div class="nav-item mr-0 pr-0">
                                    <a href="#tt.settings&edit=projects" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.settings")}">
                                        <i class="fas fa-lg fa-fw fa-cog"></i>
                                    </a>
                                </div>
                            `;
                        }
                    }

                    $("#rightTopDynamic").html(rtd);

                    current_project = $("#ttProjectSelect").val();

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

                    let x = false;
                    let f = false;

                    try {
                        x = params["filter"]?params["filter"]:$.cookie("_tt_issue_filter_" + current_project);
                        f = modules.tt.meta.filters[x];
                    } catch (e) {
                        //
                    }

                    let fcount = 0;
                    let filters = `<span class="dropdown">`;

                    filters += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary text-bold" id="ttFilter" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">${f?f:i18n("tt.filter")}</span>`;
                    filters += `<ul class="dropdown-menu" aria-labelledby="ttFilter">`;
                    for (let i in project.filters) {
                        if (x == project.filters[i]) {
                            filters += `<li class="pointer dropdown-item tt_issues_filter text-bold" data-filter-name="${project.filters[i]}">${modules.tt.meta.filters[project.filters[i]] + " [" + project.filters[i] + "]"}</li>`;
                        } else {
                            filters += `<li class="pointer dropdown-item tt_issues_filter" data-filter-name="${project.filters[i]}">${modules.tt.meta.filters[project.filters[i]] + " [" + project.filters[i] + "]"}</li>`;
                        }
                        fcount++;
                    }
                    filters += `</ul></span>`;

                    if (!fcount) {
                        filters = `<span class="text-bold text-warning">${i18n('tt.noFiltersAvailable')}</span>`;
                    }

                    if (myself.uid) {
                        $("#leftTopDynamic").html(`
                            <li class="nav-item d-none d-sm-inline-block">
                                <a href="javascript:void(0)" class="nav-link text-success text-bold createIssue">${i18n("tt.createIssue")}</a>
                            </li>
                        `);
                    }

                    $(".createIssue").off("click").on("click", () => {
                        modules.tt.issue.createIssue($("#ttProjectSelect").val());
                    });

                    document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");

                    f = $.cookie("_tt_issue_filter_" + current_project);

                    let skip = parseInt(params.skip?params.skip:0);
                    let limit = parseInt(params.limit?params.limit:modules.tt.defaultIssuesPerPage);

                    QUERY("tt", "issues", {
                        "project": current_project,
                        "filter": f?f:'',
                        "skip": skip,
                        "limit": limit,
                    }, true).
                    done(response => {
                        let issues = response.issues;

                        console.log(issues);

                        limit = parseInt(issues.limit);
                        skip = parseInt(issues.skip);
                        let page = Math.floor(skip / limit) + 1;

                        function pager() {
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

                            h += '<nav>';
                            h += '<ul class="pagination mb-0 ml-0">';

                            if (page > 1) {
                                h += `<li class="page-item pointer tt_pager" data-page="1"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                            } else {
                                h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
                            }
                            for (let i = Math.max(first - postLast, 1); i <= Math.min(last + preFirst, pages); i++) {
                                if (page == i) {
                                    h += `<li class="page-item pointer font-weight-bold tt_pager" data-page="${i}"><span class="page-link">${i}</span></li>`;
                                } else {
                                    h += `<li class="page-item pointer tt_pager" data-page="${i}"><span class="page-link">${i}</span></li>`;
                                }
                            }
                            if (page < pages) {
                                h += `<li class="page-item pointer tt_pager" data-page="${pages}"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
                            } else {
                                h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
                            }

                            h += '</ul>';
                            h += '</nav>';

                            return h;
                        }

                        $("#mainForm").html(`
                            <table class="mt-2 ml-2" style="width: 100%;">
                                <tr><td style="width: 100%;">${filters}</td><td style="padding-right: 15px;">${pager()}</td></tr>
                            </table>
                            <div class="ml-2 mr-2" id="issuesList"></div>
                        `);

                        $(".tt_issues_filter").off("click").on("click", function () {
                            modules.tt.selectFilter($(this).attr("data-filter-name"));
                        });

                        $(".tt_pager").off("click").on("click", function () {
                            modules.tt.selectFilter(false, Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit));
                        });

                        let columns = [ {
                            title: i18n("tt.issueIndx"),
                            nowrap: true,
                        } ];

                        let pKeys = Object.keys(issues.projection);

                        for (let i = 0; i < pKeys.length; i++) {
                            columns.push({
                                title: modules.tt.issueFieldTitle(pKeys[i]),
                                nowrap: true,
                                fullWidth: i == pKeys.length - 1,
                            });
                        };

                        if (issues.issues) {
                            cardTable({
                                target: "#issuesList",
                                columns: columns,
                                rows: () => {
                                    let rows = [];

                                    for (let i = 0; i < issues.issues.length; i++) {

                                        let cols = [ {
                                            data: i + skip + 1,
                                            nowrap: true,
                                            click: modules.tt.viewIssue,
                                        } ];

                                        for (let j = 0; j < pKeys.length; j++) {
                                            cols.push({
                                                data: modules.tt.issueField2Html(issues.issues[i], pKeys[j]),
                                                nowrap: true,
                                                click: modules.tt.viewIssue,
                                                fullWidth: j == pKeys.length - 1,
                                            });
                                        }

                                        rows.push({
                                            uid: utf8_to_b64(JSON.stringify({
                                                id: issues.issues[i]["issueId"],
                                                filter: f?f:'',
                                                index: i + skip + 1,
                                                count: issues.count,
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
                    }).
                    fail(FAIL).
                    always(loadingDone);
                } else {
                    window.location.href = "#tt.settings&edit=projects";
                }
            }
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();