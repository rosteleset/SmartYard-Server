({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    createIssue: function (current_project) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            let workflows = [];

            for (let i in modules.tt.meta.workflows) {
                workflows[i] = modules.tt.meta.workflows[i]?modules.tt.meta.workflows[i]:i;
            }

            function workflowsByProject(project) {
                let w = [];

                if (project) {
                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].acronym == project) {
                            for (let j in modules.tt.meta.projects[i].workflows) {
                                w.push({
                                    id: modules.tt.meta.projects[i].workflows[j],
                                    text: $.trim(workflows[modules.tt.meta.projects[i].workflows[j]] + " [" + modules.tt.meta.projects[i].workflows[j] + "]"),
                                    selected: $.cookie("_workflow") == modules.tt.meta.projects[i].workflows[j],
                                });
                            }
                            break;
                        }
                    }
                }

                return w;
            }

            let projects = [];

            projects.push({
                id: "",
                text: "-",
            });

            for (let i in modules.tt.meta.projects) {
                projects.push({
                    id: modules.tt.meta.projects[i].acronym,
                    text: $.trim(modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]"),
                    selected: current_project == modules.tt.meta.projects[i].acronym || $.cookie("_project") == modules.tt.meta.projects[i].acronym,
                });
            }

            cardForm({
                title: i18n("tt.createIssue"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
                apply: "create",
                singleColumn: true,
                fields: [
                    {
                        id: "project",
                        type: "select2",
                        title: i18n("tt.project"),
                        options: projects,
                        minimumResultsForSearch: Infinity,
                        select: (el, id, prefix) => {
                            $(`#${prefix}workflow`).html("").select2({
                                data: workflowsByProject(el.val()),
                                minimumResultsForSearch: Infinity,
                                language: lang["_code"],
                            });
                        },
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                    },
                    {
                        id: "workflow",
                        type: "select2",
                        title: i18n("tt.workflow"),
                        minimumResultsForSearch: Infinity,
                        options: workflowsByProject(current_project),
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                    },
                ],
                callback: function (result) {
                    if (result.project && result.workflow) {
                        $.cookie("_project", result.project, { expires: 3650, insecure: config.insecureCookie });
                        $.cookie("_workflow", result.workflow, { expires: 3650, insecure: config.insecureCookie });
                    }
                    location.href = `#tt.issue&action=create&project=${result.project}&workflow=${result.workflow}`;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone)
    },

    createIssueForm: function (current_project, workflow) {
        $("#leftTopDynamic").html("");
        $("#rightTopDynamic").html("");

        loadingStart();
        modules.users.loadUsers(() => {
            modules.groups.loadGroups(() => {
                QUERY("tt", "workflowCreateIssueTemplate", {
                    workflow: workflow,
                }).
                done(response => {
                    document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");

                    let workflows = [];

                    for (let i in modules.tt.meta.workflows) {
                        workflows[i] = modules.tt.meta.workflows[i]?modules.tt.meta.workflows[i]:i;
                    }

                    let projectName = "";
                    let project = false;
                    let projectId = -1;

                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].acronym == current_project) {
                            project = modules.tt.meta.projects[i];
                            projectName = modules.tt.meta.projects[i].project?$.trim(modules.tt.meta.projects[i].project + " [" + modules.tt.meta.projects[i].acronym + "]"):modules.tt.meta.projects[i].acronym;
                            projectId = modules.tt.meta.projects[i].projectId;
                        }
                    }

                    let fields = [
                        {
                            id: "projectName",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.project"),
                            value: projectName,
                        },
                        {
                            id: "projectAcronym",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.projectAcronym"),
                            value: project.acronym,
                            hidden: true,
                        },
                        {
                            id: "workflowName",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.workflow"),
                            value: workflows[workflow],
                        },
                        {
                            id: "workflow",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.workflow"),
                            value: workflow,
                            hidden: true,
                        },
                    ];

                    let af = [];
                    if (response.template && response.template.fields) {
                        for (let i in response.template.fields) {
                            if (af.indexOf(response.template.fields[i]) < 0) {
                                let f = modules.tt.issueField2FormFieldEditor(false, response.template.fields[i], projectId);
                                if (f) {
                                    fields.push(f);
                                    af.push(response.template.fields[i]);
                                }
                            }
                        }
                    }

                    cardForm({
                        title: i18n("tt.createIssueTitle"),
                        footer: true,
                        borderless: true,
                        target: "#mainForm",
                        apply: "create",
                        fields: fields,
                        callback: modules.tt.issue.doCreateIssue,
                        cancel: () => {
                            history.back();
                        },
                    });

                    loadingDone();
                }).
                fail(FAIL).
                fail(() => {
                    history.back();
                });
            });
        });
    },

    doCreateIssue: function (issue) {
        loadingStart();
        delete issue.projectName;
        delete issue.workflowName;
        issue.project = issue.projectAcronym;
        delete issue.projectAcronym;
        POST("tt", "issue", false, {
            issue: issue,
        }).
        done(result => {
            modules.tt.issue.viewIssue(utf8_to_b64(JSON.stringify(result)));
        }).
        fail(FAIL).
        always(loadingDone);
    },

    viewIssue: function (issue) {
        issue = JSON.parse(b64_to_utf8(issue));
        window.location.href = `#tt&issue=${encodeURIComponent(issue.id)}&filter=${encodeURIComponent(issue.filter?issue.filter:"")}&index=${issue.index?issue.index:""}&count=${issue.count?issue.count:""}&search=${encodeURIComponent(($.trim(issue.search) && typeof issue.search === "string")?$.trim(issue.search):"")}`;
    },

    renderIssue: function (issue, filter, index, count, search) {
        $("#leftTopDynamic").html("");
        $("#rightTopDynamic").html("");

        search = ($.trim(search) && typeof search === "string")?$.trim(search):"";
        
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
        if (index && count && index !== true && count !== true) {
            if (parseInt(index) > 1) {
                h += "<i id='stepPrev' class='fas fa-fw fa-chevron-left pointer'></i>"
            } else {
                h += "<i class='fas fa-fw fa-chevron-left text-muted'></i>"
            }
            h += "<span class='ml-2 mr-2'>" + index + " " + i18n("tt.of") + " " + count + "</span>";
            if (parseInt(index) < parseInt(count)) {
                h += "<i id='stepNext' class='fas fa-fw fa-chevron-right pointer'></i>"
            } else {
                h += "<i class='fas fa-fw fa-chevron-right text-muted'></i>"
            }
        } else {
            h += "&nbsp;";
        }
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
            let action = $(this).text();

            loadingStart();
            QUERY("tt", "workflowActionTemplate", {
                _id: issue.issue.issueId,
                action: action,
            }, true).done(r => {
                let fields = [];
    
                let project;
    
                for (let i in modules.tt.meta.projects) {
                    if (modules.tt.meta.projects[i].acronym == issue.issue.project) {
                        project = modules.tt.meta.projects[i];
                    }
                }
    
                let n = 0;
                for (let i in r.template) {
                    fields.push(modules.tt.issueField2FormFieldEditor(issue.issue, r.template[i], project.projectId));
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
                            r["issueId"] = issue.issue.issueId;
                            loadingStart();
                            PUT("tt", "workflowProgressAction", issue.issue.issueId, {
                                set: r,
                                action: action,
                            }).
                            fail(FAIL).
                            always(() => {
                                modules.tt.route({
                                    issue: issue.issue.issueId,
                                    filter: filter,
                                    index: index,
                                    count: count,
                                    search: search,
                                });
                            });
                        },
                    });
                } else {
                    mConfirm(action + " \"" + issue.issue.issueId + "\"?", i18n("confirm"), action, () => {
                        loadingStart();
                        PUT("tt", "workflowProgressAction", issue.issue.issueId, {
                            set: {
                                issueId: issue.issue.issueId,
                            },
                            action: action,
                        }).
                        fail(FAIL).
                        always(() => {
                            modules.tt.route({
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
                            });
                        });
                    });
                }
            }).
            fail(FAIL).
            always(loadingDone);
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
                            issue: issue.issue.issueId,
                            filter: filter,
                            index: index,
                            count: count,
                            search: search,
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
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
                            });
                        }).
                        always(loadingDone);
                    } else {
                        loadingStart();
                        PUT("tt", "comment", false, result).
                        fail(FAIL).
                        done(() => {
                            modules.tt.route({
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
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
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
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
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                }).
                always(loadingDone);
            });
        });

        $(".ttSaAssignToMe").off("click").on("click", () => {
            mConfirm(i18n("tt.confirmAssignToMe"), i18n("confirm"), i18n("tt.saAssignToMe"), () => {
                loadingStart();
                PUT("tt", "issue", issue.issue.issueId, {
                    "action": "assignToMe"
                }).
                fail(FAIL).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                }).
                always(loadingDone);
            });
        });

        $(".ttSaWatch").off("click").on("click", () => {
            mConfirm(i18n("tt.confirmWatch"), i18n("confirm"), i18n("tt.saWatch"), () => {
                loadingStart();
                PUT("tt", "issue", issue.issue.issueId, {
                    "action": "watch"
                }).
                fail(FAIL).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                }).
                always(loadingDone);
            });
        });

        $(".ttSaDelete").off("click").on("click", () => {
            mConfirm(i18n("tt.confirmDeleteIssue", issue.issue.issueId), i18n("confirm"), i18n("delete"), () => {
                loadingStart();
                DELETE("tt", "issue", issue.issue.issueId).
                fail(FAIL).
                done(() => {
                    window.location.href = "#tt";
                }).
                fail(loadingDone);
            });
        });

        $(".ttSaLink").off("click").on("click", () => {
            console.log("link");
        });

        $(".ttSaSubTask").off("click").on("click", () => {
            console.log("subTask");
        });

        $("#stepPrev").off("click").on("click", () => {
            loadingStart();
            QUERY("tt", "issues", {
                "project": issue.issue.project,
                "filter": filter,
                "skip": Math.max(parseInt(index) - 2, 0),
                "limit": 1,
                "search": search,
            }, true).
            done(response => {
                modules.tt.issue.viewIssue(utf8_to_b64(JSON.stringify({
                    id: response.issues.issues[0]["issueId"],
                    filter: filter,
                    index: parseInt(response.issues.skip) + 1,
                    count: parseInt(response.issues.count),
                    search: search,
                })));
            }).
            fail(FAIL).
            fail(loadingDone);
        });

        $("#stepNext").off("click").on("click", () => {
            loadingStart();
            QUERY("tt", "issues", {
                "project": issue.issue.project,
                "filter": filter,
                "skip": index,
                "limit": 1,
                "search": search,
            }, true).
            done(response => {
                modules.tt.issue.viewIssue(utf8_to_b64(JSON.stringify({
                    id: response.issues.issues[0]["issueId"],
                    filter: filter,
                    index: parseInt(response.issues.skip) + 1,
                    count: parseInt(response.issues.count),
                    search: search,
                })));
            }).
            fail(FAIL).
            fail(loadingDone);
        });
    },

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        GET("tt", "tt").
        fail(FAIL).
        fail(() => {
            history.back();
        }).
        done(modules.tt.tt).
        done(() => {
            switch (params.action) {
                case "create":
                    modules.tt.issue.createIssueForm(params.project, params.workflow);
                    break;
                default:
                    loadingDone();
                    page404();
                    break;
            }
        });
    },
}).init();