({
    meta: [],

    init: function () {
        if (AVAIL("queues", "queues")) {
            leftSide("far fa-fw fa-clock", i18n("queues.queues"), "?#queues", "households");
        }
        moduleLoaded("queues", this);
    },

    queues: function (queues) {
        modules.queues.meta = queues["queues"];
    },

    renderQueues: function () {
        loadingStart();

        GET("queues", "queues", false, true).
        then(result => {
            modules.queues.queues(result);

            cardTable({
                target: "#mainForm",
                columns: [
                    {
                        title: i18n("queues.taskId"),
                    },
                    {
                        title: i18n("queues.objectType"),
                    },
                    {
                        title: i18n("queues.objectId"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.queues.meta) {
                        rows.push({
                            uid: modules.queues.meta[i].taskId,
                            cols: [
                                {
                                    data: modules.queues.meta[i].taskId,
                                },
                                {
                                    data: i18n(modules.queues.meta[i].objectType),
                                    nowrap: true,
                                },
                                {
                                    data: modules.queues.meta[i].objectId,
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

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("queues.queues");

        $("#altForm").hide();
        subTop();

        modules.queues.renderQueues(params);
    },
}).init();
