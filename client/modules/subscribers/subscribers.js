({
    init: function () {
        leftSide("fas fa-fw fa-mobile-alt", i18n("subscribers.subscribers"), "#subscribers");
        moduleLoaded("subscribers", this);
    },

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        document.title = i18n("windowTitle") + " :: " + i18n("subscribers.subscribers");

        $("#mainForm").html(nl2br(i18n("subscribers.subscribers")));

        loadingDone();
    },
}).init();