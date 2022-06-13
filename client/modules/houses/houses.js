({
    init: function () {
        moduleLoaded("houses", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("houses.houses");

        $("#mainForm").html(nl2br(i18n("houses.houses")));

        loadingDone();
    },
}).init();