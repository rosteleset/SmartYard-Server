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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "body",
                    type: "area",
                    title: i18n("addresses.messageBody"),
                    placeholder: i18n("addresses.messageBody"),
                    validate: v => {
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
        });
    },

    renderSubscriberInbox: function (subscriberId) {
        loadingStart();

        GET("inbox", "message", subscriberId, true).
        fail(FAILPAGE).
        done(response => {
            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.messages"),
                    filter: true,
                    pagerItemsCount: 250,
                    button: {
                        icon: "far fa-fw fa-envelope",
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
                        title: i18n("addresses.messageDate"),
                    },
                    {
                        title: i18n("addresses.messageTitle"),
                    },
                    {
                        title: i18n("addresses.messageBody"),
                        fullWidth: true,
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
                                    data: ttDate(response.messages[i].date, false),
                                    nowrap: true,
                                },
                                {
                                    data: response.messages[i].title,
                                    nowrap: true,
                                },
                                {
                                    data: msg,
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
        document.title = i18n("windowTitle") + " :: " + i18n("addresses.subscriberInbox");

        modules.addresses.topMenu();

        QUERY("addresses", "addresses", {
            houseId: params.houseId,
        }).
        done(modules.addresses.addresses).
        fail(FAIL).
        done(a => {
            for (let i in a.addresses.houses) {
                if (a.addresses.houses[i].houseId == params.houseId) {
                    document.title = i18n("windowTitle") + " :: " + a.addresses.houses[i].houseFull + ", " + params.flat;
                    subTop(modules.addresses.path((parseInt(params.settlementId) ? "settlement" : "street"), parseInt(params.settlementId) ? params.settlementId : params.streetId, true) + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + `<a href="?#addresses.houses&houseId=${params.houseId}">${a.addresses.houses[i].houseFull}</a>` + '<i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + `<a href='?#addresses.subscribers&flatId=${params.flatId}&houseId=${params.houseId}&flat=${params.flat}&settlementId=${params.settlementId}&streetId=${params.streetId}'>` +  params.flat + '</a><i class="fas fa-xs fa-angle-double-right ml-2 mr-2"></i>' + params.phone);
                    break;
                }
            }

            modules.addresses.subscriberInbox.renderSubscriberInbox(params.subscriberId);
        });

        $("#altForm").hide();
    }
}).init();
