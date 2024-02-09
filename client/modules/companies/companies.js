({
    meta: [],

    init: function () {
        if (AVAIL("companies", "companies")) {
            leftSide("far fa-fw fa-building", i18n("companies.companies"), "?#companies", "households");
        }
        moduleLoaded("companies", this);
    },

    companies: function (companies) {
        modules.companies.meta = companies["companies"];
    },

    addCompany: function () {
        cardForm({
            title: i18n("companies.addCompany"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "type",
                    type: "select",
                    title: i18n("companies.type"),
                    placeholder: i18n("companies.type"),
                    options: [
                        {
                            id: "1",
                            text: i18n("companies.type1"),
                        },
                        {
                            id: "2",
                            text: i18n("companies.type2"),
                        },
                    ],
                },
                {
                    id: "uid",
                    type: "text",
                    title: i18n("companies.uid"),
                    placeholder: i18n("companies.uid"),
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "name",
                    type: "text",
                    title: i18n("companies.name"),
                    placeholder: i18n("companies.name"),
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "contacts",
                    type: "area",
                    title: i18n("companies.contacts"),
                    placeholder: i18n("companies.contacts"),
                },
                {
                    id: "comment",
                    type: "area",
                    title: i18n("companies.comment"),
                    placeholder: i18n("companies.comment"),
                },
            ],
            callback: result => {
                loadingStart();
                POST("companies", "company", false, result).
                then(() => {
                    message(i18n("companies.companyWasAdded"));
                    modules.companies.renderCompanies();
                }).
                fail(FAIL).
                fail(loadingDone);
            },
        }).show();
    },

    modifyCompany: function (companyId) {
        GET("companies", "company", companyId, true).
        then(result => {
            console.log(result);

            cardForm({
                title: i18n("companies.modifyCompany"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("apply"),
                size: "lg",
                fields: [
                    {
                        id: "type",
                        type: "select",
                        title: i18n("companies.type"),
                        placeholder: i18n("companies.type"),
                        value: result.company.type,
                        options: [
                            {
                                id: "1",
                                text: i18n("companies.type1"),
                            },
                            {
                                id: "2",
                                text: i18n("companies.type2"),
                            },
                        ],
                    },
                    {
                        id: "uid",
                        type: "text",
                        title: i18n("companies.uid"),
                        placeholder: i18n("companies.uid"),
                        value: result.company.uid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "name",
                        type: "text",
                        title: i18n("companies.name"),
                        placeholder: i18n("companies.name"),
                        value: result.company.name,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "contacts",
                        type: "area",
                        title: i18n("companies.contacts"),
                        placeholder: i18n("companies.contacts"),
                        value: result.company.contacts,
                    },
                    {
                        id: "comment",
                        type: "area",
                        title: i18n("companies.comment"),
                        placeholder: i18n("companies.comment"),
                        value: result.company.comment,
                    },
                ],
                callback: result => {
                    loadingStart();
                    PUT("companies", "company", companyId, result).
                    then(() => {
                        message(i18n("companies.companyWasChanged"));
                        modules.companies.renderCompanies();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    deleteCompany: function (companyId) {
        mConfirm(i18n("companies.confirmCompanyDelete", companyId), i18n("confirm"), `danger:${i18n("delete")}`, () => {
            loadingStart();
            DELETE("companies", "company", companyId).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                message(i18n("companies.companyWasDeleted"));
                modules.companies.renderCompanies();
            });
        });
    },

    renderCompanies: function () {
        loadingStart();

        GET("companies", "companies", false, true).
        then(result => {
            modules.companies.companies(result);

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("companies.companies"),
                    button: {
                        caption: i18n("companies.addCompany"),
                        click: () => {
                            modules.companies.addCompany();
                        },
                    },
                    filter: true,
                },
                edit: modules.companies.modifyCompany,
                columns: [
                    {
                        title: i18n("companies.companyId"),
                    },
                    {
                        title: i18n("companies.type"),
                    },
                    {
                        title: i18n("companies.uid"),
                    },
                    {
                        title: i18n("companies.name"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];
    
                    for (let i in modules.companies.meta) {
                        rows.push({
                            uid: modules.companies.meta[i].companyId,
                            cols: [
                                {
                                    data: modules.companies.meta[i].companyId,
                                },
                                {
                                    data: i18n("companies.type" + modules.companies.meta[i].type),
                                    nowrap: true,
                                },
                                {
                                    data: modules.companies.meta[i].uid,
                                    nowrap: true,
                                },
                                {
                                    data: modules.companies.meta[i].name,
                                    nowrap: true,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-trash-alt",
                                        title: i18n("companies.deleteCompany"),
                                        class: "text-danger",
                                        click: companyId => {
                                            modules.companies.deleteCompany(companyId);
                                        },
                                    },
                                ],
                            },
                        });
                    }
    
                    return rows;
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("companies.companies");

        modules.companies.renderCompanies(params);
    },
}).init();