({
    menuItem: false,

    demoWorkspace: {
        name: "workspace",
        workspace: [
            {
                project: "RTL",
                filter: "all",
            },
            {
                right: true,
                project: "RTL",
                filter: "my",
            },
            {
                project: "RTL",
                filter: "opened",
            },
        ],
    },

    init: function () {
        if (parseInt(myself.uid) && AVAIL("tt", "tt")) {
            this.menuItem = leftSide("fas fa-fw fa-tablets", i18n("tt.workspaces"), "?#tt.workspaces", "tt");
        }
        moduleLoaded("tt.workspaces", this);
    },

    renderWorkspaces: function(params) {
        loadingStart();

        $("#mainForm").html("");
        $("#altForm").html("");

        QUERY("files", "files", {
            type: "workspace",
            withContent: true,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(result => {
            document.title = i18n("windowTitle") + " :: " + i18n("tt.workspaces");

            let showAlt = false;
            let workspace = [];
            let currentWorkspace = params.workspace ? params.workspace : lStore("ttWorkspace");
            if (!currentWorkspace && result.files.length) {
                currentWorkspace = result.files[0].filename;
            }
            lStore("ttWorkspace", currentWorkspace);

            let rtd = "";

            rtd += '<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="ttWorkspaceSelect" class="form-control select-arrow right-top-select top-input"></select></div></div></form>';

            rtd += `<li class="nav-item nav-item-back-hover"><span id="editWorkspace" class="nav-link pointer" role="button" title="${i18n("tt.addModifyWorkspace")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></li>`;

            if (currentWorkspace) {
                rtd += `<li class="nav-item nav-item-back-hover"><span id="deleteWorkspace" class="nav-link pointer" role="button" title="${i18n("tt.deleteWorkspace")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></li>`;
            }

            $("#rightTopDynamic").html(rtd);

            let h = "";
            for (let i in result.files) {
                if (result.files[i].filename == currentWorkspace) {
                    h += "<option selected='selected'>" + escapeHTML(result.files[i].filename) + "</option>";
                    try {
                        workspace = JSON.parse(result.files[i].file).workspace;
                    } catch (e) {
                        FAIL();
                        workspace = [];
                    }
                } else {
                    h += "<option>" + escapeHTML(result.files[i].filename) + "</option>";
                }
            }

            $("#ttWorkspaceSelect").attr("disabled", !h);

            if (!h) {
                h = "<option>" + i18n("tt.noWorkspacesAvailable") + "</option>";
            }

            $("#ttWorkspaceSelect").html(h);

            if (!result.files.length) {
                $("#mainForm").html(`<div class="mt-2 ml-2">${i18n("tt.noWorkspacesAvailable")}</>`);
            } else {
                if (!workspace.length) {
                    $("#mainForm").html(`<div class="mt-2 ml-2">${i18n("tt.noWorkspaceAvailable")}</>`);
                }
            }

            for (let i in workspace) {
                if (workspace[i].right) {
                    showAlt = true;
                }
            }

            if (showAlt) {
                $("#altForm").show();
            } else {
                $("#altForm").hide();
            }

            let _workspace = JSON.parse(JSON.stringify(workspace));

            (function loadWorkspace() {
                let block = _workspace.shift();

                if (block) {
                    let query = {
                        project: block.project,
                        filter: block.filter,
                        class: block.class,
                        limit: block.limit,
                    };

                    if (lStore("sortBy:" + block.filter)) {
                        query.sort = lStore("sortBy:" + block.filter);
                    }

                    modules.tt.renderIssues(query, $(block.right ? "#altForm" : "#mainForm"), md5(guid()), loadWorkspace);
                } else {
                    loadingDone();
                }
            })();

            $("#ttWorkspaceSelect").off("change").on("change", () => {
                modules.tt.workspaces.renderWorkspaces({
                    workspace: $("#ttWorkspaceSelect").val(),
                });
            });

            $("#editWorkspace").off("click").on("click", () => {
                cardForm({
                    title: i18n("tt.addModifyWorkspace"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("addModify"),
                    size: "xl",
                    noHover: true,
                    singleColumn: true,
                    fields: [
                        {
                            id: "code",
                            type: "code",
                            language: "json",
                            height: 2 * (window.innerHeight / 3),
                            value: JSON.stringify(workspace.length?{ name: currentWorkspace, workspace: workspace }:modules.tt.workspaces.demoWorkspace, null, 4),
                            validate: w => {
                                try {
                                    w = JSON.parse(w);
                                } catch (_) {
                                    return false;
                                }
                                if (w.name && typeof w.name == "string" && w.workspace && w.workspace.length) {
                                    return true;
                                } else {
                                    return false;
                                }
                            },
                        },
                    ],
                    callback: f => {
                        loadingStart();
                        let w = JSON.parse(f.code);
                        PUT("files", "file", false, {
                            type: "workspace",
                            filename: w.name,
                            file: f.code,
                        }).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            lStore("ttWorkspace", w.name);
                            modules.tt.workspaces.renderWorkspaces({
                                workspace: w.name,
                            });
                        });
                    },
                });
            });

            $("#deleteWorkspace").off("click").on("click", () => {
                if (currentWorkspace) {
                    mConfirm(i18n("tt.confirmWorkspaceDelete", currentWorkspace), i18n("confirm"), i18n("delete"), () => {
                        loadingStart();
                        DELETE("files", "file", false, {
                            type: "workspace",
                            filename: currentWorkspace,
                        }).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            lStore("ttWorkspace", null);
                            modules.tt.workspaces.renderWorkspaces({
                                workspace: null,
                            });
                        });
                    });
                }
            });
        });
    },

    route: function (params) {
        if (modules.tt.workspaces.menuItem) {
            $("#" + modules.tt.workspaces.menuItem).children().first().attr("href", "?#tt.workspaces&_=" + Math.random());
        }

        subTop();

        if (parseInt(myself.uid)) {
            if (modules.groups) {
                modules.users.loadUsers(() => {
                    modules.groups.loadGroups(() => {
                        modules.tt.workspaces.renderWorkspaces(params);
                    });
                });
            } else {
                modules.users.loadUsers(() => {
                    modules.tt.workspaces.renderWorkspaces(params);
                });
            }
        } else {
            window.location.href = "?#tt.settings";
        }
    },

    search: function (s) {
        modules.tt.search(s);
    },
}).init();