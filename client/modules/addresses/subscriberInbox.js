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
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.messages"),
                    filter: true,
                    pagerItemsCount: 250,
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

                    for (let i in response.messages) {
                        let msg = $.trim(response.messages[i].msg).split("\n");
                        if (msg.length > 1) {
                            msg = msg[0] + '...';
                        } else {
                            msg = msg[0];
                        }

                        rows.push({
                            uid: response.messages[i].msgId,
                            cols: [
                                {
                                    data: response.messages[i].msgId,
                                },
                                {
                                    data: response.messages[i].id,
                                },
                                {
                                    data: response.messages[i].title,
                                    nowrap: true,
                                },
                                {
                                    data: msg,
                                },
                                {
                                    data: parseInt(response.messages[i].readed)?i18n("yes"):i18n("no"),
                                },
                                {
                                    data: response.messages[i].code,
                                },
                            ],
                        });
                    }

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
