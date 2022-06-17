({
    init: function () {
        leftSide("fas fa-fw fa-door-open", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    domophones: false,

    addDomophone: function () {

    },

    modifyDomophone: function (domophoneId) {

    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("domophones.domophones");


        GET("domophones", "domophones", false, true).
        done(response => {
            $("#altForm").hide();

            modules["domophones"].domophones = response.domophones;

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("domophones.domophones"),
                    button: {
                        caption: i18n("domophones.addDomophone"),
                        click: modules["domophones"].addDomophone(),
                    },
                    filter: true,
                },
                edit: modules["domophones"].modifyDomophone,
                columns: [
                    {
                        title: i18n("domophones.domophoneId"),
                    },
                    {
                        title: i18n("domophones.ip"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in response.domophones) {
                        rows.push({
                            uid: response.domophones[i].domophoneId,
                            cols: [
                                {
                                    data: response.domophones[i].domophoneId,
                                },
                                {
                                    data: response.domophones[i].domophoneIp,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();