({
    init: function () {
        leftSide("fas fa-fw fa-door-open", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("domophones.domophones");


        GET("domophones", "domophones", false, true).
        done(response => {
            $("#mainForm").html(nl2br(i18n("domophones.domophones")));
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();