({
    init: function () {
        moduleLoaded("addresses.domophones", this);
    },

    meta: false,

    doAddDomophone: function (domophone) {
        loadingStart();
        POST("houses", "domophone", false, domophone).
        fail(FAIL).
        always(modules.addresses.domophones.route);
    },

    doModifyDomophone: function (domophone) {
        loadingStart();
        PUT("houses", "domophone", domophone.domophoneId, domophone).
        fail(FAIL).
        always(modules.addresses.domophones.route);
    },

    doDeleteDomophone: function (domophoneId) {
        loadingStart();
        DELETE("houses", "domophone", domophoneId).
        fail(FAIL).
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
                    id: "ip",
                    type: "text",
                    title: i18n("addresses.ip"),
                    placeholder: "IP",
                    validate: v => {
                        return !!ip2long(v);
                    },
                },
                {
                    id: "port",
                    type: "text",
                    title: i18n("addresses.port"),
                    placeholder: "80",
                    value: "80",
                    validate: v => {
                        return !!parseInt(v);
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
                    id: "callerId",
                    type: "text",
                    title: i18n("addresses.callerId"),
                    placeholder: i18n("addresses.callerId"),
                    validate: v => {
                        return $.trim(v) !== "" && $.trim(v).length <= 32;
                    },
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
                title: i18n("addresses.modifyDomophone"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                delete: i18n("addresses.deleteDomophone"),
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
                        id: "ip",
                        type: "text",
                        title: i18n("addresses.ip"),
                        placeholder: "IP",
                        value: domophone.ip,
                        validate: v => {
                            return !!ip2long(v);
                        },
                    },
                    {
                        id: "port",
                        type: "text",
                        title: i18n("addresses.port"),
                        placeholder: "80",
                        value: domophone.port,
                        validate: v => {
                            return !!parseInt(v);
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
                        id: "callerId",
                        type: "text",
                        title: i18n("addresses.callerId"),
                        placeholder: i18n("addresses.callerId"),
                        value: domophone.callerId,
                        validate: v => {
                            return $.trim(v) !== "" && $.trim(v).length <= 32;
                        },
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
        let top = '';

        if (location.href.split("#")[1] !== "addresses.domophones") {
            top += `<li class="nav-item d-none d-sm-inline-block">`;
            top += `<a href="#addresses.domophones" class="nav-link nav-item-back-hover text-dark"><i class="fa-fw fa-xs fas fa-door-open mr-2"></i>${i18n("addresses.domophones")}</a>`;
            top += `</li>`;
        }

        $("#leftTopDynamic").html(top);

        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.domophones");

        let domophoneId = false;

        if (params.domophoneId) {
            domophoneId = parseInt(params.domophoneId);
        }

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
                    filter: true,
                },
                edit: modules.addresses.domophones.modifyDomophone,
                columns: [
                    {
                        title: i18n("addresses.domophoneId"),
                    },
                    {
                        title: i18n("addresses.ip"),
                    },
                    {
                        title: i18n("addresses.callerId"),
                    },
                    {
                        title: i18n("addresses.comment"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.domophones.meta.domophones) {

                        if (domophoneId && domophoneId !== parseInt(modules.addresses.domophones.meta.domophones[i].domophoneId)) continue;

                        rows.push({
                            uid: modules.addresses.domophones.meta.domophones[i].domophoneId,
                            cols: [
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].domophoneId,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].ip,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].callerId,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].comment,
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