({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.settings", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.settings");

        $("#topMenuLeftDynamic").html(`
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#tt.settings&edit=projects" class="nav-link">${i18n("tt.projects")}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#tt.settings&edit=types" class="nav-link">${i18n("tt.types")}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link text-primary text-bold">${i18n("tt.statuses")}</span>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#tt.settings&edit=resolutions" class="nav-link">${i18n("tt.resolutions")}</a>
            </li>
        `);

        let h = '';

        $("#mainForm").html(h);

        loadingDone();
    },
}).init();