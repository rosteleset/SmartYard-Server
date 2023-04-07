({
    init: function () {
        if (AVAIL("providers", "provider", "GET")) {
            leftSide("fas fa-fw fa-network-wired", i18n("providers.providers"), "?#providers", "providers");
        }
        moduleLoaded("providers", this);
    },

    doAddProvider: function (provider) {
        loadingStart();
        POST("providers", "provider", false, provider).
        fail(FAIL).
        done(() => {
            message(i18n("providers.providerWasAdded"));
        }).
        always(modules.providers.route);
    },

    doModifyProvider: function (providerId, params) {
        loadingStart();
        PUT("providers", "provider", providerId, params).
        fail(FAIL).
        done(() => {
            message(i18n("providers.providerWasChanged"));
        }).
        always(modules.providers.route);
    },

    doDeleteProvider: function (providerId) {
        loadingStart();
        DELETE("providers", "provider", providerId).
        fail(FAIL).
        done(() => {
            message(i18n("providers.providerWasDeleted"));
        }).
        always(modules.providers.route);
    },

    addProvider: function () {
        cardForm({
            title: i18n("providers.addProvider"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "uid",
                    type: "text",
                    title: i18n("providers.uid"),
                    placeholder: i18n("providers.uid"),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                },
                {
                    id: "baseUrl",
                    type: "text",
                    title: i18n("providers.baseUrl"),
                    placeholder: i18n("providers.baseUrl"),
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
                    id: "name",
                    type: "text",
                    title: i18n("providers.name"),
                    placeholder: i18n("providers.name"),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                },
                {
                    id: "tokenCommon",
                    type: "text",
                    title: i18n("providers.tokenCommon"),
                    placeholder: i18n("providers.tokenCommon"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}tokenCommon`).val(md5(guid()));
                        },
                    }
                },
                {
                    id: "tokenSms",
                    type: "text",
                    title: i18n("providers.tokenSms"),
                    placeholder: i18n("providers.tokenSms"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}tokenSms`).val(md5(guid()));
                        },
                    }
                },
                {
                    id: "hidden",
                    type: "yesno",
                    title: i18n("providers.hidden"),
                    placeholder: i18n("providers.hidden"),
                    value: "0",
                },
            ],
            callback: modules.providers.doAddProvider,
        });
    },

    modifyProvider: function (providerId) {
        loadingStart();
        GET("providers", "provider", false, true).
        done(response => {

            let provider = false;
            for (let i in response.providers) {
                if (response.providers[i].providerId == providerId) {
                    provider = response.providers[i];
                }
            }
            if (provider) {
                cardForm({
                    title: i18n("providers.modifyProvider"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    size: "lg",
                    delete: i18n("providers.deleteProvider"),
                    fields: [
                        {
                            id: "uid",
                            type: "text",
                            title: i18n("providers.uid"),
                            placeholder: i18n("providers.uid"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            value: provider.id,
                        },
                        {
                            id: "baseUrl",
                            type: "text",
                            title: i18n("providers.baseUrl"),
                            placeholder: i18n("providers.baseUrl"),
                            validate: v => {
                                try {
                                    new URL(v);
                                    return true;
                                } catch (_) {
                                    return false;
                                }
                            },
                            value: provider.baseUrl,
                        },
                        {
                            id: "name",
                            type: "text",
                            title: i18n("providers.name"),
                            placeholder: i18n("providers.name"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            value: provider.name,
                        },
                        {
                            id: "tokenCommon",
                            type: "text",
                            title: i18n("providers.tokenCommon"),
                            placeholder: i18n("providers.tokenCommon"),
                            button: {
                                class: "fas fa-magic",
                                click: prefix => {
                                    $(`#${prefix}tokenCommon`).val(md5(guid()));
                                },
                            },
                            value: provider.tokenCommon,
                        },
                        {
                            id: "tokenSms",
                            type: "text",
                            title: i18n("providers.tokenSms"),
                            placeholder: i18n("providers.tokenSms"),
                            button: {
                                class: "fas fa-magic",
                                click: prefix => {
                                    $(`#${prefix}tokenSms`).val(md5(guid()));
                                },
                            },
                            value: provider.tokenSms,
                        },
                        {
                            id: "hidden",
                            type: "yesno",
                            title: i18n("providers.hidden"),
                            placeholder: i18n("providers.hidden"),
                            value: provider.hidden,
                        },
                    ],
                    callback: result => {
                        if (result.delete === "yes") {
                            modules.providers.deleteProvider(provider.providerId);
                        } else {
                            modules.providers.doModifyProvider(provider.providerId, result);
                        }
                    },
                });
            } else {
                error(i18n("providers.providerNotFound"));
            }
        }).
        always(loadingDone);
    },

    deleteProvider: function (providerId) {
        mConfirm(i18n("providers.confirmDeleteProvider", providerId), i18n("confirm"), `danger:${i18n("providers.deleteProvider")}`, () => {
            modules.providers.doDeleteProvider(providerId);
        });
    },

    route: function () {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("providers.providers");

        loadingStart();
        GET("providers", "provider", false, true).
        done(response => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("providers.providers"),
                    button: {
                        caption: i18n("providers.addProvider"),
                        click: modules.providers.addProvider,
                    },
                },
                edit: providerId => {
                    modules.providers.modifyProvider(providerId);
                },
                columns: [
                    {
                        title: i18n("providers.id"),
                    },
                    {
                        title: i18n("providers.uid"),
                    },
                    {
                        title: i18n("providers.baseUrl"),
                    },
                    {
                        title: i18n("providers.name"),
                        fullWidth: true,
                    },
                    {
                        title: i18n("providers.hidden"),
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in response.providers) {
                        rows.push({
                            uid: response.providers[i].providerId,
                            cols: [
                                {
                                    data: response.providers[i].providerId,
                                },
                                {
                                    data: response.providers[i].id,
                                },
                                {
                                    data: response.providers[i].baseUrl,
                                },
                                {
                                    data: response.providers[i].name,
                                },
                                {
                                    data: parseInt(response.providers[i].hidden)?i18n("yes"):i18n("no"),
                                },
                            ],
                        });
                    }

                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },
}).init();