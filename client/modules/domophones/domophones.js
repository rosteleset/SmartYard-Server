({
    init: function () {
        leftSide("fab fa-fw fa-intercom", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("domophones.domophones");

        $("#mainForm").html(nl2br(i18n("domophones.domophones")));

        loadingDone();
    },
}).init();