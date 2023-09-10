({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    createIssue: function (current_project, parent) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            let workflows = [];

            for (let i in modules.tt.meta.workflows) {
                workflows[i] = modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i;
            }

            function workflowsByProject(project) {
                let w = [
                    {
                        id: "-",
                        text: "-",
                    }
                ];

                if (project) {
                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].acronym == project) {
                            for (let j in modules.tt.meta.projects[i].workflows) {
                                let wn = $.trim(workflows[modules.tt.meta.projects[i].workflows[j]]?workflows[modules.tt.meta.projects[i].workflows[j]]:modules.tt.meta.projects[i].workflows[j]);
                                if (wn.charAt(0) != "#") {
                                    w.push({
                                        id: modules.tt.meta.projects[i].workflows[j],
                                        text: wn,
                                        selected: lStore("_workflow") == modules.tt.meta.projects[i].workflows[j],
                                    });
                                }
                            }
                            break;
                        }
                    }
                }

                return w;
            }

            function catalogByWorkflow(workflow, prefix) {
                let catalog = [{
                    id: "-",
                    text: "-",
                }];
                
                let x = false;
                
                for (let i in modules.tt.meta.workflows) {
                    if (i == workflow) {
                        if (modules.tt.meta.workflows[i].catalog) {
                            x = modules.tt.meta.workflows[i].catalog;
                        }
                        break;
                    }
                }

                if (x) {
                    let k = Object.keys(x);
                    k.sort();
                    for (let i in k) {
                        let u1 = x[k[i]];
                        let u2 = [];
                        let l2 = [];
                        for (let j in u1) {
                            u2.push(u1[j]);
                        }
                        u2.sort();
                        for (let j in u2) {
                            l2.push({
                                id: u2[j],
                                text: u2[j],
                            });
                        }
                        catalog.push({
                            text: k[i],
                            children: l2,
                        });
                    }
                }

                $(`#${prefix}catalog`).html("").select2({
                    data: catalog,
                    minimumResultsForSearch: 0,
                    language: lang["_code"],
                });

                return x;
            }

            let projects = [];

            projects.push({
                id: "-",
                text: "-",
            });

            for (let i in modules.tt.meta.projects) {
                projects.push({
                    id: modules.tt.meta.projects[i].acronym,
                    text: $.trim(modules.tt.meta.projects[i].project?modules.tt.meta.projects[i].project:modules.tt.meta.projects[i].acronym),
                    selected: current_project == modules.tt.meta.projects[i].acronym || lStore("_project") == modules.tt.meta.projects[i].acronym,
                });
            }

            cardForm({
                title: i18n("tt.createIssue"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
                apply: "create",
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
                            if (catalogByWorkflow($(`#${prefix}workflow`).val(), prefix)) {
                                $(`#${prefix}catalog`).attr("disabled", false);
                            } else {
                                $(`#${prefix}catalog`).attr("disabled", true);
                            }
                        },
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                        readonly: !!parent,
                    },
                    {
                        id: "workflow",
                        type: "select2",
                        title: i18n("tt.workflowName"),
                        minimumResultsForSearch: Infinity,
                        options: workflowsByProject(current_project),
                        select: (el, id, prefix) => {
                            if (catalogByWorkflow(el.val(), prefix)) {
                                $(`#${prefix}catalog`).attr("disabled", false);
                            } else {
                                $(`#${prefix}catalog`).attr("disabled", true);
                            }
                        },
                        validate: v => {
                            return v && v !== '-' && v !== 'undefined';
                        },
                    },
                    {
                        id: "catalog",
                        type: "select2",
                        title: i18n("tt.catalog"),
                        minimumResultsForSearch: Infinity,
                        validate: (v, prefix) => {
                            return $(`#${prefix}catalog`).attr("disabled") || (v && v !== '-' && v !== 'undefined');
                        },
                    },
                ],
                done: function (prefix) {
                    if (catalogByWorkflow($(`#${prefix}workflow`).val(), prefix)) {
                        $(`#${prefix}catalog`).attr("disabled", false);
                    } else {
                        $(`#${prefix}catalog`).attr("disabled", true);
                    }
                },
                callback: function (result) {
                    if (result.project && result.workflow) {
                        lStore("_project", result.project);
                        lStore("_workflow", result.workflow);
                    }
                    modules.tt.issue.createIssueForm(result.project, result.workflow, result.catalog, (!!parent)?encodeURIComponent(parent):"");
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone)
    },

    createIssueForm: function (current_project, workflow, catalog, parent) {
        loadingStart();
        GET("tt", "tt").
        fail(FAIL).
        fail(loadingDone).
        done(modules.tt.tt).
        done(() => {
            loadingDone();

            $("#leftTopDynamic").html("");
            $("#rightTopDynamic").html("");
    
            loadingStart();
    
            function ciForm(current_project, workflow, catalog, parent) {
                QUERY("tt", "issueTemplate", {
                    _id: workflow,
                    catalog: catalog,
                }, true).
                done(response => {
                    document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");
    
                    let workflows = [];
    
                    for (let i in modules.tt.meta.workflows) {
                        workflows[i] = (modules.tt.meta.workflows[i].name?modules.tt.meta.workflows[i].name:i);
                    }
    
                    let projectName = "";
                    let project = false;
                    let projectId = -1;
    
                    for (let i in modules.tt.meta.projects) {
                        if (modules.tt.meta.projects[i].acronym == current_project) {
                            project = modules.tt.meta.projects[i];
                            projectName = modules.tt.meta.projects[i].project?modules.tt.meta.projects[i].project:modules.tt.meta.projects[i].acronym;
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
                            title: i18n("tt.workflowName"),
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
    
                    if (catalog && catalog !== "-" && catalog !== true) {
                        fields.push({
                            id: "catalog",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.catalog"),
                            value: catalog,
                        });
                    }
    
                    if (parent && parent !== "-" && parent !== true) {
                        fields.push({
                            id: "parent",
                            type: "text",
                            readonly: true,
                            title: i18n("tt.parent"),
                            value: parent,
                        });
                    }
    
                    let kx = [];
                    let ky = {};
        
                    for (let i in response.template.fields) {
                        let fx = ((typeof response.template.fields[i] == "string")?response.template.fields[i]:i).toString();
                        if (fx.charAt(0) == '%') {
                            fx = fx.split('%');
                            kx[fx[1]] = fx[2];
                            ky[fx[2]] = (typeof response.template.fields[i] == "string")?false:response.template.fields[i];
                        } else {
                            kx.push(fx);
                            ky[fx] = (typeof response.template.fields[i] == "string")?false:response.template.fields[i];
                        }
                    }

                    for (let i in kx) {
                        let fi = modules.tt.issueField2FormFieldEditor(false, kx[i], projectId, ky[kx[i]]);
                        if (fi) {
                            fields.push(fi);
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
                            location.href = "?#tt&_=" + Math.random();
                        },
                    });
    
                    loadingDone();
                }).
                fail(FAIL).
                fail(() => {
                    location.href = "?#tt&_=" + Math.random();
                });
            }
    
            modules.users.loadUsers(() => {
                if (modules.groups) {
                    modules.groups.loadGroups(() => {
                        ciForm(current_project, workflow, catalog, parent);
                    });
                } else {
                    ciForm(current_project, workflow, catalog, parent);
                }
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

    issueAction: function (issueId, action, callback, prefferredValues, timeout) {
        loadingStart();
        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        fail(FAIL).
        fail(loadingDone).
        done(() => {
            GET("tt", "issue", issueId, true).
            fail(FAIL).
            fail(loadingDone).
            done(response => {
                let issue = response.issue;
                QUERY("tt", "action", {
                    _id: issue.issue.issueId,
                    action: action,
                }, true).done(r => {
                    if (typeof r.template == "string") {
                        if (modules.custom && typeof modules.custom[r.template] == "function") {
                            modules.custom[r.template](issue.issue, action, callback, prefferredValues, timeout);
                        } else {
                            error(i18n("errors.functionNotFound", r.template), i18n("error"), 30);
                        }
                    } else {
                        let fields = [];
        
                        let project;
            
                        for (let i in modules.tt.meta.projects) {
                            if (modules.tt.meta.projects[i].acronym == issue.issue.project) {
                                project = modules.tt.meta.projects[i];
                            }
                        }
            
                        let n = 0;
            
                        let kx = [];
                        let ky = {};
            
                        for (let i in r.template) {
                            let fx = ((typeof r.template[i] == "string")?r.template[i]:i).toString();
                            if (fx.charAt(0) == '%') {
                                fx = fx.split('%');
                                kx[fx[1]] = fx[2];
                                ky[fx[2]] = (typeof r.template[i] == "string")?false:r.template[i];
                            } else {
                                kx.push(fx);
                                ky[fx] = (typeof r.template[i] == "string")?false:r.template[i];
                            }
                        }
            
                        for (let i in kx) {
                            let fi = modules.tt.issueField2FormFieldEditor(issue.issue, kx[i], project.projectId, ky[kx[i]], prefferredValues ? prefferredValues[kx[i]] : prefferredValues);
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
                                n++;
                            }
                        }
            
                        if (n) {
                            cardForm({
                                title: action,
                                apply: action,
                                fields: fields,
                                footer: true,
                                borderless: true,
                                size: "lg",
                                timeout: timeout,
                                callback: r => {
                                    r["issueId"] = issue.issue.issueId;
                                    loadingStart();
                                    PUT("tt", "action", issue.issue.issueId, {
                                        set: r,
                                        action: action,
                                    }).
                                    fail(FAIL).
                                    always(() => {
                                        if (typeof callback === "function") {
                                            callback();
                                        }
                                    });
                                },
                            });
                        } else {
                            mConfirm(action + " \"" + issue.issue.issueId + "\"?", i18n("confirm"), action, () => {
                                loadingStart();
                                PUT("tt", "action", issue.issue.issueId, {
                                    set: {
                                        issueId: issue.issue.issueId,
                                    },
                                    action: action,
                                }).
                                fail(FAIL).
                                always(() => {
                                    if (typeof callback === "function") {
                                        callback();
                                    }
                                });
                            });
                        }
                    }
                }).
                fail(FAIL).
                always(loadingDone);
            });
        });
    },

    viewIssue: function (issue) {
        issue = JSON.parse(b64_to_utf8(issue));
        window.location.href = `?#tt&issue=${encodeURIComponent(issue.id)}&filter=${encodeURIComponent(issue.filter?issue.filter:"")}&index=${issue.index?issue.index:""}&count=${issue.count?issue.count:""}&search=${encodeURIComponent(($.trim(issue.search) && typeof issue.search === "string")?$.trim(issue.search):"")}&_=${Math.random()}`;
    },

    renderIssue: function (issue, filter, index, count, search) {
        $("#leftTopDynamic").html("");
        $("#rightTopDynamic").html("");

        search = ($.trim(search) && typeof search === "string")?$.trim(search):"";
        
        try {
            document.title = i18n("windowTitle") + " :: " + i18n("tt.tt") + " :: " + issue.issue["issueId"];
        } catch (e) {
            document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
            FAILPAGE();
            return;
        }

        let rightFields = [];

        let t = [];
        for (let i in issue.fields) {
            if (issue.fields[i].charAt(0) == '*') {
                t.push(issue.fields[i].substring(1));
                rightFields.push(issue.fields[i].substring(1));
            } else {
                t.push(issue.fields[i]);
            }
        }
        issue.fields = t;

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

        function fieldRow(i, target) {
            let h = '';

            if (![ "id", "issueId", "comments", "attachments", "childrens ", "links", "linkedIssues", "tags" ].includes(issue.fields[i])) {
                    let f = false;

                if (issue.fields[i].substring(0, 4) == "_cf_") {
                    for (let j in modules.tt.meta.customFields) {
                        if (issue.fields[i] == "_cf_" + modules.tt.meta.customFields[j].field && project.customFields.indexOf(modules.tt.meta.customFields[j].customFieldId) >= 0) {
                            f = true;
                            break;
                        }
                    }
                } else {
                    f = true;
                }

                if (f) {
                    let x = modules.tt.issueField2Html(issue.issue, issue.fields[i], undefined, target);
                    if (x) {
                        h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${modules.tt.issueFieldTitle(issue.fields[i])}' style="font-size: 11pt;"/></td></tr>`;
                        h += "<tr>";
                        h += "<td colspan='2' style='width: 100%; font-size: 12pt;' class='pl-1'>";
                        h += x;
                        h += "</td>";
                        h += "</tr>";
                    }
                }
            }

            return h;
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
            "saSubIssue",
            "saCoordinate",
            "saLink",
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
                if (specialActions.indexOf(la) >= 0) {
                    h += `<span class="hoverable text-primary mr-3 tt${la.charAt(0).toUpperCase() + la.substring(1)}">${i18n("tt." + la)}</span>`;
                } else {
                    h += `<span class="hoverable text-primary mr-3 ttIssueAction">${la}</span>`;
                }
            } else
            if (t < Object.keys(issue.actions).length) {
                h += `<span class="dropdown">`;
                h += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon text-primary mr-3" id="ttIssueAllActions" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">${i18n("tt.allActions")}</span>`;
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

        if (issue.showJournal) {
            h += `<span class="hoverable text-primary mr-3 ttJournal">${i18n("tt.journal")}</span>`;
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
                h += fieldRow(i, "left");
            }
        }
        h += "</table>";
        h += "</td>";
        h += "<td style='vertical-align: top;'>";
        h += "<table style='width: 300px;'>";
        for (let i in issue.fields) {
            if (rightFields.includes(issue.fields[i])) {
                h += fieldRow(i, "right");
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
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.attachments[i].metadata.attachman == lStore("_login")) {
                        h += "<i class='far fa-trash-alt ml-2 pointer text-danger deleteAttachment'></i>";
                    }
                }
                h += "</div>";
                h += "<div class='ml-2 mb-2 mt-1'>";
                h += "<a class='hoverable' href='" + lStore("_server") + "/tt/file?issueId=" + encodeURIComponent(issue.issue["issueId"]) + "&filename=" + encodeURIComponent(issue.issue.attachments[i].filename) + "&_token=" + encodeURIComponent(lStore("_token")) + "' target='_blank'>";
                if (issue.issue.attachments[i].metadata.type.indexOf("image/") == 0) {
                    h += $.trim(issue.issue.attachments[i].filename) + "<br />";
                    h += `<img src="${lStore("_server") + "/tt/file?issueId=" + encodeURIComponent(issue.issue["issueId"]) + "&filename=" + encodeURIComponent(issue.issue.attachments[i].filename) + "&_token=" + encodeURIComponent(lStore("_token"))}" style="height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;"></img>`;
                } else {
                    h += $.trim(issue.issue.attachments[i].filename);
                }
                h += "</a>";
                h += "</div>";
                h += "</td>";
                h += "</tr>";
            }
        }

        if (issue.issue.childrens && issue.issue.childrens.issues && Object.keys(issue.issue.childrens.issues).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.subIssues")}' style="font-size: 11pt;"/></td></tr>`;
            h += "<tr>";
            h += "<td colspan='2' class='pl-1' style='font-size: 14px;'>";
            h += "<table>";
            for (let i in issue.issue.childrens.issues) {
                h += "<tr>";
                h += `<td class='text-bold hoverable ttIssue'>${issue.issue.childrens.issues[i].issueId}</td>`;
                h += `<td class='pl-2'>${ttDate(issue.issue.childrens.issues[i].created, true)}</td>`;
                h += `<td class='pl-2'>${modules.tt.issueField2Html(issue.issue.childrens.issues[i], "author", undefined, "left")}</td>`;
                h += `<td class='pl-2'>${issue.issue.childrens.issues[i].subject}</td>`;
                h += `<td class='pl-2'>${modules.tt.issueField2Html(issue.issue.childrens.issues[i], "status", undefined, "left")}</td>`;
                h += "</tr>";
            }
            h += "</table>";
            h += "</td>";
            h += "</tr>";
        }

        if (issue.issue.linkedIssues && issue.issue.linkedIssues.issues && Object.keys(issue.issue.linkedIssues.issues).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.links")}' style="font-size: 11pt;"/></td></tr>`;
            h += "<tr>";
            h += "<td colspan='2' class='pl-1' style='font-size: 14px;'>";
            h += "<table>";
            for (let i in issue.issue.linkedIssues.issues) {
                h += "<tr>";
                h += `<td class='text-bold hoverable ttIssue'>${issue.issue.linkedIssues.issues[i].issueId}</td>`;
                h += `<td class='pl-2'>${ttDate(issue.issue.linkedIssues.issues[i].created, true)}</td>`;
                h += `<td class='pl-2'>${modules.tt.issueField2Html(issue.issue.linkedIssues.issues[i], "author", undefined, "left")}</td>`;
                h += `<td class='pl-2'>${issue.issue.linkedIssues.issues[i].subject}</td>`;
                h += `<td class='pl-2'>${modules.tt.issueField2Html(issue.issue.linkedIssues.issues[i], "status", undefined, "left")}</td>`;
                h += `<td class='pl-2'><i class='fas fa-fw fa-unlink pointer text-danger unlinkIssue' data-issueId='${issue.issue.linkedIssues.issues[i].issueId}'></i></td>`;
                h += "</tr>";
            }
            h += "</table>";
            h += "</td>";
            h += "</tr>";
        }

        h += "</table>";

        h += "<table style='width: 100%;' class='ml-2' id='issueComments'>";

        if (issue.issue.comments && Object.keys(issue.issue.comments).length) {
            h += `<tr><td style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.comments")}' style="font-size: 11pt;"/></td></tr>`;
            for (let i in issue.issue.comments) {
                h += "<tr class='issueComment' data-time='" + issue.issue.comments[i].created + "'>";
                h += "<td class='pl-1' style='font-size: 14px;'>";
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
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.comments[i].author == lStore("_login")) {
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

        h += "<table style='width: 100%; display: none;' class='ml-2' id='issueJournal'>";
        h += "</table>";

        $("#mainForm").html(h);

        $(".ttIssue").off("click").on("click", function () {
            location.href = "?#tt&issue=" + encodeURIComponent($(this).text());
        });

        $(".ttJournal").off("click").on("click", () => {
            function jShow(v) {
                if (typeof v == "undefined") {
                    return false;
                } else {
                    return v && v != null && v != "&nbsp;";
                }
            }

            if ($(".ttJournal").text() == i18n("tt.journal")) {
                loadingStart();
                GET("tt", "journal", issue.issue.issueId).
                done(response => {
                    $(".ttJournal").text(i18n("tt.comments"));
                    $("#issueComments").hide();
                    let h = '';
                    h += `<tr><td style="width: 100%" colspan="4"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.journal")}' style="font-size: 11pt;"/></td></tr>`;
                    let jf = true;
                    let c = 1;
                    for (let i in response.journal) {
                        let o = response.journal[i].old && typeof response.journal[i].old.length == 'undefined';
                        let n = response.journal[i].new && typeof response.journal[i].new.length == 'undefined';
                        if (!o && !n) {
                            continue;
                        }
                        let action = response.journal[i].action.split("#")[0];
                        let indx = parseInt(response.journal[i].action.split("#")[1]) + 1;
                        let sep = [ "modifyIssue", "modifyComment" ].includes(action.split("#")[0]) ? "<i class='fas fa-fw fa-arrow-right ml-2 mr-2'></i>" : "&nbsp;";
                        h += "<tr>";
                        if (jf) {
                            jf = false;
                            h += "<td class='pl-1' style='font-size: 14px;' colspan='4'>";
                        } else {
                            h += "<td class='pl-1 pt-3' style='font-size: 14px;' colspan='4'>";
                        }
                        h += "<div>";
                        h += "#" + c + " ";
                        c++;
                        h += ttDate(response.journal[i].date);
                        h += "<span class='ml-2 text-info text-bold'>";
                        h += members[response.journal[i].login]?members[response.journal[i].login]:response.journal[i].login;
                        h += "</span>";
                        h += "<span class='ml-2'>";
                        h += i18n("tt.journalAction" + action.charAt(0).toUpperCase() + action.substring(1), indx);
                        h += "</span>";
                        h += "</div>";
                        h += "</td>";
                        h += "</tr>";
                        if (o && n) {
                            let k = Object.keys(response.journal[i].old);
                            k = k.concat(Object.keys(response.journal[i].new));
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                let oo = jShow(response.journal[i].old[k[j]]) ? modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].old[k[j]], "journal") : "&nbsp;";
                                let nn = jShow(response.journal[i].new[k[j]]) ? modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].new[k[j]], "journal") : "&nbsp;";
                                if (oo == nn) {
                                    continue;
                                }
                                h += "<tr class='tr-hoverable'>";
                                h += "<td class='pl-2 td-journal'>";
                                h += modules.tt.issueFieldTitle(k[j]) + ": ";
                                h += "</td>";
                                h += "<td class='pl-2 td-journal'>";
                                h += oo;
                                h += "</td>";
                                h += "<td class='pl-2 td-journal'>" + sep + "</td>";
                                h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                h += nn;
                                h += "</td>";
                                h += "</tr>";
                            }
                        }
                        if (!o && n) {
                            let k = Object.keys(response.journal[i].new);
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                h += "<tr class='tr-hoverable'>";
                                h += "<td class='pl-2 td-journal'>";
                                h += modules.tt.issueFieldTitle(k[j]) + ": ";
                                h += "</td>";
                                if (sep == "&nbsp;") {
                                    h += "<td class='pl-2 td-journal' style='width: 100%;' colspan='3'>";
                                    h += modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].new[k[j]], "journal");
                                    h += "</td>";
                                } else {
                                    h += "<td class='pl-2 td-journal'>&nbsp;</td>";
                                    h += "<td class='pl-2 td-journal'>" + sep + "</td>";
                                    h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                    h += modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].new[k[j]], "journal");
                                    h += "</td>";
                                }
                                h += "</tr>";
                            }
                        }
                        if (o && !n) {
                            let k = Object.keys(response.journal[i].old);
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                h += "<tr class='tr-hoverable'>";
                                h += "<td class='pl-2 td-journal'>";
                                h += modules.tt.issueFieldTitle(k[j]) + ": ";
                                h += "</td>";
                                h += "<td class='pl-2 td-journal'>";
                                h += modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].old[k[j]], "journal");
                                h += "</td>";
                                h += "<td class='pl-2 td-journal'>" + sep + "</td>";
                                h += "<td class='pl-2 td-journal' style='width: 100%;'>&nbsp;</td>";
                                h += "</tr>";
                            }
                        }
                    }
                    $("#issueJournal").html(h).show();
                    $(".ttIssue").off("click").on("click", function () {
                        location.href = "?#tt&issue=" + encodeURIComponent($(this).text());
                    });
                }).
                always(loadingDone);          
            } else {
                $(".ttJournal").text(i18n("tt.journal"));
                $("#issueJournal").hide();
                $("#issueComments").show();
            }
        });

        $(".ttIssueAction").off("click").on("click", function () {
            let action = $(this).text();

            modules.tt.issue.issueAction(issue.issue.issueId, action, () => {
                modules.tt.route({
                    issue: issue.issue.issueId,
                    filter: filter,
                    index: index,
                    count: count,
                    search: search,
                });
            });
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
                    fail(loadingDone).
                    done(() => {
                        modules.tt.route({
                            issue: issue.issue.issueId,
                            filter: filter,
                            index: index,
                            count: count,
                            search: search,
                        });
                    });
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
                        fail(loadingDone).
                        done(() => {
                            modules.tt.route({
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
                            });
                        });
                    } else {
                        loadingStart();
                        PUT("tt", "comment", false, result).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            modules.tt.route({
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
                            });
                        });
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
                callback: result => {
                    if (result.attachments.length) {
                        loadingStart();
                        POST("tt", "file", false, result).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            modules.tt.route({
                                issue: issue.issue.issueId,
                                filter: filter,
                                index: index,
                                count: count,
                                search: search,
                            });
                        });
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
                fail(loadingDone).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                });
            });
        });

        $(".ttSaAssignToMe").off("click").on("click", () => {
            mConfirm(i18n("tt.confirmAssignToMe"), i18n("confirm"), i18n("tt.saAssignToMe"), () => {
                loadingStart();
                PUT("tt", "issue", issue.issue.issueId, {
                    "action": "assignToMe"
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                });
            });
        });

        $(".ttSaWatch").off("click").on("click", () => {
            mConfirm((issue && issue.issue && issue.issue.watchers && Object.values(issue.issue.watchers).indexOf(lStore("_login")) >= 0)?i18n("tt.confirmUnWatch"):i18n("tt.confirmWatch"), i18n("confirm"), i18n("tt.saWatch"), () => {
                loadingStart();
                PUT("tt", "issue", issue.issue.issueId, {
                    "action": "watch"
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                });
            });
        });

        $(".ttSaDelete").off("click").on("click", () => {
            mConfirm(i18n("tt.confirmDeleteIssue", issue.issue.issueId), i18n("confirm"), i18n("delete"), () => {
                loadingStart();
                DELETE("tt", "issue", issue.issue.issueId).
                fail(FAIL).
                done(() => {
                    window.location.href = "?#tt&_=" + Math.random();
                }).
                fail(loadingDone);
            });
        });

        $(".ttSaSubIssue").off("click").on("click", () => {
            modules.tt.issue.createIssue(issue.issue["project"], issue.issue["issueId"]);
        });

        $(".ttSaLink").off("click").on("click", () => {
            cardForm({
                title: i18n("tt.saLink"),
                footer: true,
                borderless: true,
                topApply: true,
                fields: [
                    {
                        id: "issueId",
                        type: "select2",
                        title: i18n("tt.issue"),
                        multiple: false,
                        options: [],
                        value: [],
                        validate: a => {
                            return !!a;
                        },
                        ajax: {
                            delay: 1000,
                            transport: function (params, success) {
                                if (params.data.term) {
                                    QUERY("tt", "issues", {
                                        project: issue.issue.project,
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
                                let suggestions = [];
                                for (let i in data.issues.issues) {
                                    suggestions.push({
                                        id: data.issues.issues[i].issueId,
                                        text: "[ " + data.issues.issues[i].issueId + " ] " + data.issues.issues[i].subject,
                                    });
                                }
                                return {
                                    results: suggestions,
                                };
                            },
                        },
                    }
                ],
                callback: f => {
                    loadingStart();
                    POST("tt", "link", issue.issue.issueId, {
                        issueId: f.issueId,
                    }).
                    fail(FAIL).
                    fail(loadingDone).
                    done(() => {
                        modules.tt.route({
                            issue: issue.issue.issueId,
                            filter: filter,
                            index: index,
                            count: count,
                            search: search,
                        });
                    });
                },
            }).show();
        });

        $(".unlinkIssue").off("click").on("click", function () {
            let issueId = $(this).attr("data-issueId");
            mConfirm(i18n("tt.unlinkIssues", issue.issue.issueId, issueId), i18n("confirm"), i18n("yes"), () => {
                loadingStart();
                DELETE("tt", "link", issue.issue.issueId, {
                    issueId: issueId,
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    modules.tt.route({
                        issue: issue.issue.issueId,
                        filter: filter,
                        index: index,
                        count: count,
                        search: search,
                    });
                });
            });
        });

        $(".ttSaCoordinate").off("click").on("click", () => {
            lStore("_coordinate_issue", issue.issue["issueId"]);
            location.href = "?#cs";
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

        loadingDone();
    },
}).init();