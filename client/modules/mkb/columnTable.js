({
    init: function () {
        moduleLoaded("mkb.columnTable", this);
    },

    route: function (params) {
        $("#altForm").hide();

        $("#mainForm").html("");

        POST("mkb", "cards", false, { query: { $text: { $search: params.search } }, skip: 0, limit: 1 }).done(console.log)

        loadingDone();
    },

    search: function (search) {
        navigateUrl("mkb.columnTable", { search }, { run: true });
    },
}).init();
