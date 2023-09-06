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
        if (parseInt(myself.uid)) {
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
            let currentWorkspace = params.workspace?params.workspace:lStore("_tt_workspace");
            if (!currentWorkspace && result.files.length) {
                currentWorkspace = result.files[0].filename;
            }
            lStore("_tt_workspace", currentWorkspace);

            let rtd = "";
            rtd += `<div class="form-inline"><div class="input-group input-group-sm"><select id="ttWorkspaceSelect" class="form-control select-arrow" style="width: 259px;">`;
            rtd += `</select></div>`;
    
            rtd += `<div class="nav-item mr-0 pr-0"><span id="editWorkspace" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.addModifyWorkspace")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></div>`;
            if (currentWorkspace) {
                rtd += `<div class="nav-item mr-0 pr-0"><span id="deleteWorkspace" class="nav-link text-danger mr-1 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.deleteWorkspace")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></div>`;
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
                    modules.tt.renderIssues({
                        project: block.project,
                        filter: block.filter,
                    }, $(block.right?"#altForm":"#mainForm"), md5(guid()), loadWorkspace);
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
                            lStore("_tt_workspace", w.name);
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
                            lStore("_tt_workspace", null);
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

        $("#subTop").html("");

        if (modules.tt.menuItem) {
            $("#" + modules.tt.menuItem).children().first().attr("href", "?#tt&_=" + Math.random());
        }

        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
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
        }).
        fail(FAIL).
        fail(loadingDone);
    },
}).init();