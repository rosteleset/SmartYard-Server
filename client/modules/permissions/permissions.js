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

    doAddGroupRights: function (gid, action, allow) {
        loadingStart();
        PUT("authorization", "rights", false, {
            user: false,
            gid: gid,
            action: action,
            allow: allow,
        }).
        fail(FAIL).
        always(window.modules["permissions"].render);
    },

    doAddUserRights: function (uid, action, allow) {
        loadingStart();
        PUT("authorization", "rights", false, {
            user: true,
            uid: uid,
            action: action,
            allow: allow,
        }).
        fail(FAIL).
        always(window.modules["permissions"].render);
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
                        let a = [];
                        let api = $(`#${prefix}api`).val();
                        let method = el.val();
                        if (api && method) {
                            for (let i in window.modules["permissions"].methods[api][method]) {
                                a.push({
                                    id: window.modules["permissions"].methods[api][method][i],
                                    text: (window.lang.methods[api] && window.lang.methods[api][method] && window.lang.methods[api][method][i])?window.lang.methods[api][method][i]:i,
                                    selected: true,
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
                    multiple: true,
                },
                {
                    id: "allow",
                    type: "select",
                    title: i18n("permissions.allow"),
                    options: [
                        {
                            value: "yes",
                            text: i18n("permissions.allowYes"),
                        },
                        {
                            value: "no",
                            text: i18n("permissions.allowNo"),
                        }
                    ]
                },
            ],
            callback: function (result) {
                window.modules["permissions"].doAddGroupRights(result.gid, result.action, result.allow === "yes");
            },
        }).show();
    },

    addUserRights: function () {
        let u = [];
        for (let i in window.modules["permissions"].users) {
            u.push({
                value: window.modules["permissions"].users[i].uid,
                text: window.modules["permissions"].users[i].login,
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
                    id: "uid",
                    type: "select2",
                    title: i18n("users.login"),
                    options: u,
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
                        let a = [];
                        let api = $(`#${prefix}api`).val();
                        let method = el.val();
                        if (api && method) {
                            for (let i in window.modules["permissions"].methods[api][method]) {
                                a.push({
                                    id: window.modules["permissions"].methods[api][method][i],
                                    text: (window.lang.methods[api] && window.lang.methods[api][method] && window.lang.methods[api][method][i])?window.lang.methods[api][method][i]:i,
                                    selected: true,
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
                    multiple: true,
                },
                {
                    id: "allow",
                    type: "select",
                    title: i18n("permissions.allow"),
                    options: [
                        {
                            value: "yes",
                            text: i18n("permissions.allowYes"),
                        },
                        {
                            value: "no",
                            text: i18n("permissions.allowNo"),
                        }
                    ]
                },
            ],
            callback: function (result) {
                window.modules["permissions"].doAddUserRights(result.uid, result.action, result.allow === "yes");
            },
        }).show();
    },

    /*
        main form (permissions) render function
     */

    render: function () {
        loadingStart();
        GET("authorization", "rights", false, true).done(r => {
            window.modules["permissions"].rights = r.rights;

            QUERY("authorization", "methods", {
                all: 0,
            }).done(_m => {
                let m = {};

                for (let i in _m.methods) {
                    for (let j in _m.methods[i]) {
                        for (let k in _m.methods[i][j]) {
                            m[_m.methods[i][j][k]] = {
                                api: i,
                                api_text: (window.lang.methods[i] && window.lang.methods[i]._title)?window.lang.methods[i]._title:i,
                                method: j,
                                method_text: (window.lang.methods[i] && window.lang.methods[i][j] && window.lang.methods[i][j]._title)?window.lang.methods[i][j]._title:j,
                                action: k,
                                action_text: (window.lang.methods[i] && window.lang.methods[i][j] && window.lang.methods[i][j][k])?window.lang.methods[i][j][k]:k,
                            };
                        }
                    }
                }

                window.modules["permissions"].methods = _m.methods;

                GET("accounts", "groups").done(_g => {
                    window.modules["permissions"].groups = _g.groups;

                    let g = {};

                    for (let i in _g.groups) {
                        g[_g.groups[i].gid] = _g.groups[i];
                    }

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
                                title: i18n("groups.acronym"),
                                nowrap: true,
                            },
                            {
                                title: i18n("permissions.api"),
                                nowrap: true,
                            },
                            {
                                title: i18n("permissions.method"),
                                nowrap: true,
                                fullWidth: true,
                            },
                            {
                                title: i18n("permissions.action"),
                                nowrap: true,
                            },
                            {
                                title: i18n("permissions.allow"),
                                nowrap: true,
                            },
                        ],
                        rows: () => {
                            let rows = [];

                            for (let i in window.modules["permissions"].rights.groups) {
                                rows.push({
                                    uid: window.modules["permissions"].rights.groups[i].gid.toString() + '-' + window.modules["permissions"].rights.groups[i].aid,
                                    cols: [
                                        {
                                            data: g[window.modules["permissions"].rights.groups[i].gid].acronym,
                                        },
                                        {
                                            data: m[window.modules["permissions"].rights.groups[i].aid].api_text,
                                        },
                                        {
                                            data: m[window.modules["permissions"].rights.groups[i].aid].method_text,
                                        },
                                        {
                                            data: m[window.modules["permissions"].rights.groups[i].aid].action_text,
                                        },
                                        {
                                            data: window.modules["permissions"].rights.groups[i].allow?i18n("permissions.allowYes"):i18n("permissions.allowNo"),
                                        },
                                    ],
                                });
                            }

                            return rows;
                        },
                    });

                    GET("accounts", "users").done(_u => {
                        window.modules["permissions"].users = _u.users;

                        let u = {};

                        for (let i in _u.users) {
                            u[_u.users[i].uid] = _u.users[i];
                        }


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
                                    title: i18n("users.login"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.api"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.method"),
                                    nowrap: true,
                                    fullWidth: true,
                                },
                                {
                                    title: i18n("permissions.action"),
                                    nowrap: true,
                                },
                                {
                                    title: i18n("permissions.allow"),
                                    nowrap: true,
                                },
                            ],
                            rows: () => {
                                let rows = [];

                                for (let i in window.modules["permissions"].rights.users) {
                                    rows.push({
                                        uid: window.modules["permissions"].rights.users[i].uid.toString() + '-' + window.modules["permissions"].rights.users[i].aid,
                                        cols: [
                                            {
                                                data: u[window.modules["permissions"].rights.users[i].uid].login,
                                            },
                                            {
                                                data: m[window.modules["permissions"].rights.users[i].aid].api_text,
                                            },
                                            {
                                                data: m[window.modules["permissions"].rights.users[i].aid].method_text,
                                            },
                                            {
                                                data: m[window.modules["permissions"].rights.users[i].aid].action_text,
                                            },
                                            {
                                                data: window.modules["permissions"].rights.users[i].allow?i18n("permissions.allowYes"):i18n("permissions.allowNo"),
                                            },
                                        ],
                                    });
                                }

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
        document.title = i18n("windowTitle") + " :: " + i18n("permissions.permissions");

        window.modules["permissions"].render();
    }
}).init();