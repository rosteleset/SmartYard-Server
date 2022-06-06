({
    meta: {},

    init: function () {
        if (AVAIL("tt", "tt")) {
            leftSide("fas fa-fw fa-tasks", i18n("tt.tt"), "#tt");
        }
        loadSubModules("tt", [
            "issue",
            "settings",
        ], () => {
            moduleLoaded("tt", this);
        })
    },

    tt: function (tt) {
        window.modules["tt"].meta = tt["meta"];
        console.log(window.modules["tt"].meta);
    },

    route: function (params) {
        $("#altForm").hide();

        $("#topMenuLeftDynamic").html(`
            <li class="nav-item d-none d-sm-inline-block">
                <a href="javascript:void()" class="nav-link text-success text-bold createIssue">${i18n("tt.createIssue")}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#tt.settings&edit=projects" class="nav-link">${i18n("tt.settings")}</a>
            </li>
        `);

        $(".createIssue").off("click").on("click", window.modules["tt.issue"].createIssue);

        document.title = i18n("windowTitle") + " :: " + i18n("tt.tt");
        $("#mainForm").html(i18n("tt.tt"));

        loadingDone();
    }
}).init();