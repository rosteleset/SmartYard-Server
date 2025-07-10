({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-lock", i18n("addresses.domophones"), "?#addresses.domophones", "households");
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
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "model",
                    type: "select2",
                    title: i18n("addresses.model"),
                    placeholder: i18n("addresses.model"),
                    options: models,
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "server",
                    type: "select2",
                    title: i18n("addresses.server"),
                    placeholder: i18n("addresses.server"),
                    options: servers,
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "url",
                    type: "text",
                    title: i18n("addresses.url"),
                    placeholder: "http://",
                    validate: v => {
                        try {
                            if (!/^https?:\/\/.+/.test(v)) {
                                throw new Error();
                            }
                            new URL(v);
                            return true;
                        } catch (_) {
                            return false;
                        }
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "credentials",
                    type: "text",
                    title: i18n("addresses.credentials"),
                    placeholder: i18n("addresses.credentials"),
                    validate: v => {
                        return $.trim(v) !== "";
                    },
                    tab: i18n("addresses.primary"),
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
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "nat",
                    type: "yesno",
                    title: i18n("addresses.nat"),
                    value: "0",
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "video",
                    type: "select",
                    title: i18n("addresses.video"),
                    options: [
                        {
                            id: "inband",
                            text: i18n("addresses.inband"),
                        },
                        {
                            id: "webrtc",
                            text: i18n("addresses.webrtc"),
                        },
                    ],
                    value: "inband",
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "name",
                    type: "text",
                    title: i18n("addresses.domophoneName"),
                    placeholder: i18n("addresses.domophoneName"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "comments",
                    type: "text",
                    title: i18n("addresses.comments"),
                    placeholder: i18n("addresses.comments"),
                    validate: v => {
                        return $.trim(v).length <= 64;
                    },
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "display",
                    type: "area",
                    title: i18n("addresses.display"),
                    placeholder: i18n("addresses.display"),
                    tab: i18n("addresses.primary"),
                },
                {
                    id: "ext",
                    type: "json",
                    title: false,
                    tab: i18n("addresses.ext"),
                    noHover: true,
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
                deleteTab: i18n("addresses.primary"),
                size: "lg",
                fields: [
                    {
                        id: "domophoneId",
                        type: "text",
                        title: i18n("addresses.domophoneId"),
                        value: domophoneId,
                        readonly: true,
                        hint: 100000 + parseInt(domophoneId),
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "enabled",
                        type: "yesno",
                        title: i18n("addresses.enabled"),
                        value: domophone.enabled,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "model",
                        type: "select2",
                        title: i18n("addresses.model"),
                        placeholder: i18n("addresses.model"),
                        options: models,
                        value: domophone.model,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "server",
                        type: "select2",
                        title: i18n("addresses.server"),
                        placeholder: i18n("addresses.server"),
                        options: servers,
                        value: domophone.server,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "url",
                        type: "text",
                        title: i18n("addresses.url"),
                        placeholder: "http://",
                        value: domophone.url,
                        validate: v => {
                            try {
                                if (!/^https?:\/\/.+/.test(v)) {
                                    throw new Error();
                                }
                                new URL(v);
                                return true;
                            } catch (_) {
                                return false;
                            }
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "credentials",
                        type: "text",
                        title: i18n("addresses.credentials"),
                        placeholder: i18n("addresses.credentials"),
                        value: domophone.credentials,
                        validate: v => {
                            return $.trim(v) !== "";
                        },
                        tab: i18n("addresses.primary"),
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
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "firstTime",
                        type: "yesno",
                        title: i18n("addresses.firstTime"),
                        value: domophone.firstTime,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "nat",
                        type: "yesno",
                        title: i18n("addresses.nat"),
                        value: domophone.nat,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "video",
                        type: "select",
                        title: i18n("addresses.video"),
                        options: [
                            {
                                id: "inband",
                                text: i18n("addresses.inband"),
                            },
                            {
                                id: "webrtc",
                                text: i18n("addresses.webrtc"),
                            },
                        ],
                        value: domophone.video,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "locksAreOpen",
                        type: "yesno",
                        title: i18n("addresses.locksAreOpen"),
                        value: domophone.locksAreOpen,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "name",
                        type: "text",
                        title: i18n("addresses.domophoneName"),
                        placeholder: i18n("addresses.domophoneName"),
                        value: domophone.name,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "comments",
                        type: "text",
                        title: i18n("addresses.comments"),
                        placeholder: i18n("addresses.comments"),
                        value: domophone.comments,
                        validate: v => {
                            return $.trim(v).length <= 64;
                        },
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "display",
                        type: "area",
                        title: i18n("addresses.display"),
                        placeholder: i18n("addresses.displayPlaceholder"),
                        value: domophone.display,
                        tab: i18n("addresses.primary"),
                    },
                    {
                        id: "ext",
                        type: "json",
                        title: false,
                        tab: i18n("addresses.ext"),
                        value: domophone.ext,
                        noHover: true,
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

    handleDeviceStatus: function (status) {
        let statusClass;
        switch (status) {
            case 'OK':
                statusClass = 'status-ok';
                break;
            case 'Offline':
                statusClass = 'status-offline';
                break;
            case 'SIP error':
                statusClass = 'status-sip-failure';
                break;
            case 'Disabled':
                statusClass = 'status-disabled';
                break;
            case 'Other':
                statusClass = 'status-other-error';
                break;
            default:
                statusClass = 'status-unknown';
                status = 'unknown'
        }
        return `
        <div class="status-container">
            <span class="status-indicator ${statusClass}">
                <div class="status-tooltip">${status}</div>
            </span>
        </div>
    `;
    },

    route: function (params) {
        $("#altForm").hide();
        subTop();

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
                        title: i18n("addresses.domophoneIdList"),
                    },
                    {
                        title: i18n("addresses.status"),
                    },
                    {
                        title: i18n("addresses.url"),
                    },
                    {
                        title: i18n("addresses.model"),
                    },
                    {
                        title: i18n("addresses.domophoneName"),
                    },
                    {
                        title: i18n("addresses.comments"),
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
                                    data: modules.addresses.domophones.meta.domophones[i].enabled
                                        ? modules.addresses.domophones.handleDeviceStatus(
                                            modules.addresses.domophones.meta.domophones[i].status
                                                ? modules.addresses.domophones.meta.domophones[i].status.status : "Unknown")
                                        : modules.addresses.domophones.handleDeviceStatus("Disabled"),
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].url,
                                    nowrap: true,
                                },
                                {
                                    data: (modules.addresses.domophones.meta.domophones[i].model && modules.addresses.domophones.meta.models[modules.addresses.domophones.meta.domophones[i].model]) ? modules.addresses.domophones.meta.models[modules.addresses.domophones.meta.domophones[i].model].title : modules.addresses.domophones.meta.domophones[i].model,
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].name ? modules.addresses.domophones.meta.domophones[i].name : "",
                                    nowrap: true,
                                },
                                {
                                    data: modules.addresses.domophones.meta.domophones[i].comments ? modules.addresses.domophones.meta.domophones[i].comments : "",
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