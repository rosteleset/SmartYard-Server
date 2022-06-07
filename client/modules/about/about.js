({
    init: function () {
        leftSide("fas fa-fw fa-info-circle", i18n("about.about"), "#about", true);
        moduleLoaded("about", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.about");
        $("#topMenuLeftDynamic").html(`<li class="ml-2 nav-item d-none d-sm-inline-block text-bold text-lg">${i18n("about.about")}</li>`);

        $("#mainForm").html(nl2br(i18n("about.text")));
        loadingDone();

        modalUpload([ "image/jpeg", "image/png", "application/pdf" ], 2 * 1024 * 1024, '/server/');

    },
}).init();