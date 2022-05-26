({
    groupsStartPage: 1,
    usersStartPage: 1,

    rights: false,
    groups: false,
    users: false,
    methods: false,

    init: function () {
        leftSide("fas fa-fw fa-balance-scale-right", i18n("permissions.permissions"), "#permissions");
        moduleLoaded("permissions", this);
    },

    /*
        action functions
     */

    doAddPermission: function (gid, action, allow) {
        console.log(gid, action, allow);
    },

    /*
        UI functions
     */

    addGroupRights: function () {
        let g = [];
        for (let i in window.modules["permissions"].groups) {
            g.push({
                value: window.modules["permissions"].groups[i].gid,
                text: window.modules["permissions"].groups[i].acronym,
            });
        }
        let a = [];
        a.push({
            value: "",
            text: "-",
        });
        for (let i in window.modules["permissions"].methods) {
            a.push({
                value: i,
                text: (window.lang.methods[i] && window.lang.methods[i]["_title"])?window.lang.methods[i]["_title"]:i,
            });
        }
        cardForm({
            title: i18n("permissions.add"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "gid",
                    type: "select2",
                    title: i18n("groups.acronym"),
                    options: g,
                },
                {
                    id: "api",
                    type: "select2",
                    title: i18n("permissions.api"),
                    options: a,
                    select: (el, id, prefix) => {
                        let m = [
                            {
                                id: "",
                                text: "-",
                            }
                        ];
                        let api = el.val();
                        if (api) {
                            for (let i in window.modules["permissions"].methods[api]) {
                                m.push({
                                    id: i,
                                    text: (window.lang.methods[api] && window.lang.methods[api][i])?window.lang.methods[api][i]["_title"]:i,
                                })
                            }
                        }
                        $(`#${prefix}method`).html("").select2({
                            data: m,
                            theme: "bootstrap4",
                            language: window.lang["_code"],
                        });
                        $(`#${prefix}action`).html("");
                    }
                },
                {
                    id: "method",
                    type: "select2",
                    title: i18n("permissions.method"),
                    options: [
                        {
                            value: "",
                            text: "-",
                        }
                    ],
                    select: (el, id, prefix) => {
                        let a = [
                            {
                                id: "",
                                text: "-",
                            }
                        ];
                        let api = $(`#${prefix}api`).val();
                        let method = el.val();
                        if (api && method) {
                            for (let i in window.modules["permissions"].methods[api][method]) {
                                a.push({
                                    id: window.modules["permissions"].methods[api][method][i],
                                    text: (window.lang.methods[api] && window.lang.methods[api][method] && window.lang.methods[api][method][i])?window.lang.methods[api][method][i]:i,
                                })
                            }
                        }
                        $(`#${prefix}action`).html("").select2({
                            data: a,
                            theme: "bootstrap4",
                            language: window.lang["_code"],
                        });
                    }
                },
                {
                    id: "action",
                    type: "select2",
                    title: i18n("permissions.action"),
                    minimumResultsForSearch: Infinity,
                    options: [
                        {
                            value: "",
                            text: "-",
                        }
                    ],
                },
                {
                    id: "allow",
                    type: "select",
                    title: i18n("permissions.allow"),
                    options: [
                        {
                            value: 1,
                            text: i18n("permissions.allowYes"),
                        },
                        {
                            value: 0,
                            text: i18n("permissions.allowNo"),
                        }
                    ]
                },
            ],
            callback: function (result) {
                window.modules["permissions"].doAddPermission(result.gid, result.action, result.allow);
            },
        }).show();
    },

    /*
        main form (permissions) render function
     */

    render: function () {
        loadingStart();
        GET("authorization", "rights").done(r => {
            window.modules["permissions"].rights = r.rights;

            console.log(r);

            GET("authorization", "methods").done(m => {
                window.modules["permissions"].methods = m.methods;

                console.log(m);

                GET("accounts", "groups").done(g => {
                    window.modules["permissions"].groups = g.groups;

                    console.log(g);

                    cardTable({
                        target: "#mainForm",
                        title: {
                            button: {
                                caption: i18n("permissions.addRights"),
                                click: window.modules["permissions"].addGroupRights,
                            },
                            caption: i18n("permissions.groups"),
                            filter: true,
                        },
                        startPage: window.modules["permissions"].groupsStartPage,
                        pageChange: page => {
                            window.modules["permissions"].groupsStartPage = page;
                        },
                        columns: [
                            {
                                title: i18n("permissions.api"),
                                nowrap: true,
                            },
                            {
                                title: i18n("permissions.method"),
                                nowrap: true,
                            },
                            {
                                title: i18n("permissions.action"),
                                nowrap: true,
                            },
                            {
                                title: i18n("groups.acronym"),
                                nowrap: true,
                                fullWidth: true,
                            },
                            {
                                title: i18n("permissions.allow"),
                                nowrap: true,
                            },
                        ],
                        rows: () => {
                            let rows = [];

                            return rows;
                        },
                    });

                    GET("accounts", "users").done(u => {
                        console.log(u);

                        cardTable({
                            target: "#altForm",
                            title: {
                                button: {
                                    caption: i18n("permissions.addRights"),
                                    click: window.modules["permissions"].addUserRights,
                                },
                                caption: i18n("permissions.users"),
                                filter: true,
                            },
                            startPage: window.modules["permissions"].usersStartPage,
                            pageChange: page => {
                                window.modules["permissions"].usersStartPage = page;
                            },
                            columns: [
                                {
                                    title: i18n("permissions.api"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.method"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.action"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("users.login"),
                                    fullWidth: true,
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.allow"),
                                    nowrap: true,
                                },
                            ],
                            rows: () => {
                                let rows = [];

                                return rows;
                            },
                        }).show();
                        loadingDone();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                }).
                fail(FAIL).
                fail(loadingDone);
            }).
            fail(FAIL).
            fail(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("groups.groups");

        window.modules["permissions"].render();
    }
}).init();