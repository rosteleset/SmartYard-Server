({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    callsLoaded: false,

    createIssue: function (current_project, parent) {
        let projects = [];

        projects.push({
            id: "-",
            text: "-",
        });

        for (let i in modules.tt.meta.projects) {
            projects.push({
                id: modules.tt.meta.projects[i].acronym,
                text: $.trim(modules.tt.meta.projects[i].project ? modules.tt.meta.projects[i].project : modules.tt.meta.projects[i].acronym),
                selected: current_project == modules.tt.meta.projects[i].acronym || lStore("ttProject") == modules.tt.meta.projects[i].acronym,
            });
        }

        for (let i in projects) {
            if (projects[i].selected) {
                current_project = projects[i].id;
            }
        }

        let workflows = [];

        for (let i in modules.tt.meta.workflows) {
            workflows[i] = modules.tt.meta.workflows[i].name ? modules.tt.meta.workflows[i].name : i;
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
                            let wn = $.trim(workflows[modules.tt.meta.projects[i].workflows[j]] ? workflows[modules.tt.meta.projects[i].workflows[j]] : modules.tt.meta.projects[i].workflows[j]);
                            if (wn.charAt(0) != "#") {
                                w.push({
                                    id: modules.tt.meta.projects[i].workflows[j],
                                    text: wn,
                                    selected: lStore("ttWorkflow") == modules.tt.meta.projects[i].workflows[j],
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
                        return !!$(`#${prefix}catalog`).attr("disabled") || (!!v && v !== '-' && v !== 'undefined');
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
                    lStore("ttProject", result.project);
                    lStore("ttWorkflow", result.workflow);
                }
                modules.tt.issue.createIssueForm(result.project, result.workflow, result.catalog, (!!parent) ? encodeURIComponent(parent) : "");
            },
        });
    },

    createIssueForm: function (current_project, workflow, catalog, parent) {
        subTop();

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
                    if (fi && kx[i] !== "comment" && kx[i] !== "optionalComment") {
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
                        window.location.href = "?#tt&_=" + Math.random();
                    },
                }).show();

                loadingDone();
            }).
            fail(FAIL).
            fail(() => {
                window.location.href = "?#tt&_=" + Math.random();
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
            window.location.href = navigateUrl("tt", { issue: result.id });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    issueAction: function (issueId, action, callback, prefferredValues, timeout) {
        loadingStart();
        GET("tt", "issue", issueId, true).
        fail(FAIL).
        fail(loadingDone).
        done(response => {
            let issue = response.issue;
            QUERY("tt", "action", {
                _id: issue.issue.issueId,
                action: action,
            }, true).done(r => {
                if (r && r.template) {
                    if (typeof r.template == "string") {
                        if (r.template == "!") {
                            // action without accept
                            PUT("tt", "action", issue.issue.issueId, {
                                action: action,
                            }).
                            fail(FAIL).
                            always(() => {
                                if (typeof callback === "function") {
                                    callback();
                                }
                            });
                        } else {
                            if (modules.custom && typeof modules.custom[r.template] == "function") {
                                modules.custom[r.template](issue.issue, action, callback, prefferredValues, timeout);
                            } else {
                                loadingDone();
                                error(i18n("errors.functionNotFound", r.template), i18n("error"), 30);
                            }
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

                        loadingDone();

                        if (n) {
                            cardForm({
                                title: issueId,
                                apply: modules.tt.displayAction(action),
                                fields: fields,
                                footer: true,
                                borderless: true,
                                size: "lg",
                                timeout: timeout,
                                callback: r => {
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
                            cardForm({
                                title: issueId,
                                apply: modules.tt.displayAction(action),
                                fields: [
                                    {
                                        id: "confirm",
                                        type: "none",
                                        title: modules.tt.displayAction(action) + "?",
                                    }
                                ],
                                footer: true,
                                borderless: true,
                                size: "lg",
                                timeout: timeout,
                                noHover: true,
                                callback: () => {
                                    loadingStart();
                                    PUT("tt", "action", issue.issue.issueId, {
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
                        }
                    }
                } else {
                    loadingDone();
                    error(i18n("tt.actionNotAvailable"), 30);
                }
            }).
            fail(FAIL).
            fail(loadingDone);
        });
    },

    renderIssue: function (issue, filter, search) {

        function commentsMenu() {
            let options = [];
            let c = project.comments.split("\n");
            for (let i in c) {
                options.push({
                    id: parseInt(i) + 100,
                    text: c[i],
                });
            }

            let value = [];
            if (lStore("ttCommentsFilter")) {
                value = lStore("ttCommentsFilter");
            }

            cardForm({
                title: i18n("tt.commentsHideFilter"),
                footer: true,
                borderless: true,
                noFocus: true,
                noHover: true,
                singleColumn: true,
                fields: [
                    {
                        id: "comments",
                        type: "multiselect",
                        options,
                        value,
                    },
                ],
                callback: r => {
                    let h = [];
                    for (let i in r.comments) {
                        h.push(parseInt(r.comments[i]));
                    }
                    lStore("ttCommentsFilter", h);
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);
                },
            });
        }

        modules.tt.issue.callsLoaded = false;

        let count = false;
        let index = false;
        let next = false;
        let prev = false;

        if (filter) {
            let f = lStore("ttIssueFilterList:" + filter);

            if (f) {
                count = f.length;
                for (let i in f) {
                    i = parseInt(i);
                    if (issue && issue.issue && issue.issue.issueId && f[i] == issue.issue.issueId) {
                        next = f[i + 1];
                        prev = f[i - 1];
                        index = i + 1;
                        break;
                    }
                }
            }
        }

        $("#leftTopDynamic").html("");

        search = ($.trim(search) && typeof search === "string") ? $.trim(search) : "";

        try {
            document.title = issue.issue["issueId"] + ": " + issue.issue["subject"];
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

        let noJournal = [ "updated" ];
        for (let i in project.customFieldsNoJournal) {
            for (let j in modules.tt.meta.customFields) {
                if (project.customFieldsNoJournal[i] == modules.tt.meta.customFields[j].customFieldId) {
                    noJournal.push("_cf_" + modules.tt.meta.customFields[j].field);
                }
            }
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
                        h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${modules.tt.issueFieldTitle(issue.fields[i])}' /></td></tr>`;
                        h += "<tr>";
                        h += "<td colspan='2' style='width: 100%;' class='pl-1'>";
                        h += x;
                        h += "</td>";
                        h += "</tr>";
                    }
                }
            }

            return h;
        }

        let h = "";

        h += "<div>";
        h += "<table class='mt-2 ml-2' style='width: 100%;'>";
        h += "<tr>";
        h += "<td style='vertical-align: top; width: 100%;'>";
        h += "<div class='text-bold pt-1 pb-1'>";
        h += "<span class='mr-3'>";
        let url = new URL(window.location.href);
        url = url.origin + url.pathname + "?#tt&issue=" + issue.issue["issueId"];
        h += "<span class='cc hover pointer fas fa-fw fa-link mr-2' id='issueIssueIdUrl' data-clipboard-target='#issueIssueIdUrl' data-clipboard-text='" + url + "' title='" + i18n("tt.issueIdUrl") + "'></span>";
        h += "<span class='cc hover pointer' id='issueIssueIdText' data-clipboard-target='#issueIssueIdText' data-clipboard-text='" + issue.issue["issueId"] + "' title='" + i18n("tt.issueIdText") + "'>" + issue.issue["issueId"] + "</span>";
        if (!isEmpty(issue.actions)) {
            h += ":";
        }
        h += "</span>";

        if (!isEmpty(issue.actions)) {
            let t = 0;
            let la = false;
            for (let i in issue.actions) {
                if (issue.actions[i].substring(0, 1) === "!") {
                    if (modules.tt.specialActions.indexOf(issue.actions[i].substring(1)) >= 0) {
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
                if (modules.tt.specialActions.indexOf(la) >= 0) {
                    h += `<span class="hoverable text-primary mr-3 tt${la.charAt(0).toUpperCase() + la.substring(1)}">${i18n("tt." + la)}</span>`;
                } else {
                    h += `<span class="hoverable text-primary mr-3 ttIssueAction">${la}</span>`;
                }
            } else
            if (t < Object.keys(issue.actions).length) {
                h += `<span class="dropdown" data-toggle="dropdown">`;
                h += `<span class="pointer hoverable dropdown-toggle dropdown-toggle-no-icon text-primary mr-3" id="ttIssueAllActions" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-flip="false">${i18n("tt.allActions")}</span>`;
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
                    if (modules.tt.specialActions.indexOf(a) >= 0) {
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

        if (issue.print && config.printServer) {
            h += `<span class="dropdown">`;
            h += `<span class="pointer hoverable dropdown-toggle dropdown-toggle-no-icon text-primary mr-3" id="ttIssuePrint" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">${i18n("tt.print")}</span>`;
            h += `<ul class="dropdown-menu" aria-labelledby="ttIssuePrint">`;
            for (let i in issue.print) {
                for (let j in modules.tt.meta.prints) {
                    if (issue.print[i] == modules.tt.meta.prints[j].formName) {
                        h += `<li class="pointer dropdown-item ttIssuePrint" data-print="${modules.tt.meta.prints[j].printId}">${modules.tt.meta.prints[j].description}</li>`;
                        break;
                    }
                }
            }
            h += '</ul>';
            h += '</span>';
        }

        if (AVAIL("cdr", "cdr", "POST") && modules.tt.cdr && modules.tt.cdr.hasCdr(issue.issue) && !modules.tt.issue.callsLoaded) {
            h += `<span class="hoverable text-primary mr-3 ttCalls">${i18n("tt.calls")}</span>`;
        }

        if (issue.showJournal) {
            h += `<span class="hoverable text-primary mr-3 ttJournal">${i18n("tt.journal")}</span>`;
        }

        if (AVAIL("tt", "json", "GET")) {
            h += `<span class="hoverable text-primary mr-3 ttJSON">JSON</span>`;
        }

        h += "</div>";
        h += "</td>";
        h += "<td style='text-align: right;' class='pr-2' nowrap>";
        if (index && count && index !== true && count !== true) {
            if (parseInt(index) > 1) {
                h += "<i id='stepPrev' class='fas fa-fw fa-chevron-left pointer'></i>"
            } else {
                h += "<i class='fas fa-fw fa-chevron-left text-muted'></i>"
            }
            h += `<span class='hoverable pointer ml-2 mr-2' id='stepOf' title='${i18n("tt.backToList")}'>${index} ${i18n("tt.of")} ${count} </span>`;
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
        h += "</table>";
        h += "</div>";

        h += "<div>";
        h += "<table class='mt-1 ml-2' style='width: 100%;'>";
        if (!isEmpty(issue.issue.tags)) {
            h += "<tr>";
            h += "<td style='vertical-align: top; width: 100%;' colspan='2'>";
            h += "<div class='pt-1 pb-1 small'>";
            for (let i in issue.issue.tags) {
                let fg = (tags[issue.issue.tags[i]] && tags[issue.issue.tags[i]].foreground) ? tags[issue.issue.tags[i]].foreground : "#666666";
                let bg = (tags[issue.issue.tags[i]] && tags[issue.issue.tags[i]].background) ? tags[issue.issue.tags[i]].background : "#ffffff";
                h += `<span class="mr-1 text-bold" style='border: solid thin #cbccce; padding-left: 6px; padding-right: 5px; padding-top: 2px; padding-bottom: 2px; color: ${fg}; border-radius: 4px; background: ${bg};'><i class="fas fa-tag mr-2"></i>${escapeHTML(issue.issue.tags[i])}</span>`;
            }
            h += "</div>";
            h += "</td>";
            h += "</tr>";
        }

        h += "<tr>";
        h += "<td style='vertical-align: top; width: 100%;'>";
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
            members[modules.users.meta[i].login] = modules.users.meta[i].realName ? modules.users.meta[i].realName : modules.users.meta[i].login;
        }

        if (issue.issue.attachments && Object.keys(issue.issue.attachments).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.attachments")}' /></td></tr>`;
            h += '<tbody class="gallery">';
            for (let i in issue.issue.attachments) {
                h += "<tr>";
                h += "<td colspan='2' class='pl-1'>";
                h += "<div>";
                h += "<span class='text-bold'>";
                h += members[issue.issue.attachments[i].metadata.attachman] ? members[issue.issue.attachments[i].metadata.attachman] : issue.issue.attachments[i].metadata.attachman;
                h += "</span>";
                h += "&nbsp;" + i18n("tt.wasAttached") + "&nbsp;";
                h += ttDate(issue.issue.attachments[i].metadata.added);
                if (modules.tt.meta.myRoles[issue.issue.project] >= 20 && !modules.tt.meta.finalStatus[issue.issue.status]) {
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.attachments[i].metadata.attachman == lStore("_login")) {
                        h += "<i class='far fa-trash-alt ml-2 pointer text-danger deleteFile'></i>";
                    }
                }
                h += "</div>";
                h += "<div class='ml-2 mb-2 mt-1'>";
                let ref = lStore("_server") + "/tt/file?issueId=" + encodeURIComponent(issue.issue["issueId"]) + "&filename=" + encodeURIComponent(issue.issue.attachments[i].filename) + "&_token=" + encodeURIComponent(lStore("_token"));
                if (issue.issue.attachments[i].metadata.type.indexOf("image/") == 0) {
                    h += `<span>${$.trim(issue.issue.attachments[i].filename)}</span>`;
                    h += "<br />";
                    h += `<a class='gallery-link' href='${ref}'>`;
                    h += `<img class='gallery-image' src="${ref}" style="height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;"></img>`;
                    h += '</a>'
                } else {
                    h += `<a class='hoverable' href='${ref}' target='_blank'>`;
                    h += $.trim(issue.issue.attachments[i].filename);
                    h += "</a>";
                }
                h += "</div>";
                h += "</td>";
                h += "</tr>";
            }
            h += "</tbody>";
        }

        if (issue.issue.childrens && issue.issue.childrens.issues && Object.keys(issue.issue.childrens.issues).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.subIssues")}' /></td></tr>`;
            h += "<tr>";
            h += "<td colspan='2' class='pl-1'>";
            h += '<table class="datatable">';

            h += '<thead>';
            h += '<tr>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.issue") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.created") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.author") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.status") + '</th>';
            h += '<th class="pl-2 pr-2">' + i18n("tt.subject") + '</th>';
            h += '</tr>';
            h += '</thead>';

            h += '<tbody>';
            for (let i in issue.issue.childrens.issues) {
                h += "<tr>";
                h += `<td><a class='hoverable pl-2 pr-2 ildc' href='?#tt&issue=${issue.issue.childrens.issues[i].issueId}'>${issue.issue.childrens.issues[i].issueId}</a></td>`;
                h += `<td class='pl-2 pr-2 ildc'>${ttDate(issue.issue.childrens.issues[i].created, true)}</td>`;
                h += `<td class='pl-2 pr-2 ildc'>${modules.tt.issueField2Html(issue.issue.childrens.issues[i], "author", undefined, "left")}</td>`;
                h += `<td class='pl-2 pr-2 ildc'>${modules.tt.issueField2Html(issue.issue.childrens.issues[i], "status", undefined, "left")}</td>`;
                h += `<td class='pl-2 pr-2'>${issue.issue.childrens.issues[i].subject}</td>`;
                h += "</tr>";
            }
            h += '</tbody>';

            h += "</table>";
            h += "</td>";
            h += "</tr>";
        }

        if (issue.issue.linkedIssues && issue.issue.linkedIssues.issues && Object.keys(issue.issue.linkedIssues.issues).length) {
            h += `<tr><td colspan='2' style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.links")}' /></td></tr>`;
            h += "<tr>";
            h += "<td colspan='2' class='pl-1'>";

            h += '<table class="datatable">';

            h += '<thead>';
            h += '<tr>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.issue") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.created") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.author") + '</th>';
            h += '<th class="pl-2 pr-2 ildc">' + i18n("tt.status") + '</th>';
            h += '<th class="pl-2 pr-2">' + i18n("tt.subject") + '</th>';
            h += '<th class="pl-2 pr-2">&nbsp;</th>';
            h += '</tr>';
            h += '</thead>';

            h += '<tbody>';

            for (let i in issue.issue.linkedIssues.issues) {
                h += "<tr>";
                h += `<td><a class='hoverable pl-2 pr-2 ildc' href='?#tt&issue=${issue.issue.linkedIssues.issues[i].issueId}'>${issue.issue.linkedIssues.issues[i].issueId}</a></td>`;
                h += `<td class='pl-2 pr-2 ildc'>${ttDate(issue.issue.linkedIssues.issues[i].created, true)}</td>`;
                h += `<td class='pl-2 pr-2 ildc'>${modules.tt.issueField2Html(issue.issue.linkedIssues.issues[i], "author", undefined, "left")}</td>`;
                h += `<td class='pl-2 pr-2 ildc'>${modules.tt.issueField2Html(issue.issue.linkedIssues.issues[i], "status", undefined, "left")}</td>`;
                h += `<td class='pl-2 pr-2'>${issue.issue.linkedIssues.issues[i].subject}</td>`;
                h += `<td class='pl-2 pr-2'><i class='fas fa-fw fa-unlink pointer text-danger unlinkIssue' data-issueId='${issue.issue.linkedIssues.issues[i].issueId}' title='${i18n("tt.unlinkIssuesTitle")}'></i></td>`;
                h += "</tr>";
            }

            h += '</tbody>';
            h += "</table>";
            h += "</td>";
            h += "</tr>";
        }

        h += "</table>";

        h += "<table style='width: 100%;' class='ml-2' id='issueComments'>";

        let hc = 0;

        if (issue.issue.comments && Object.keys(issue.issue.comments).length) {
            let cts = [];
            try {
                cts = project.comments.split("\n");
            } catch (_) {
                //
            }
            let cf = lStore("ttCommentsFilter");
            if (project.comments && project.comments.split("\n").length > 0 && trim(project.comments.split("\n")[0])) {
                h += `<tr><td style="width: 100%"><hr class='hr-text-pointer mt-1 mb-1 commentsMenu' data-content='&#x2630; ${i18n("tt.comments")}' /></td></tr>`;
            } else {
                h += `<tr><td style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.comments")}' /></td></tr>`;
            }
            for (let i in issue.issue.comments) {
                let ct = 0;
                if (cts.indexOf(issue.issue.comments[i].type) >= 0) {
                    ct = cts.indexOf(issue.issue.comments[i].type) + 100;
                }
                if (!cf || !Array.isArray(cf) || cf.indexOf(ct) < 0) {
                    h += "<tr class='issueComment' data-type='" + ct + "' data-time='" + issue.issue.comments[i].created + "' data-date='" + date("d-m-Y H:i:s", issue.issue.comments[i].created) + "'>";
                    h += "<td class='pl-1'>";
                    h += "<div>";
                    h += "<span class='text-bold'>";
                    h += members[issue.issue.comments[i].author] ? members[issue.issue.comments[i].author] : issue.issue.comments[i].author;
                    h += "</span>";
                    h += "&nbsp;";
                    h += i18n("tt.commented");
                    h += "&nbsp;";
                    h += ttDate(issue.issue.comments[i].created);
                    if (issue.issue.comments[i].private) {
                        h += "<i class='ml-2 fas fa-fw fa-eye-slash text-warning'></i>";
                    } else {
                        h += "<i class='ml-2 fas fa-fw fa-eye text-success'></i>";
                    }
                    if (modules.tt.meta.myRoles[issue.issue.project] >= 20 && !modules.tt.meta.finalStatus[issue.issue.status]) {
                        if (modules.tt.meta.myRoles[issue.issue.project] >= 70 || issue.issue.comments[i].author == lStore("_login")) {
                            h += `<i class='far fa-fw fa-edit ml-2 pointer text-primary modifyComment' data-index='${i}'></i>`;
                        }
                    }
                    h += "</div>";
                    h += "<div class='ml-2 mb-2 mt-1'>";
                    h += convertLinks(nl2br($.trim(escapeHTML(issue.issue.comments[i].body))));
                    h += "</div>";
                    h += "</td>";
                    h += "</tr>";
                } else {
                    hc++;
                }
            }
        }

        h += "</table>";

        h += "<table style='width: 100%; display: none;' class='ml-2' id='issueJournal'>";
        h += "</table>";
        h += "</div>";

        $("#mainForm").html(h);

        if (hc) {
            $(".commentsMenu").attr("data-content", "☰ " + i18n("tt.commentsHidden", hc));
        }

        $(".ttIssue").off("click").on("click", function () {
            // window.location.href = "?#tt&issue=" + encodeURIComponent($(this).text());
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
                    $(".ttCalls").hide();
                    $("#issueComments").hide();
                    let h = '';
                    h += `<tr><td style='width: 100%' colspan='2'><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.journal")}' /></td></tr>`;
                    let jf = true;
                    for (let i in response.journal) {
                        let o = response.journal[i].old && typeof response.journal[i].old.length == 'undefined';
                        let n = response.journal[i].new && typeof response.journal[i].new.length == 'undefined';
                        if (!o && !n) {
                            continue;
                        }
                        let action = response.journal[i].action.split("#")[0];
                        let indx = parseInt(response.journal[i].action.split("#")[1]) + 1;
                        h += "<tr>";
                        if (jf) {
                            jf = false;
                            h += "<td class='pl-1' colspan='2'>";
                        } else {
                            h += "<td class='pl-1 pt-3' colspan='2'>";
                        }
                        h += "<div>";
                        h += "<span class='text-bold'>";
                        h += members[response.journal[i].login] ? members[response.journal[i].login] : response.journal[i].login;
                        h += "</span>";
                        h += "&nbsp;";
                        h += i18n("tt.journalAction" + action.charAt(0).toUpperCase() + action.substring(1), indx);
                        h += "&nbsp;";
                        h += ttDate(response.journal[i].date);
                        h += "</div>";
                        h += "</td>";
                        h += "</tr>";

                        if (o && n) {
                            let k = Object.keys(response.journal[i].old);
                            k = k.concat(Object.keys(response.journal[i].new));
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                if (noJournal.indexOf(k[j]) >= 0) continue;

                                let oo = jShow(response.journal[i].old[k[j]]) ? modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].old[k[j]], "journal") : "&nbsp;";
                                let nn = jShow(response.journal[i].new[k[j]]) ? modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].new[k[j]], "journal") : "&nbsp;";

                                if (oo == nn) {
                                    continue;
                                }

                                if (k[j] == "workflowAction") {
                                    h += "<tr class='tr-hoverable'>";
                                    h += "<td class='pl-2 td-journal nowrap'>";
                                    h += modules.tt.issueFieldTitle(k[j]) + ": ";
                                    h += "</td>";
                                    h += "<td class='pl-2 td-journal' style='width: 100%; font-weight: bold;'>";
                                    h += nn;
                                    h += "</td>";
                                    h += "</tr>";
                                } else {
                                    if (oo != "" && oo != "&nbsp;") {
                                        h += "<tr class='tr-hoverable'>";
                                        h += "<td class='pl-2 td-journal nowrap'>";
                                        h += modules.tt.issueFieldTitle(k[j]) + " (" + i18n("tt.old") + "): ";
                                        h += "</td>";
                                        h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                        h += oo;
                                        h += "</td>";
                                        h += "</tr>";
                                    }
                                    if (nn != "" && nn != "&nbsp;") {
                                        h += "<tr class='tr-hoverable'>";
                                        h += "<td class='pl-2 td-journal nowrap'>";
                                        h += modules.tt.issueFieldTitle(k[j]) + " (" + i18n("tt.new") + "): ";
                                        h += "</td>";
                                        h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                        h += nn;
                                        h += "</td>";
                                        h += "</tr>";
                                    }
                                }
                            }
                        }

                        if (!o && n) {
                            let k = Object.keys(response.journal[i].new);
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                if (noJournal.indexOf(k[j]) >= 0) continue;
                                    h += "<tr class='tr-hoverable'>";
                                    h += "<td class='pl-2 td-journal nowrap'>";
                                    h += modules.tt.issueFieldTitle(k[j]) + ": ";
                                    h += "</td>";
                                    h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                    h += modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].new[k[j]], "journal");
                                    h += "</td>";
                                    h += "</tr>";
                            }
                        }

                        if (o && !n) {
                            let k = Object.keys(response.journal[i].old);
                            k = [...new Set(k)].sort();
                            for (let j in k) {
                                if (noJournal.indexOf(k[j]) >= 0) continue;

                                h += "<tr class='tr-hoverable'>";
                                h += "<td class='pl-2 td-journal nowrap'>";
                                h += modules.tt.issueFieldTitle(k[j]) + " (" + i18n("tt.old") + "): ";
                                h += "</td>";
                                h += "<td class='pl-2 td-journal' style='width: 100%;'>";
                                h += modules.tt.issueField2Html(issue.issue, k[j], response.journal[i].old[k[j]], "journal");
                                h += "</td>";
                                h += "</tr>";
                            }
                        }
                    }
                    $("#issueJournal").html(h).show();
                }).
                always(loadingDone);
            } else {
                $(".ttJournal").text(i18n("tt.journal"));
                $("#issueJournal").hide();
                $("#issueComments").show();
                if (AVAIL("cdr", "cdr", "POST") && modules.tt.cdr && modules.tt.cdr.hasCdr(issue.issue) && !modules.tt.issue.callsLoaded) {
                    $(".ttCalls").show();
                }
            }
        });

        $(".ttCalls").off("click").on("click", () => {
            loadingStart();
            modules.tt.cdr.cdr(issue.issue).
            fail(FAIL).
            fail(() => {
                loadingDone();
            }).
            done(result => {
                $(".ttCalls").hide();
                modules.tt.issue.callsLoaded = true;
                if (result.cdr && result.cdr.length) {
                    if ($("#issueComments").text()) {
                        for (let i in result.cdr) {
                            let comments = $(".issueComment");

                            let h = "<tr class='issueComment' data-time='" + result.cdr[i].start + "' data-date='" + date("d-m-Y H:i:s", result.cdr[i].start) + "'>";
                            h += "<td class='pl-1'>";
                            h += "<div>";
                            h += "<span class='text-bold'>";
                            h += i18n("tt.call");
                            h += "</span>";
                            h += " ";
                            h += result.cdr[i].src + "&nbsp;>>>&nbsp;" + result.cdr[i].dst + ", " + result.cdr[i].billsec + " " + i18n("tt.sec");
                            h += "&nbsp;";
                            h += ttDate(result.cdr[i].start);
                            h += "</div>";

                            h += "<div class='ml-2 mb-1 mt-1'>";
                            h += `<audio src='${result.cdr[i].file}' controls='true' preload='none'>`;
                            h += "</div>";
                            h += "</td>";
                            h += "</tr>";

                            let first = comments.first();
                            let f = false;

                            if (parseInt(result.cdr[i].start) < parseInt(first.attr("data-time"))) {
                                $(h).insertBefore(first);
                                f = true;
                            } else {
                                comments.each(function () {
                                    let comment = $(this);
                                    if ((parseInt(result.cdr[i].start) > parseInt(comment.attr("data-time"))) && (parseInt(result.cdr[i].start) <= parseInt(comment.next().attr("data-time")))) {
                                        $(h).insertAfter(comment);
                                        f = true;
                                        return false;
                                    }
                                });
                            }

                            if (!f) {
                                $(h).insertAfter(comments.last());
                            }
                        }
                    } else {
                        let h = '';
                        if (project.comments && project.comments.split("\n").length > 0 && trim(project.comments.split("\n")[0])) {
                            h += `<tr><td style="width: 100%"><hr class='hr-text-pointer mt-1 mb-1 commentsMenu' data-content='&#x2630; ${i18n("tt.comments")}' /></td></tr>`;
                        } else {
                            h += `<tr><td style="width: 100%"><hr class='hr-text mt-1 mb-1' data-content='${i18n("tt.comments")}' /></td></tr>`;
                        }
                        for (let i in result.cdr) {
                            h += "<tr class='issueComment' data-time='" + result.cdr[i].start + "' data-date='" + date("d-m-Y H:i:s", result.cdr[i].start) + "'>";
                            h += "<td class='pl-1'>";
                            h += "<div>";
                            h += "<span class='text-bold'>";
                            h += i18n("tt.call");
                            h += "</span>";
                            h += " ";
                            h += result.cdr[i].src + "&nbsp;>>>&nbsp;" + result.cdr[i].dst + ", " + result.cdr[i].billsec + " " + i18n("tt.sec");
                            h += "&nbsp;";
                            h += ttDate(result.cdr[i].start);
                            h += "</div>";
                            h += "<div class='ml-2 mb-2 mt-1'>";
                            h += `<audio src='${result.cdr[i].file}' controls='true' preload='none'>`;
                            h += "</div>";
                            h += "</td>";
                            h += "</tr>";
                        }
                        $("#issueComments").html(h);
                        $(".commentsMenu").off("click").on("click", commentsMenu);
                    }
                    message(i18n("tt.callsLoaded", result.cdr.length));
                } else {
                    warning(i18n("tt.callsNotFound"));
                }
                loadingDone();
            });
        });

        $(".ttJSON").off("click").on("click", () => {
            window.location.href = "?#tt.json&issue=" + issue.issue.issueId;
        });

        $(".ttIssueAction").off("click").on("click", function () {
            let action = $(this).text();

            modules.tt.issue.issueAction(issue.issue.issueId, action, () => {
                window.location.href = refreshUrl();
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
                        window.location.href = refreshUrl();
                    });
                },
            });
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
                            window.location.href = refreshUrl();
                        });
                    } else {
                        loadingStart();
                        PUT("tt", "comment", false, result).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            window.location.href = refreshUrl();
                        });
                    }
                },
            });
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
                            window.location.href = refreshUrl();
                        });
                    }
                },
            });
        });

        $(".ttSaAddSingleFile").off("click").on("click", () => {
            let maxSize = parseInt(project.maxFileSize);

            let files = [];

            loadFile(false, maxSize, file => {
                if (file) {
                    files.push(file);
                    loadingStart();
                    POST("tt", "file", false, { issueId: issue.issue.issueId, attachments: files }).
                    fail(FAIL).
                    fail(loadingDone).
                    done(() => {
                        window.location.href = refreshUrl();
                    });
                }
            });
        });

        $(".ttSaAddSingleFileQuiet").off("click").on("click", () => {
            let maxSize = parseInt(project.maxFileSize);

            let files = [];

            loadFile(false, maxSize, file => {
                if (file) {
                    files.push(file);
                    loadingStart();
                    POST("tt", "file", false, { issueId: issue.issue.issueId, attachments: files }).
                    fail(FAIL).
                    fail(loadingDone).
                    done(() => {
                        window.location.href = refreshUrl();
                    });
                }
            }, false, true);
        });

        $(".ttSaAddMultipleFilesQuiet").off("click").on("click", () => {
            let maxSize = parseInt(project.maxFileSize);

            loadFiles(false, maxSize, files => {
                if (files) {
                    loadingStart();
                    POST("tt", "file", false, { issueId: issue.issue.issueId, attachments: files }).
                    fail(FAIL).
                    fail(loadingDone).
                    done(() => {
                        window.location.href = refreshUrl();
                    });
                }
            });
        });

        $(".deleteFile").off("click").on("click", function () {
            let file = $.trim($(this).parent().next().text());
            mConfirm(i18n("tt.deleteFile", file), i18n("confirm"), i18n("delete"), () => {
                loadingStart();
                DELETE("tt", "file", false, {
                    issueId: issue.issue.issueId,
                    filename: file,
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    message(i18n("tt.fileWasDeleted"));
                    window.location.href = refreshUrl();
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
                    window.location.href = refreshUrl();
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
                    window.location.href = refreshUrl();
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
                size: "xl",
                fields: [
                    {
                        id: "issueId",
                        type: "select2",
                        title: i18n("tt.issue"),
                        multiple: true,
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
                        window.location.href = refreshUrl();
                    });
                },
            });
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
                    window.location.href = refreshUrl();
                });
            });
        });

        $(".ttSaCoordinate").off("click").on("click", () => {
            lStore("_coordinate_issue", issue.issue["issueId"]);
            window.location.href = "?#cs";
        });

        $(".ttIssuePrint").off("click").on("click", function () {
            let printId = $(this).attr("data-print");
            loadingStart();
            QUERY("tt", "prints", {
                "mode": "data",
                "_id": printId,
            }, true).
            fail(FAIL).
            fail(() => {
                loadingDone();
            }).
            done(r => {
                try {
                    (new Function ("issue", "callback", r.data))(issue.issue, data => {
                        loadingStart();
                        POST("tt", "printIssue", printId, {
                            "data": data,
                        }).
                        fail(FAIL).
                        done(r => {
                            if (r && r.file) {
                                let link = document.createElement('a');
                                link.href = trim(config.printServer, "/") + "/" + r.file;
                                link.target = "_blank";
                                link.click();
                            }
                        }).
                        always(() => {
                            loadingDone();
                        });
                    });
                } catch (e) {
                    loadingDone();
                    error(i18n("errors.errorInFunction"), i18n("error"), 30);
                }
            });
        });

        $(".commentsMenu").off("click").on("click", commentsMenu);

        $("#stepPrev").off("click").on("click", () => {
            loadingStart();
            window.location.href = navigateUrl("tt", {
                issue: prev,
                filter: filter,
                search: search,
            });
        });

        $("#stepNext").off("click").on("click", () => {
            loadingStart();
            window.location.href = navigateUrl("tt", {
                issue: next,
                filter: filter,
                search: search,
            });
        });

        $("#stepOf").off("click").on("click", () => {
            loadingStart();
            modules.tt.selectFilter(filter, Math.floor((index - 1) / modules.tt.defaultIssuesPerPage) * modules.tt.defaultIssuesPerPage, modules.tt.defaultIssuesPerPage, search);
        });

        $(".gallery-image").off("load").on("load", function () {
            let img = $(this);
            img.parent().attr("data-pswp-width", img.get(0).naturalWidth);
            img.parent().attr("data-pswp-height", img.get(0).naturalHeight);
        });

        let lightbox = new PhotoSwipeLightbox({
            gallery: '.gallery',
            children: 'a.gallery-link',
            pswpModule: PhotoSwipe,
            showHideAnimationType: 'none',
        });

        lightbox.on('uiRegister', function() {
            lightbox.pswp.ui.registerElement({
                name: 'download-button',
                order: 8,
                isButton: true,
                tagName: 'a',

                html: '<i class="fas fa-fw fa-save" style="margin-top: 23px; margin-left: 18px; color: white;"></i>',

                onInit: (el, pswp) => {
                    el.setAttribute('download', '');
                    el.setAttribute('target', '_blank');
                    el.setAttribute('rel', 'noopener');

                    pswp.on('change', () => {
                        el.href = pswp.currSlide.data.src;
                    });
                }
            });

            lightbox.pswp.ui.registerElement({
                name: 'rotate-right',
                order: 8,
                isButton: true,
                tagName: 'div',

                html: '<i class="fas fa-fw fa-undo" style="margin-top: 23px; margin-left: 18px; color: white; -moz-transform: scaleX(-1); -o-transform: scaleX(-1); -webkit-transform: scaleX(-1); transform: scaleX(-1); filter: FlipH; -ms-filter: "FlipH";"></i>',

                onClick: () => {
                    const rotations = [ '', 'rotate(90deg)', 'rotate(180deg)', 'rotate(270deg)' ];
                    const currentSlide = lightbox.pswp.currSlide;
                    const imageElement = currentSlide.container.querySelector('img');

                    if (imageElement && rotations.indexOf(imageElement.style.transform) >= 0) {
                        imageElement.style.transform = rotations[(rotations.indexOf(imageElement.style.transform) + 1) % 4];
                    }
                }
            });

            lightbox.pswp.ui.registerElement({
                name: 'rotate-left',
                order: 8,
                isButton: true,
                tagName: 'div',

                html: '<i class="fas fa-fw fa-undo" style="margin-top: 23px; margin-left: 18px; color: white;"></i>',

                onClick: () => {
                    const rotations = [ '', 'rotate(270deg)', 'rotate(180deg)', 'rotate(90deg)' ];
                    const currentSlide = lightbox.pswp.currSlide;
                    const imageElement = currentSlide.container.querySelector('img');

                    if (imageElement && rotations.indexOf(imageElement.style.transform) >= 0) {
                        imageElement.style.transform = rotations[(rotations.indexOf(imageElement.style.transform) + 1) % 4];
                    }
                }
            });
        });

        lightbox.init();

        (new ClipboardJS('.cc', {
            text: function(trigger) {
                return trigger.getAttribute('data-clipboard-text');
            }
        })).on("success", () => {
            message(i18n("copied"), i18n("clipboard"), 3);
        });

        loadingDone();
    },

    search: function (s) {
        modules.tt.search(s);
    }
}).init();