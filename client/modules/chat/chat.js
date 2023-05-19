({
    init: function () {
        leftSide("far fa-fw fa-comments", i18n("chat.chat"), "?#chat", "tt");
        moduleLoaded("chat", this);
    },

    route: function (params) {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("chat.chat");

        $("#mainForm").html(i18n("chat.chat"));

        loadingDone();
    },
}).init();