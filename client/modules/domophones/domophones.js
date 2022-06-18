({
    init: function () {
        leftSide("fas fa-fw fa-door-open", i18n("domophones.domophones"), "#domophones");
        moduleLoaded("domophones", this);
    },

    meta: false,

    doAddDomophone: function (domophone) {
        loadingStart();
        POST("domophones", "domophone", false, domophone).
        fail(FAIL).
        always(modules["domophones"].route);
    },

    doModifyDomophone: function (domophone) {
        loadingStart();
        PUT("domophones", "domophone", domophone.domophoneId, domophone).
        fail(FAIL).
        always(modules["domophones"].route);
    },

    doDeleteDomophone: function (domophoneId) {
        loadingStart();
        DELETE("domophones", "domophone", domophoneId).
        fail(FAIL).
        always(modules["domophones"].route);
    },

    addDomophone: function () {
        let models = [];
        let first;

        for (let id in modules["domophones"].meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules["domophones"].meta.models[id].title,
            })
        }

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
                    type: "select2",
                    title: i18n("domophones.model"),
                    placeholder: i18n("domophones.model"),
                    options: models,
                },
                {
                    id: "ip",
                    type: "text",
                    title: i18n("domophones.ip"),
                    placeholder: "IP",
                    validate: v => {
                        return !!ip2long(v);
                    },
                },
                {
                    id: "port",
                    type: "text",
                    title: i18n("domophones.port"),
                    placeholder: "80",
                    value: "80",
                    validate: v => {
                        return !!parseInt(v);
                    },
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
                    validate: v => {
                        return $.trim(v) !== "" && $.trim(v).length <= 32;
                    },
                },
                {
                    id: "comment",
                    type: "text",
                    title: i18n("domophones.comment"),
                    placeholder: i18n("domophones.comment"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
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
            callback: modules["domophones"].doAddDomophone,
        });
    },

    modifyDomophone: function (domophoneId) {
        let models = [];
        let first;

        for (let id in modules["domophones"].meta.models) {
            if (!first) {
                first = id;
            }
            models.push({
                id,
                text: modules["domophones"].meta.models[id].title,
            })
        }

        let domophone = false;

        for (let i in modules["domophones"].meta.domophones) {
            if (modules["domophones"].meta.domophones[i].domophoneId == domophoneId) {
                domophone = modules["domophones"].meta.domophones[i];
                break;
            }
        }

        if (domophone) {
            cardForm({
                title: i18n("domophones.modifyDomophone"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("domophones.deleteDomophone"),
                fields: [
                    {
                        id: "domophoneId",
                        type: "text",
                        title: i18n("domophones.domophoneId"),
                        value: domophoneId,
                        readonly: true,
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("domophones.enabled"),
                        value: domophone.enabled,
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("domophones.model"),
                        placeholder: i18n("domophones.model"),
                        options: models,
                        value: domophone.model,
                    },
                    {
                        id: "ip",
                        type: "text",
                        title: i18n("domophones.ip"),
                        placeholder: "IP",
                        value: domophone.ip,
                        validate: v => {
                            return !!ip2long(v);
                        },
                    },
                    {
                        id: "port",
                        type: "text",
                        title: i18n("domophones.port"),
                        placeholder: "80",
                        value: domophone.port,
                        validate: v => {
                            return !!parseInt(v);
                        },
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("domophones.credentials"),
                        placeholder: i18n("domophones.credentials"),
                        value: domophone.credentials,
                    },
                    {
                        id: "callerId",
                        type: "text",
                        title: i18n("domophones.callerId"),
                        placeholder: i18n("domophones.callerId"),
                        value: domophone.callerId,
                        validate: v => {
                            return $.trim(v) !== "" && $.trim(v).length <= 32;
                        },
                    },
                    {
                        id: "comment",
                        type: "text",
                        title: i18n("domophones.comment"),
                        placeholder: i18n("domophones.comment"),
                        value: domophone.comment,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                    },
                    {
                        id: "locksDisabled",
                        type: "yesno",
                        title: i18n("domophones.locksDisabled"),
                        value: domophone.locksDisabled,
                    },
                    {
                        id: "cmsLevels",
                        type: "text",
                        title: i18n("domophones.cmsLevels"),
                        placeholder: i18n("domophones.cmsLevels"),
                        value: domophone.cmsLevels,
                    },
                ],
                callback: result => {
                    if (result.delete === "yes") {
                        modules["domophones"].deleteDomophone(domophoneId);
                    } else {
                        modules["domophones"].doModifyDomophone(result);
                    }
                },
            });
        } else {
            error(i18n("domophones.domophoneNotFound"));
        }
    },

    deleteDomophone: function (domophoneId) {
        mConfirm(i18n("domophones.confirmDeleteDomophone", domophoneId), i18n("confirm"), `danger:${i18n("domophones.deleteDomophone")}`, () => {
            modules["domophones"].doDeleteDomophone(domophoneId);
        });
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
                    },
                    {
                        title: i18n("domophones.callerId"),
                    },
                    {
                        title: i18n("domophones.comment"),
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
                                    data: modules["domophones"].meta.domophones[i].ip,
                                    nowrap: true,
                                },
                                {
                                    data: modules["domophones"].meta.domophones[i].callerId,
                                    nowrap: true,
                                },
                                {
                                    data: modules["domophones"].meta.domophones[i].comment,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-phone-volume",
                                        title: i18n("domophones.cms"),
                                        click: domophoneId => {
                                            // ?
                                        },
                                    },
                                ]
                            }
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