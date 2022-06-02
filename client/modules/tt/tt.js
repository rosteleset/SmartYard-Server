({
    meta: {},

    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt");
        }
        loadSubModules("tt", [
            "createIssue",
            "settings",
        ], () => {
            moduleLoaded("tt", this);
        })
    },

    tt: function (tt) {
        window.modules["tt"].meta = tt["meta"];
    },

    route: function (params) {
        $("#altForm").hide();

        $("#topMenuLeftDynamic").html(`
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#tt.settings&edit=projects" class="nav-link">${i18n("tt.settings")}</a>
            </li>
        `);

        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
        $("#mainForm").html(i18n("tt.tt"));

        loadingDone();
    }
}).init();