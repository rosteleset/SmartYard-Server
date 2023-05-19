({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-door-open", i18n("addresses.domophones"), "?#addresses.domophones", "households");
        }
        moduleLoaded("addresses.domophones", this);
    },

    meta: false,
    startPage: 1,
    flter: "",

    doAddDomophone: function (domophone) {
        loadingStart();
        POST("houses", "domophone", false, domophone).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.domophoneWasAdded"))
            modules.addresses.domophones.route({
                flter: domophone.url
            });
        }).
        fail(modules.addresses.domophones.route);
    },

    doModifyDomophone: function (domophone) {
        loadingStart();
        PUT("houses", "domophone", domophone.domophoneId, domophone).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.domophoneWasChanged"))
        }).
        always(modules.addresses.domophones.route);
    },

    doDeleteDomophone: function (domophoneId) {
        loadingStart();
        DELETE("houses", "domophone", domophoneId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.domophoneWasDeleted"))
        }).
        always(modules.addresses.domophones.route);
    },

    addDomophone: function () {
        let models = [];
        let servers = [];

        for (let id in modules.addresses.domophones.meta.models) {
            models.push({
                id,
                text: modules.addresses.domophones.meta.models[id].title,
            })
        }

        for (let id in modules.addresses.domophones.meta.servers) {
            servers.push({
                id: modules.addresses.domophones.meta.servers[id].ip,
                text: modules.addresses.domophones.meta.servers[id].title,
            })
        }

        cardForm({
            title: i18n("addresses.addDomophone"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "enabled",
                    type: "yesno",
                    title: i18n("addresses.enabled"),
                    value: "1",
                },
                {
                    id: "model",
                    type: "select2",
                    title: i18n("addresses.model"),
                    placeholder: i18n("addresses.model"),
                    options: models,
                },
                {
                    id: "server",
                    type: "select2",
                    title: i18n("addresses.server"),
                    placeholder: i18n("addresses.server"),
                    options: servers,
                },
                {
                    id: "url",
                    type: "text",
                    title: i18n("addresses.url"),
                    placeholder: "http://",
                    validate: v => {
                        try {
                            new URL(v);
                            return true;
                        } catch (_) {
                            return false;
                        }
                    },
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("addresses.credentials"),
                    placeholder: i18n("addresses.credentials"),
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "dtmf",
                    type: "text",
                    title: i18n("addresses.dtmf"),
                    placeholder: i18n("addresses.dtmf"),
                    value: "1",
                    validate: v => {
                        return [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ].indexOf($.trim(v)) >= 0;
                    },
                },
                {
                    id: "nat",
                    type: "yesno",
                    title: i18n("addresses.nat"),
                    value: "0",
                },
                {
                    id: "comment",
                    type: "text",
                    title: i18n("addresses.comment"),
                    placeholder: i18n("addresses.comment"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                },
            ],
            callback: modules.addresses.domophones.doAddDomophone,
        });
    },

    modifyDomophone: function (domophoneId) {
        let models = [];
        let servers = [];

        for (let id in modules.addresses.domophones.meta.models) {
            models.push({
                id,
                text: modules.addresses.domophones.meta.models[id].title,
            })
        }

        for (let id in modules.addresses.domophones.meta.servers) {
            servers.push({
                id: modules.addresses.domophones.meta.servers[id].ip,
                text: modules.addresses.domophones.meta.servers[id].title,
            })
        }

        let domophone = false;

        for (let i in modules.addresses.domophones.meta.domophones) {
            if (modules.addresses.domophones.meta.domophones[i].domophoneId == domophoneId) {
                domophone = modules.addresses.domophones.meta.domophones[i];
                break;
            }
        }

        if (domophone) {
            cardForm({
                title: i18n("addresses.editDomophone"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("addresses.deleteDomophone"),
                size: "lg",
                fields: [
                    {
                        id: "domophoneId",
                        type: "text",
                        title: i18n("addresses.domophoneId"),
                        value: domophoneId,
                        readonly: true,
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("addresses.enabled"),
                        value: domophone.enabled,
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("addresses.model"),
                        placeholder: i18n("addresses.model"),
                        options: models,
                        value: domophone.model,
                    },
                    {
                        id: "server",
                        type: "select2",
                        title: i18n("addresses.server"),
                        placeholder: i18n("addresses.server"),
                        options: servers,
                        value: domophone.server,
                    },
                    {
                        id: "url",
                        type: "text",
                        title: i18n("addresses.url"),
                        placeholder: "http://",
                        value: domophone.url,
                        validate: v => {
                            try {
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        },
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("addresses.credentials"),
                        placeholder: i18n("addresses.credentials"),
                        value: domophone.credentials,
                        validate: v => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "dtmf",
                        type: "text",
                        title: i18n("addresses.dtmf"),
                        placeholder: i18n("addresses.dtmf"),
                        value: domophone.dtmf,
                        validate: v => {
                            return [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ].indexOf($.trim(v)) >= 0;
                        },
                    },
                    {
                        id: "firstTime",
                        type: "yesno",
                        title: i18n("addresses.firstTime"),
                        value: domophone.firstTime,
                    },
                    {
                        id: "nat",
                        type: "yesno",
                        title: i18n("addresses.nat"),
                        value: domophone.nat,
                    },
                    {
                        id: "locksAreOpen",
                        type: "yesno",
                        title: i18n("addresses.locksAreOpen"),
                        value: domophone.locksAreOpen,
                    },
                    {
                        id: "comment",
                        type: "text",
                        title: i18n("addresses.comment"),
                        placeholder: i18n("addresses.comment"),
                        value: domophone.comment,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                    },
                ],
                callback: result => {
                    if (result.delete === "yes") {
                        modules.addresses.domophones.deleteDomophone(domophoneId);
                    } else {
                        modules.addresses.domophones.doModifyDomophone(result);
                    }
                },
            });
        } else {
            error(i18n("addresses.domophoneNotFound"));
        }
    },

    deleteDomophone: function (domophoneId) {
        mConfirm(i18n("addresses.confirmDeleteDomophone", domophoneId), i18n("confirm"), `danger:${i18n("addresses.deleteDomophone")}`, () => {
            modules.addresses.domophones.doDeleteDomophone(domophoneId);
        });
    },

    route: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.domophones");

        GET("houses", "domophones", false, true).
        done(response => {
            modules.addresses.domophones.meta = response.domophones;

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.domophones"),
                    button: {
                        caption: i18n("addresses.addDomophone"),
                        click: modules.addresses.domophones.addDomophone,
                    },
                    filter: params.flter?params.flter:(modules.addresses.domophones.flter?modules.addresses.domophones.flter:true),
                },
                edit: modules.addresses.domophones.modifyDomophone,
                startPage: modules.addresses.domophones.startPage,
                pageChange: p => {
                    modules.addresses.domophones.startPage = p;
                },
                filterChange: f => {
                    modules.addresses.domophones.flter = f;
                },
                columns: [
                    {
                        title: i18n("addresses.domophoneId"),
                    },
                    {
                        title: i18n("addresses.url"),
                    },
                    {
                        title: i18n("addresses.comment"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.domophones.meta.domophones) {
                        rows.push({
                            uid: modules.addresses.domophones.meta.domophones[i].domophoneId,
                            cols: [
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].domophoneId,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].url,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].comment,
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