({
    init: function () {
        leftSide("fab fa-fw fa-intercom", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("domophones.domophones");

        $("#topMenuLeftDynamic").html(`<li class="ml-2 nav-item d-none d-sm-inline-block text-bold text-lg">${i18n("domophones.domophones")}</li>`);

        $("#mainForm").html(nl2br(i18n("domophones.domophones")));

        loadingDone();
    },
}).init();