({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.issue", this);
    },

    createIssue: function () {
        loadingStart();
        GET("tt", "tt", false, true).
        done(window.modules["tt"].tt).
        done(() => {

            function workflowsByProject(project) {
                let w = [
                    {
                        id: "",
                        text: "-",
                    }
                ];

                if (project) {
                    for (let i in window.modules["tt"].meta.projects) {
                        if (window.modules["tt"].meta.projects[i].projectId == project) {
                            for (let j in window.modules["tt"].meta.projects[i].workflows) {
                                let a = "";
                                for (let k in window.modules["tt"].meta.workflowAliases) {
                                    if (window.modules["tt"].meta.workflowAliases[k].workflow == window.modules["tt"].meta.projects[i].workflows[j]) {
                                        a = window.modules["tt"].meta.workflowAliases[k].alias;
                                        break;
                                    }
                                }
                                w.push({
                                    id: window.modules["tt"].meta.projects[i].workflows[j],
                                    text: $.trim(a + " [" + window.modules["tt"].meta.projects[i].workflows[j] + "]"),
                                    selected: $.cookie("lastIssueWorkflow") == window.modules["tt"].meta.projects[i].workflows[j],
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
            })

            for (let i in window.modules["tt"].meta.projects) {
                projects.push({
                    id: window.modules["tt"].meta.projects[i].projectId,
                    text: $.trim(window.modules["tt"].meta.projects[i].project + " [" + window.modules["tt"].meta.projects[i].acronym + "]"),
                    selected: $.cookie("lastIssueProject") == window.modules["tt"].meta.projects[i].projectId,
                });
            }

            let project = $.cookie("lastIssueProject")?$.cookie("lastIssueProject"):"";

            window.modules["tt"].meta.projects
            cardForm({
                title: i18n("tt.createIssue"),
                footer: true,
                borderless: true,
                noHover: true,
                topApply: true,
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
                                language: window.lang["_code"],
                            });
                        }
                    },
                    {
                        id: "workflow",
                        type: "select2",
                        title: i18n("tt.workflow"),
                        minimumResultsForSearch: Infinity,
                        options: workflowsByProject(project),
                    },
                ],
                callback: function (result) {
                    if (result.project && result.workflow) {
                        $.cookie("lastIssueProject", result.project, { expires: 36500 });
                        $.cookie("lastIssueWorkflow", result.workflow, { expires: 36500 });
                    }
                    window.location.href = `#tt.issue&action=create&project=${result.project}&workflow=${result.workflow}`;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone)
    },

    createIssueForm(project, workflow) {
        loadingStart();
        QUERY("tt", "workflowCreateIssueTemplate", {
            workflow: workflow,
        }).
        done(response => {
            console.log(response);

            document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssue");

            let h = '';

            let projectName = "";
            for (let i in window.modules["tt"].meta.projects) {
                if (window.modules["tt"].meta.projects[i].projectId == project) {
                    projectName = $.trim(window.modules["tt"].meta.projects[i].project + " [" + window.modules["tt"].meta.projects[i].acronym + "]");
                }
            }

            let workflowName = "";
            for (let i in window.modules["tt"].meta.workflowAliases) {
                if (window.modules["tt"].meta.workflowAliases[i].workflow == workflow) {
                    workflowName = $.trim(window.modules["tt"].meta.workflowAliases[i].alias + " [" + workflow + "]");
                }
            }

            h += `<div class="card mt-2">`;
            h += `<div class="card-header">`;
            h += `<h3 class="card-title">`;
            h += i18n("tt.createIssueTitle");
            h += `</h3>`;
            h += `</div>`;
            h += `<div class="card-body table-responsive p-0">`;
            h += `<table class="table table-hover tform-borderless">`;

            h += `<tbody>`;

            h += `
            <tr>
                <td class="tdform">${i18n("tt.project")}</td>
                <td class="tdform-right">
                    <input type="text" class="form-control" disabled="disabled" value="${projectName}">
                </td>
            </tr>
        `;

            h += `
            <tr>
                <td class="tdform">${i18n("tt.workflow")}</td>
                <td class="tdform-right">
                    <input type="text" class="form-control" disabled="disabled" value="${workflowName}">
                </td>
            </tr>
        `;

            h += `
            <tr>
                <td class="tdform">${i18n("tt.subject")}</td>
                <td class="tdform-right">
                    <input id="newIssueSubject" type="text" class="form-control" autocomplete="off" placeholder="${i18n("tt.subject")}">
                </td>
            </tr>
        `;

            h += `
            <tr>
                <td class="tdform-top">${i18n("tt.description")}</td>
                <td class="tdform-right">
                    <textarea id="newIssueDescription" class="form-control" autocomplete="off" placeholder="${i18n("tt.description")}"></textarea>
                </td>
            </tr>
        `;

            h += `
            <tr>
                <td class="tdform">${i18n("tt.tags")}</td>
                <td class="tdform-right">
                    <select id="newIssueTags" class="form-control" multiple="multiple"></select>
                </td>
            </tr>
        `;

            h += `</tbody>`;

            h += `<tfoot>`;
            h += `<tr>`;
            h += `<td colspan="2">`;
            h += `<button type="submit" class="btn btn-primary modalFormOk">${i18n("tt.createIssue")}</button>`;
            h += `<button type="cancel" class="btn btn-default float-right modalFormCancel">${i18n("cancel")}</button>`;
            h += `</td>`;
            h += `</tr>`;
            h += `</tfoot>`;

            h += `</table>`;
            h += `</div>`;
            h += `</div>`;

            $("#mainForm").html(h);

            $(".modalFormCancel").off("click").on("click", () => {
                history.back();
            });

            $("#newIssueDescription").summernote({
                tabDisable: false,
                tabsize: 4,
                height: 300,
                minHeight: null,
                maxHeight: null,
                disableResizeEditor: true,
                lang: (window.lang["_code"] === "ru") ? "ru-RU" : "en-US",
                toolbar: [
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                ],
            });
            $('.note-statusbar').hide();

            $("#newIssueTags").select2({
                tags: true,
                language: window.lang["_code"],
                placeholder: i18n("tt.tags"),
            });

            $(".newIssueSelect2").select2({
                language: window.lang["_code"],
            });

            loadingDone();
        }).
        fail(FAIL).
        fail(() => {
            window.history.back();
        });
    },

    route: function (params) {
        $("#altForm").hide();

        GET("tt", "tt").
        fail(FAIL).
        fail(() => {
            window.history.back();
        }).
        done(window.modules["tt"].tt).
        done(() => {
            switch (params.action) {
                case "create":
                    window.modules["tt.issue"].createIssueForm(params.project, params.workflow);
                    break;
                default:
                    loadingDone();
                    page404();
                    break;
            }
        });
    },
}).init();