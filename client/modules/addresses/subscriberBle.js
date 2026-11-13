({
    submitting: false,

    init: function () {
        moduleLoaded('addresses.subscriberBle', this);
    },

    open: function (subscriberId, caption) {
        if (!AVAIL('subscribers', 'bleKeys', 'GET')) {
            return;
        }

        loadingStart();
        GET('subscribers', 'bleKeys', subscriberId, true).done(result => {
            modalTable({
                caption: escapeHTML(caption),
                dropDownLeft: true,
                title: {
                    caption: i18n('addresses.bleKeys'),
                    button: AVAIL('subscribers', 'bleKey', 'POST') ? {
                        caption: i18n('addresses.generateBleKey'),
                        icon: 'fas fa-dice',
                        click: () => {
                            modules.addresses.subscriberBle.generateKey(subscriberId, caption);
                        },
                    } : false,
                },
                columns: [
                    {
                        title: i18n('addresses.key'),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    if (!result.keys.length) {
                        return [{
                            cols: [{ data: i18n('addresses.bleKeysEmpty'), class: 'text-muted' }],
                        }];
                    }

                    return result.keys.map((item, index) => ({
                        uid: index,
                        cols: [{
                            data: `<span class="text-monospace text-break" style="white-space: pre-wrap;">${escapeHTML(item.key)}</span>`,
                            fullWidth: true,
                        }],
                        dropDown: AVAIL('subscribers', 'bleKey', 'DELETE') ? {
                            items: [{
                                icon: 'fas fa-trash-alt',
                                title: i18n('addresses.deleteBleKey'),
                                class: 'text-danger',
                                click: () => {
                                    modules.addresses.subscriberBle.deleteKey(subscriberId, item.key, caption);
                                },
                            }],
                        } : false,
                    }));
                },
            });
        }).fail(FAIL).always(loadingDone);
    },

    generateKey: function (subscriberId, caption) {
        let ble = modules.addresses.subscriberBle;
        if (!AVAIL('subscribers', 'bleKey', 'POST') || ble.submitting) {
            return;
        }

        ble.submitting = true;
        loadingStart();
        POST('subscribers', 'bleKey', subscriberId).done(() => {
            message(i18n('addresses.bleKeyReady'));
        }).fail(FAIL).always(() => {
            ble.submitting = false;
            loadingDone();
        }).done(() => {
            ble.open(subscriberId, caption);
        });
    },

    deleteKey: function (subscriberId, key, caption) {
        let ble = modules.addresses.subscriberBle;
        if (!AVAIL('subscribers', 'bleKey', 'DELETE') || ble.submitting) {
            return;
        }

        mConfirm(i18n('addresses.confirmDeleteBleKey', escapeHTML(key)), i18n('confirm'), `danger:${i18n('addresses.deleteBleKey')}`, () => {
            if (ble.submitting) {
                return;
            }

            ble.submitting = true;
            loadingStart();
            DELETE('subscribers', 'bleKey', subscriberId, { key: key }).done(() => {
                message(i18n('addresses.keyWasDeleted'));
            }).fail(FAIL).always(() => {
                ble.submitting = false;
                loadingDone();
            }).done(() => {
                ble.open(subscriberId, caption);
            });
        });
    },
}).init();
