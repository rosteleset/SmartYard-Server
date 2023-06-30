({
    menuItem: false,

    init: function () {
        if (parseInt(myself.uid)) {
            this.menuItem = leftSide("fas fa-fw fa-tablets", i18n("tt.workspaces"), "?#tt.workspaces", "tt");
        }
        moduleLoaded("tt.workspaces", this);
    },

    modifyWorkspace: function(workspace) {

    },

    renderWorkspaces: function(params) {
        loadingStart();

        let rtd = "";
        rtd += `<div class="form-inline"><div class="input-group input-group-sm"><select id="ttWorkspaceSelect" class="form-control" style="width: 259px;">`;
        rtd += `</select></div>`;

        rtd += `<div class="nav-item mr-0 pr-0 align-middle"><span id="addWorkspace" class="nav-link text-success mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.addWorkspace")}"><i class="fas fa-lg fa-fw fa-plus-square"></i></span></div>`;
        rtd += `<div class="nav-item mr-0 pr-0"><span id="editWorkspace" class="nav-link text-primary mr-0 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.editWorkspace")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></div>`;
        rtd += `<div class="nav-item mr-0 pr-0"><span id="deleteWorkspace" class="nav-link text-danger mr-1 pr-0" role="button" style="cursor: pointer" title="${i18n("tt.deleteWorkspace")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></div>`;

        $("#rightTopDynamic").html(rtd);

        $("#mainForm").html("");
        $("#altForm").html("");

        QUERY("files", "files", {
            type: "workspace",
            withContent: true,
        }).
        fail(FAIL).
        fail(loadingDone).
        done(result => {
            console.log(result.files);

            let showAlt = false;
            let workspace = [];
            let currentWorkspace = params.workspace?params.workspace:lStore("_tt_workspace");
            lStore("_tt_workspace", currentWorkspace);

/*
            let workspace = [
                {
                    project: "RTL",
                    filter: "all",
                },
                {
                    target: "right",
                    project: "RTL",
                    filter: "my",
                },
                {
                    project: "RTL",
                    filter: "delayed",
                },
            ];
*/

            let h = "";
            for (let i in result.files) {
                if (result.files[i].filename == currentWorkspace) {
                    h += "<option selected='selected'>" + escapeHTML(result.files[i].filename) + "</option>";
                    try {
                        workspace = JSON.parse(result.files[i].file);
                    } catch (e) {
                        FAIL();
                        workspace = [];
                    }
                } else {
                    h += "<option>" + escapeHTML(result.files[i].filename) + "</option>";
                }
            }

            if (!result.files.length) {
                $("#mainForm").html(`<div class="mt-2 ml-2">${i18n("tt.noWorkspacesAvailable")}</>`);
            } else {
                if (!workspace.length) {
                    $("#mainForm").html(`<div class="mt-2 ml-2">${i18n("tt.noWorkspaceAvailable")}</>`);
                }
            }

            for (let i in workspace) {
                if (workspace[i].target == "right") {
                    showAlt = true;
                }
            }
    
            if (showAlt) {
                $("#altForm").show();
            } else {
                $("#altForm").hide();
            }
    
            (function loadWorkspace() {
                let block = workspace.shift();
    
                if (block) {
                    modules.tt.renderIssues({
                        project: block.project,
                        filter: block.filter,
                    }, $((block.target === "right")?"#altForm":"#mainForm"), md5(guid()), loadWorkspace);
                } else {
                    loadingDone();
                }
            })();
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