({
    init: function () {
        leftSide("fas fa-fw fa-door-open", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    domophones: false,

    doAddDomophone: function (ip) {

    },

    addDomophone: function () {
        cardForm({
            title: i18n("domophones.addDomophone"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: [
                {
                    id: "enabled",
                    type: "yesno",
                    title: i18n("domophones.enabled"),
                    value: "1",
                },
                {
                    id: "model",
                    type: "text",
                    title: i18n("domophones.model"),
                    placeholder: i18n("domophones.model"),
                },
                {
                    id: "cms",
                    type: "text",
                    title: i18n("domophones.cms"),
                    placeholder: i18n("domophones.cms"),
                },
                {
                    id: "ip",
                    type: "text",
                    title: i18n("domophones.ip"),
                    placeholder: "IP",
                },
                {
                    id: "port",
                    type: "text",
                    title: i18n("domophones.port"),
                    placeholder: i18n("domophones.port"),
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("domophones.credentials"),
                    placeholder: i18n("domophones.credentials"),
                },
                {
                    id: "callerId",
                    type: "text",
                    title: i18n("domophones.callerId"),
                    placeholder: i18n("domophones.callerId"),
                },
                {
                    id: "comment",
                    type: "text",
                    title: i18n("domophones.comment"),
                    placeholder: i18n("domophones.comment"),
                },
                {
                    id: "locksDisabled",
                    type: "yesno",
                    title: i18n("domophones.locksDisabled"),
                    value: "0",
                },
                {
                    id: "cmsLevels",
                    type: "text",
                    title: i18n("domophones.cmsLevels"),
                    placeholder: i18n("domophones.cmsLevels"),
                },
            ],
            callback: result => {
                modules["domophones"].doAddDomophone(result.ip);
            },
        });
    },

    modifyDomophone: function (domophoneId) {

    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("domophones.domophones");

        GET("domophones", "domophones", false, true).
        done(response => {
            $("#altForm").hide();

            modules["domophones"].meta = response.domophones;

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("domophones.domophones"),
                    button: {
                        caption: i18n("domophones.addDomophone"),
                        click: modules["domophones"].addDomophone,
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

                    for (let i in modules["domophones"].meta.domophones) {
                        rows.push({
                            uid: modules["domophones"].meta.domophones[i].domophoneId,
                            cols: [
                                {
                                    data: modules["domophones"].meta.domophones[i].domophoneId,
                                },
                                {
                                    data: modules["domophones"].meta.domophones[i].domophoneIp,
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