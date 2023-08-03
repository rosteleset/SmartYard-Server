({
    init: function () {
//        leftSide("fas fa-fw fa-mail-bulk blink-icon notifications", i18n("notifications.notifications"), "?#notifications", "tt");
        moduleLoaded("notifications", this);
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("notifications.notifications");

        $("#mainForm").html(i18n("notifications.notifications"));

        loadingDone();
    },
}).init();