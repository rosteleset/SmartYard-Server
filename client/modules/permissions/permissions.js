({
    init: function () {
        leftSide("fas fa-fw fa-balance-scale-right", i18n("permissions.permissions"), "#permissions");
        moduleLoaded("permissions", this);
    },

    /*
        main form (permissions) render function
     */

    render: function () {
        loadingStart();
        $("#mainForm").html(i18n("permissions.permissions"));
        loadingDone();
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("groups.groups");

        window.modules["permissions"].render();
    }
}).init();