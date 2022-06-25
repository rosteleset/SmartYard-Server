({
    init: function () {
        leftSide("fas fa-fw fa-key", i18n("keys.keys"), "#keys");
        moduleLoaded("keys", this);
    },

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        document.title = i18n("windowTitle") + " :: " + i18n("keys.keys");

        $("#mainForm").html(nl2br(i18n("keys.keys")));

        loadingDone();
    },
}).init();