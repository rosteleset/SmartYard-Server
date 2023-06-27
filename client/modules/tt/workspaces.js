({
    menuItem: false,

    init: function () {
        if (parseInt(myself.uid)) {
            this.menuItem = leftSide("fas fa-fw fa-tablets", i18n("tt.workspaces"), "?#tt.workspaces", "tt");
        }
        moduleLoaded("tt.workspaces", this);
    },

    renderWorkspaces: function(params) {
        let showAlt = false;

        let workspace = [
            {
                target: "#mainForm",
                project: "RTL",
                filter: "all",
            },
            {
                target: "#altForm",
                project: "RTL",
                filter: "my",
            },
            {
                target: "#mainForm",
                project: "RTL",
                filter: "delayed",
            },
        ];

        for (let i in workspace) {
            if (workspace[i].target == "#altForm") {
                showAlt = true;
            }
        }

        if (showAlt) {
            $("#altForm").show();
        }

        $("#mainForm").html("");
        $("#altForm").html("");

        (function loadWorkspace() {
            let block = workspace.shift();

            if (block) {
                modules.tt.renderIssues({
                    project: block.project,
                    filter: block.filter,
                }, $(block.target), md5(guid()), loadWorkspace);
            } else {
                loadingDone();
            }
        })();
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