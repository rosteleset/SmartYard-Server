({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.subscriberInbox", this);
    },

    doSendMessage: function (subscriberId, params) {
        loadingStart();
        POST("inbox", "message", subscriberId, params).
        fail(FAIL).
        always(() => {
             modules.addresses.subscriberInbox.renderSubscriberInbox(subscriberId);
        });
    },

    sendMessage: function (subscriberId) {
        cardForm({
            title: i18n("addresses.messageSend"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: "addresses.doMessageSend",
            size: "lg",
            fields: [
                {
                    id: "title",
                    type: "text",
                    title: i18n("addresses.messageTitle"),
                    placeholder: i18n("addresses.messageTitle"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "body",
                    type: "area",
                    title: i18n("addresses.messageBody"),
                    placeholder: i18n("addresses.messageBody"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "action",
                    type: "select2",
                    title: i18n("addresses.messageAction"),
                    options: [
                        {
                            value: "inbox",
                            text: i18n("addresses.messageActionInbox"),
                        },
                        {
                            value: "money",
                            text: i18n("addresses.messageActionBalancePlus"),
                        },
                    ]
                },
            ],
            callback: function (result) {
                modules.addresses.subscriberInbox.doSendMessage(subscriberId, result);
            },
        }).show();
    },

    renderSubscriberInbox: function (subscriberId) {
        loadingStart();

        GET("inbox", "message", subscriberId, false).
        fail(FAILPAGE).
        done(response => {
            console.log(response);

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.messages"),
                    button: {
                        icon: "fas fa-fw fa-envelope-open",
                        caption: i18n("addresses.messageSend"),
                        click: () => {
                            modules.addresses.subscriberInbox.sendMessage(subscriberId);
                        },
                    },
                },
                columns: [
                    {
                        title: i18n("addresses.messageId"),
                    },
                    {
                        title: i18n("addresses.messageMobile"),
                    },
                    {
                        title: i18n("addresses.messageTitle"),
                    },
                    {
                        title: i18n("addresses.messageBody"),
                        nowrap: true,
                        fullWidth: true,
                    },
                    {
                        title: i18n("addresses.messageReaded"),
                    },
                    {
                        title: i18n("addresses.messagesCode"),
                    },
                ],
                rows: () => {
                    let rows = [];
                    /*
                                    let list = [];

                                    rows.push({
                                        uid: list[i].subscriberId,
                                        cols: [
                                            {
                                                data: list[i].subscriberId,
                                            },
                                            {
                                                data: list[i].mobile,
                                            },
                                            {
                                                data: owner?i18n("yes"):i18n("no"),
                                            },
                                        ],
                                    });
                    */
                    return rows;
                }

            }).show();
        }).
        always(loadingDone);
    },

    route: function (params) {
        modules.addresses.topMenu();

        modules.addresses.subscriberInbox.renderSubscriberInbox(params.subscriberId);

        $("#altForm").hide();
    }
}).init();
