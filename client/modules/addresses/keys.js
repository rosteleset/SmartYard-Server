({
    menuItem: false,

    init: function () {
        if (AVAIL("subscribers", "keys")) {
            this.menuItem = leftSide("fab fa-fw fa-keycdn", i18n("addresses.superKeys"), "?#addresses.keys", "households");
        }
        moduleLoaded("addresses.keys", this);
    },

    renderKeys: function (params) {
        loadingStart();
        QUERY("subscribers", "keys", {
            by: params.by ? params.by : "0",
            query: params.query ? params.query : "0",
        }, true).
        fail(FAILPAGE).
        done(result => {
            let target = (params.modal ? modalTable : cardTable)({
                caption: params.caption,
                target: "#mainForm",
                title: {
                    caption: parseInt(params.by) ? i18n("addresses.objectKeys", i18n("addresses.keysType" + parseInt(params.by))) : i18n("addresses.superKeys"),
                    button: {
                        caption: i18n("addresses.addKey"),
                        click: () => {
                            modules.addresses.keys.addKey(params);
                        },
                    },
                },
                edit: keyId => {
                    modules.addresses.keys.modifyKey(keyId, params);
                },
                columns: [
                    {
                        title: i18n("addresses.keyId"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.rfId"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.lastSeen"),
                        nowrap: true,
                    },
                    {
                        title: i18n("addresses.comments"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in result.keys) {
                        rows.push({
                            uid: result.keys[i].keyId,
                            cols: [
                                {
                                    data: result.keys[i].keyId,
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].rfId,
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].lastSeen ? ttDate(result.keys[i].lastSeen) : "&nbsp;",
                                    nowrap: true,
                                },
                                {
                                    data: result.keys[i].comments ? result.keys[i].comments : "&nbsp;",
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-trash-alt",
                                        title: i18n("addresses.deleteKey"),
                                        class: "text-danger",
                                        click: keyId => {
                                            modules.addresses.keys.removeKey(keyId, params);
                                        },
                                    },
                                ],
                            },
                        });
                    }

                    return rows;
                },
            });

            if (target) {
                target.show();
            }

            loadingDone();
        });
    },

    splitRfIds: function (value) {
        let rfIds = [];

        for (const part of (value ? value : "").split(/[\r\n,;]+/)) {
            let rfId = $.trim(part);

            if (rfId) {
                rfIds.push(rfId);
            }
        }

        return rfIds;
    },

    rfIdPlaceholder: function (format) {
        return format === "dec"
            ? "313249263\n884401844993\n20015998343868\n45514025410622983"
            : "12:AB:CD:EF\ncdea753301\n12-34-56-78-9A-BC\nA1 B2 C3 D4 E5 F6 07";
    },

    rfIdByteSizeOptions: function () {
        let options = [
            {
                id: "",
                text: "",
            },
        ];

        for (let size = 1; size <= 7; size++) {
            options.push({
                id: size.toString(),
                text: size.toString(),
            });
        }

        return options;
    },

    hideRfIdPreview: function (prefix) {
        $("#" + prefix + "rfIdPreview").empty();
        $("#" + prefix + "rfIdPreview-container").hide();
    },

    toggleRfIdBytes: function (prefix, visible) {
        $("#" + prefix + "rfIdBytes-container")
            .toggle(visible)
            .attr("data-form-runtime-hide", visible ? "0" : "1");
        modules.addresses.keys.hideRfIdPreview(prefix);
    },

    processRfIds: function (value, format, reverseBytes, byteSize) {
        let seen = new Set();
        let separatedHex = /^(?:[0-9A-Fa-f]{2}(?::[0-9A-Fa-f]{2}){1,6}|[0-9A-Fa-f]{2}(?:-[0-9A-Fa-f]{2}){1,6}|[0-9A-Fa-f]{2}(?:\s+[0-9A-Fa-f]{2}){1,6})$/;
        let maxRfId = BigInt("0xFFFFFFFFFFFFFF");
        let byteLength = parseInt(byteSize);

        return modules.addresses.keys.splitRfIds(value).map(source => {
            let rfId;

            if (format === "dec") {
                if (!/^\d+$/.test(source) || BigInt(source) > maxRfId) {
                    return { source: source, valid: false };
                }
                rfId = BigInt(source).toString(16).toUpperCase();
            } else {
                if (!/^[0-9A-Fa-f]{1,14}$/.test(source) && !separatedHex.test(source)) {
                    return { source: source, valid: false };
                }
                rfId = source.replace(/[:-]|\s+/g, "").toUpperCase();
            }

            if (reverseBytes) {
                if (!/^[1-7]$/.test(byteSize) || (rfId.replace(/^0+/, "") || "0").length > byteLength * 2) {
                    return { source: source, valid: false };
                }
                rfId = (rfId.replace(/^0+/, "") || "0").padStart(byteLength * 2, "0");
                rfId = rfId.match(/.{2}/g).reverse().join("");
            }

            rfId = rfId.padStart(14, "0");

            let duplicate = seen.has(rfId);
            seen.add(rfId);

            return {
                source: source,
                result: rfId,
                valid: true,
                duplicate: duplicate,
            };
        });
    },

    showRfIdPreview: function (prefix, notify) {
        let value = $("#" + prefix + "rfIds").val();
        let format = $("#" + prefix + "rfIdFormat").val();
        let reverseBytes = $("#" + prefix + "reverseRfIdBytes").val() === "1";
        let byteSize = reverseBytes ? $("#" + prefix + "rfIdBytes").val() : null;
        let rows = modules.addresses.keys.processRfIds(value, format, reverseBytes, byteSize);

        if (!rows.length || (reverseBytes && !/^[1-7]$/.test(byteSize))) {
            modules.addresses.keys.hideRfIdPreview(prefix);
            $("#" + prefix + (rows.length ? "rfIdBytes" : "rfIds")).addClass("is-invalid");
            if (notify) {
                error(i18n("invalidFieldValue"));
            }
            return false;
        }

        let valid = rows.every(row => row.valid);

        let preview = `
            <div class="overflow-auto" style="max-height: 300px;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>${i18n("addresses.rfidSource")}</th>
                            <th>${i18n("addresses.rfidResult")}</th>
                            <th>${i18n("addresses.status")}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(row => `
                            <tr class="${!row.valid ? "rfid-preview-error" : (row.duplicate ? "rfid-preview-duplicate" : "")}">
                                <td>${escapeHTML(row.source)}</td>
                                <td>${row.result ? escapeHTML(row.result) : "&mdash;"}</td>
                                <td>${i18n(!row.valid ? "addresses.rfidInvalid" : (row.duplicate ? "addresses.rfidDuplicate" : "addresses.rfidOk"))}</td>
                            </tr>
                        `).join("")}
                    </tbody>
                </table>
            </div>
        `;

        $("#" + prefix + "rfIds").removeClass("is-invalid");
        $("#" + prefix + "rfIdBytes").removeClass("is-invalid");
        $("#" + prefix + "rfIdPreview").removeClass("is-invalid").html(preview);
        $("#" + prefix + "rfIdPreview-container").show();

        return valid;
    },

    normalizeRfIds: function (value, format, reverseBytes, byteSize) {
        return modules.addresses.keys.processRfIds(value, format, reverseBytes, byteSize)
            .filter(row => row.valid && !row.duplicate)
            .map(row => row.result);
    },

    rfIdFormFields: function () {
        return [
            {
                id: "rfIdFormat",
                type: "select",
                title: i18n("addresses.rfidFormat"),
                value: "hex",
                select: (field, id, prefix) => {
                    $("#" + prefix + "rfIds").attr("placeholder", modules.addresses.keys.rfIdPlaceholder(field.val()));
                    modules.addresses.keys.hideRfIdPreview(prefix);
                },
                options: [
                    {
                        id: "hex",
                        text: "HEX",
                    },
                    {
                        id: "dec",
                        text: "DEC",
                    },
                ],
            },
            {
                id: "rfIds",
                type: "area",
                title: i18n("addresses.keys"),
                placeholder: modules.addresses.keys.rfIdPlaceholder("hex"),
            },
            {
                id: "reverseRfIdBytes",
                type: "noyes",
                title: i18n("addresses.rfidReverseBytes"),
                value: "0",
                select: (field, id, prefix) => modules.addresses.keys.toggleRfIdBytes(prefix, field.val() === "1"),
            },
            {
                id: "rfIdBytes",
                type: "select",
                title: i18n("addresses.rfidBytes"),
                value: "",
                hidden: true,
                options: modules.addresses.keys.rfIdByteSizeOptions(),
                validate: (value, prefix) => $("#" + prefix + "reverseRfIdBytes").val() !== "1" || /^[1-7]$/.test(value),
                select: (field, id, prefix) => modules.addresses.keys.hideRfIdPreview(prefix),
            },
            {
                id: "previewRfIds",
                type: "button",
                title: false,
                button: {
                    hint: i18n("addresses.rfidPreview"),
                    class: "btn-secondary",
                    click: prefix => modules.addresses.keys.showRfIdPreview(prefix, true),
                },
            },
            {
                id: "rfIdPreview",
                type: "empty",
                title: i18n("addresses.rfidConversionResult"),
                hidden: true,
                validate: (value, prefix) => modules.addresses.keys.showRfIdPreview(prefix, false),
            },
            {
                id: "comments",
                type: "text",
                title: i18n("addresses.comments"),
            },
        ];
    },

    prepareRfIdFormResult: function (result) {
        result.rfIds = modules.addresses.keys.normalizeRfIds(result.rfIds, result.rfIdFormat, result.reverseRfIdBytes === "1", result.rfIdBytes);
        delete result.rfIdFormat;
        delete result.reverseRfIdBytes;
        delete result.rfIdBytes;
        delete result.previewRfIds;
        delete result.rfIdPreview;

        return result;
    },

    initRfIdForm: function (prefix) {
        $("#" + prefix + "rfIdPreview-container").attr("data-form-runtime-hide", "0");
        $("#" + prefix + "rfIds").on("input", () => modules.addresses.keys.hideRfIdPreview(prefix));
    },

    addKeyMessage: function (response) {
        if (response.keys) {
            if (response.keys.added.length) {
                message(response.keys.total > 1 ? i18n("addresses.keysWereAdded", response.keys.added.length, response.keys.total) : i18n("addresses.keyWasAdded"));
            }
            if (response.keys.failed.length) {
                let failed = response.keys.failed.map(key => key.rfId).slice(0, 5).join(", ");
                error(i18n("addresses.keysWereNotAdded", response.keys.failed.length, response.keys.total) + (failed ? ": " + failed : ""));
            }
        } else {
            message(i18n("addresses.keyWasAdded"));
        }
    },

    addKey: function (params) {
        cardForm({
            title: i18n("addresses.addKey"),
            size: "lg",
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            fields: modules.addresses.keys.rfIdFormFields(),
            callback: function (result) {
                result = modules.addresses.keys.prepareRfIdFormResult(result);
                result.accessType = params.by ? params.by : "0";
                result.accessTo = params.query ? params.query : "0";
                loadingStart();
                POST("subscribers", "key", false, result).
                fail(FAIL).
                fail(loadingDone).
                done(response => {
                    modules.addresses.keys.addKeyMessage(response);
                }).
                always(() => {
                    if (params.modal) {
                        modules.addresses.keys.modalKeys(params);
                    } else {
                        window.location = refreshUrl();
                    }
                });
            },
            done: modules.addresses.keys.initRfIdForm,
        });
    },

    removeKey: function (keyId, params) {
        mConfirm(i18n("addresses.confirmDeleteKey", keyId), i18n("confirm"), `danger:${i18n("addresses.deleteKey")}`, () => {
            DELETE("subscribers", "key", keyId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("addresses.keyWasDeleted"));
            }).
            always(() => {
                if (params.modal) {
                    modules.addresses.keys.modalKeys(params);
                } else {
                    window.location = refreshUrl();
                }
            });
        });
    },

    modifyKey: function (keyId, params) {
        loadingStart();
        QUERY("subscribers", "keys", {
            by: "keyId",
            query: keyId,
        }, true).
        fail(FAILPAGE).
        done(result => {
            loadingDone();

            let key = false;

            for (let i in result.keys) {
                if (result.keys[i].keyId == keyId) {
                    key = result.keys[i];
                    break;
                }
            }

            if (key) {
                cardForm({
                    title: i18n("addresses.editKey"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("edit"),
                    fields: [
                        {
                            id: "keyId",
                            type: "text",
                            title: i18n("addresses.keyId"),
                            readonly: true,
                            value: key.keyId,
                        },
                        {
                            id: "rfId",
                            type: "text",
                            title: i18n("addresses.rfId"),
                            readonly: true,
                            value: key.rfId,
                        },
                        {
                            id: "comments",
                            type: "text",
                            title: i18n("addresses.comments"),
                            placeholder: i18n("addresses.comments"),
                            value: key.comments,
                        },
                    ],
                    callback: result => {
                        PUT("subscribers", "key", result.keyId, result).
                        fail(FAIL).
                        fail(loadingDone).
                        done(() => {
                            message(i18n("addresses.keyWasChanged"));
                        }).
                        always(() => {
                            if (params.modal) {
                                modules.addresses.keys.modalKeys(params);
                            } else {
                                window.location = refreshUrl();
                            }
                        });
                    },
                });
            } else {
                error(i18n("addresses.keyNotFound"));
            }
        });
    },

    modalKeys: function (params) {
        params.modal = true;

        modules.addresses.keys.renderKeys(params);
    },

    route: function (params) {
        $("#altForm").hide();

        if (params.backStr && params.back) {
            subTop(`<a href="?#${params.back}">${params.backStr}</a>${params.backStrPlus ? (", " + params.backStrPlus) : ""}`);
        } else {
            subTop();
        }

        modules.addresses.keys.renderKeys(params);
    },
}).init();