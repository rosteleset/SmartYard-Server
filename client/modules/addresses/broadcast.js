({
    init: function () {
        moduleLoaded("addresses.broadcast", this);
    },

    scopeLabel: function (by, query) {
        if (by === "all") {
            return i18n("addresses.broadcastAll");
        }

        let object = by.substring(0, by.length - 2);
        let collection = object === "city" ? "cities" : object + "s";
        let items = (modules.addresses.meta || {})[collection] || [];
        if (object === "house" && modules.addresses.houses.meta && modules.addresses.houses.meta.house) {
            items = [ modules.addresses.houses.meta.house ];
        }
        let item = items.find(item => String(item[by]) === String(query));
        let name = item ? item[object === "house" ? "houseFull" : object + "WithType"] : "";
        return i18n("addresses." + object) + ": " + (name || "#" + query);
    },

    open: function (by, query) {
        if (!AVAIL("inbox", "broadcast", "GET") || !AVAIL("inbox", "broadcast", "POST")) {
            return;
        }

        let scope = modules.addresses.broadcast.scopeLabel(by, query);
        loadingStart();
        QUERY("inbox", "broadcast", { by: by, query: query }, true).
        done(result => {
            let count = result.audience.count;
            if (!count) {
                warning(i18n("addresses.noSubscribersFond"));
                return;
            }

            let submitting = false;
            cardForm({
                title: i18n("addresses.messageSend"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: "addresses.doMessageSend",
                size: "lg",
                fields: [
                    {
                        id: "scope",
                        type: "text",
                        title: i18n("addresses.broadcastScope"),
                        value: scope,
                        readonly: true,
                    },
                    {
                        id: "recipients",
                        type: "text",
                        title: i18n("addresses.broadcastRecipients"),
                        value: count,
                        readonly: true,
                        hint: i18n("addresses.broadcastQueueHint"),
                    },
                    {
                        id: "title",
                        type: "text",
                        title: i18n("addresses.messageTitle"),
                        placeholder: i18n("addresses.messageTitle"),
                        validate: v => $.trim(v) !== "",
                    },
                    {
                        id: "body",
                        type: "area",
                        title: i18n("addresses.messageBody"),
                        placeholder: i18n("addresses.messageBody"),
                        validate: v => $.trim(v) !== "",
                    },
                    {
                        id: "action",
                        type: "select2",
                        title: i18n("addresses.messageAction"),
                        options: [
                            { value: "inbox", text: i18n("addresses.messageActionInbox") },
                            { value: "money", text: i18n("addresses.messageActionBalancePlus") },
                        ],
                    },
                ],
                callback: msg => {
                    mConfirm(i18n("addresses.broadcastConfirm", escapeHTML(scope), count), i18n("addresses.broadcast"), i18n("addresses.doMessageSend"), () => {
                        if (submitting) {
                            return;
                        }
                        submitting = true;
                        loadingStart();
                        POST("inbox", "broadcast", false, {
                            by: by,
                            query: query,
                            title: msg.title,
                            body: msg.body,
                            action: msg.action,
                        }).
                        done(result => message(i18n("addresses.messagesQueued", result.queued.count))).
                        fail(FAIL).
                        always(() => {
                            submitting = false;
                            loadingDone();
                        });
                    });
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },
}).init();
