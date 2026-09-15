({
    init: function () {
        $('<link>', {rel: 'stylesheet', href: 'modules/addresses/virtualIntercom/style.css?ver=' + version}).appendTo('head');
        moduleLoaded('addresses.virtualIntercom', this);
    },

    edit: function (entranceId) {
        loadingStart();
        GET('houses', 'virtualIntercom', entranceId, true).
        done(response => this.render(response.virtualIntercom)).
        fail(FAIL).
        always(loadingDone);
    },

    render: function (panel) {
        const canEdit = !!AVAIL('houses', 'virtualIntercom', 'PUT');
        const yesNo = [{value: '1', text: i18n('yes')}, {value: '0', text: i18n('no')}];
        cardForm({
            title: i18n('addresses.virtualIntercom') + ' · #' + panel.entranceId,
            size: 'lg', footer: true, borderless: true, topApply: canEdit,
            apply: i18n('addresses.virtualIntercomSave'),
            fields: [
                {id: 'description', type: 'none', title: false, value: escapeHTML(i18n('addresses.virtualIntercomDescription'))},
                {id: 'unavailable', type: 'none', title: false, hidden: panel.available,
                    value: escapeHTML(i18n('addresses.virtualIntercomUnavailable'))},
                {id: 'enabled', type: 'select', title: i18n('addresses.virtualIntercomEnabled'),
                    options: yesNo, value: panel.enabled ? '1' : '0', readonly: !canEdit},
                {id: 'title', type: 'text', title: i18n('addresses.virtualIntercomTitle'), value: panel.title,
                    readonly: !canEdit, validate: value => !!value.trim() && Array.from(value).length <= 120},
                {id: 'subtitle', type: 'text', title: i18n('addresses.virtualIntercomSubtitle'), value: panel.subtitle,
                    readonly: !canEdit, validate: value => Array.from(value).length <= 240},
                {id: 'listEnabled', type: 'select', title: i18n('addresses.virtualIntercomList'),
                    options: yesNo, value: panel.listEnabled ? '1' : '0', readonly: !canEdit},
                {id: 'allowAllFlats', type: 'select', title: i18n('addresses.virtualIntercomAllFlats'),
                    hint: i18n('addresses.virtualIntercomAllFlatsHint'),
                    options: yesNo, value: (panel.allowAllFlats ?? true) ? '1' : '0', readonly: !canEdit},
                {id: 'url', type: 'text', title: i18n('addresses.virtualIntercomLink'), value: panel.url || '',
                    placeholder: i18n('addresses.virtualIntercomLinkAfterSave'), readonly: true,
                    button: {class: 'fas fa-copy', hint: i18n('addresses.virtualIntercomCopy'), click: async prefix => {
                        if (!panel.url) return;
                        try {
                            await navigator.clipboard.writeText(panel.url);
                            message(i18n('copied'), i18n('clipboard'), 3);
                        } catch (_) {
                            const input = document.getElementById(prefix + 'url'); input.focus(); input.select();
                        }
                    }}},
                {id: 'links', type: 'empty', title: false, hidden: !panel.url},
                {id: 'qr', type: 'empty', title: i18n('addresses.virtualIntercomQR'), hidden: !panel.url},
            ],
            done: prefix => {
                $('#' + prefix + 'enabled').closest('.card').addClass('virtual-intercom-form');
                $(`.formOk[data-prefix="${prefix}"]`).toggle(canEdit);
                $('#' + prefix + 'url').prop('disabled', false).prop('readOnly', true);
                $('#' + prefix + 'title').attr('maxlength', 120);
                $('#' + prefix + 'subtitle').attr('maxlength', 240);
                if (!panel.url) return;
                const links = $('#' + prefix + 'links').addClass('virtual-intercom-links');
                $('<a>').attr({href: panel.url, target: '_blank', rel: 'noopener noreferrer'})
                    .addClass('btn btn-outline-primary mr-2').text(i18n('addresses.virtualIntercomOpen')).appendTo(links);
                if (typeof QRCode !== 'undefined') {
                    $('#' + prefix + 'qr').addClass('virtual-intercom-qr');
                    new QRCode(document.getElementById(prefix + 'qr'), {text: panel.url, width: 220, height: 220});
                    $('<button>').attr('type', 'button').addClass('btn btn-outline-secondary')
                        .text(i18n('addresses.virtualIntercomDownloadQR')).appendTo(links).on('click', () => {
                            const canvas = document.querySelector('#' + prefix + 'qr canvas');
                            const image = document.querySelector('#' + prefix + 'qr img');
                            const url = canvas ? canvas.toDataURL('image/png') : image?.src;
                            if (!url) return;
                            const link = document.createElement('a'); link.href = url;
                            link.download = 'virtual-intercom-' + panel.entranceId + '.png'; link.click();
                        });
                }
            },
            callback: values => {
                if (!canEdit) return;
                const input = {enabled: values.enabled === '1', title: values.title, subtitle: values.subtitle,
                    listEnabled: values.listEnabled === '1', allowAllFlats: values.allowAllFlats === '1'};
                // cardForm closes after invoking this callback. Reopen only once
                // that transition finishes, retaining the draft if saving fails.
                const closed = new Promise(resolve => $('#modal').one('hidden.bs.modal', resolve));
                loadingStart();
                PUT('houses', 'virtualIntercom', panel.entranceId, input).
                done(async response => { await closed; this.render(response.virtualIntercom); }).
                fail(async response => { FAIL(response); await closed; this.render({...panel, ...input}); }).
                always(loadingDone);
            },
        });
    },
}).init();
