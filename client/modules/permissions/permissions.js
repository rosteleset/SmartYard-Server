({
    groupsStartPage: 1,
    usersStartPage: 1,

    rights: false,
    groups: false,
    users: false,
    methods: false,

    init: function () {
        if (AVAIL("authorization", "rights")) {
            leftSide("fas fa-fw fa-balance-scale-right", i18n("permissions.permissions"), "?#permissions", "accounts");
        }
        moduleLoaded("permissions", this);
    },

    /*
        action functions
     */

    doAddGroupRights: function (gid, api, method, allow, deny) {
        loadingStart();
        POST("authorization", "rights", false, {
            user: false,
            gid: gid,
            api: api,
            method: method,
            allow: allow,
            deny: deny,
        }).
        fail(FAIL).
        always(modules.permissions.render);
    },

    doAddUserRights: function (uid, api, method, allow, deny) {
        loadingStart();
        POST("authorization", "rights", false, {
            user: true,
            uid: uid,
            api: api,
            method: method,
            allow: allow,
            deny: deny,
        }).
        fail(FAIL).
        always(modules.permissions.render);
    },

    /*
        UI functions
     */

    addRights: function (group) {
        let g = [];
        let u = [];

        if (group) {
            for (let i in modules.permissions.groups) {
                g.push({
                    value: modules.permissions.groups[i].gid,
                    text: modules.permissions.groups[i].name ? modules.permissions.groups[i].name : modules.permissions.groups[i].acronym,
                });
            }
        } else {
            for (let i in modules.permissions.users) {
                if (modules.permissions.users[i].uid > 0) {
                    u.push({
                        value: modules.permissions.users[i].uid,
                        text: modules.permissions.users[i].login,
                    });
                }
            }
        }

        let a = [];
        a.push({
            value: "",
            text: "-",
        });
        for (let i in modules.permissions.methods) {
            a.push({
                value: i,
                text: (lang.methods[i] && lang.methods[i]["_title"])?lang.methods[i]["_title"]:i,
            });
        }
        cardForm({
            title: i18n("permissions.add"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            fields: [
                {
                    id: group?"gid":"uid",
                    type: "select2",
                    title: group?i18n("groups.group"):i18n("users.login"),
                    options: group?g:u,
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
                            for (let i in modules.permissions.methods[api]) {
                                m.push({
                                    id: i,
                                    text: (lang.methods[api] && lang.methods[api][i])?lang.methods[api][i]["_title"]:i,
                                })
                            }
                        }
                        $(`#${prefix}method`).html("").select2({
                            data: m,
                            language: lang["_code"],
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
                        let d = [];
                        let api = $(`#${prefix}api`).val();
                        let method = el.val();
                        if (api && method) {
                            for (let i in modules.permissions.methods[api][method]) {
                                a.push({
                                    id: modules.permissions.methods[api][method][i],
                                    text: (lang.methods[api] && lang.methods[api][method] && lang.methods[api][method][i])?lang.methods[api][method][i]:i,
                                    selected: true,
                                });
                                d.push({
                                    id: modules.permissions.methods[api][method][i],
                                    text: (lang.methods[api] && lang.methods[api][method] && lang.methods[api][method][i])?lang.methods[api][method][i]:i,
                                });
                            }
                        }
                        $(`#${prefix}actionAllow`).html("").select2({
                            data: a,
                            language: lang["_code"],
                        });
                        $(`#${prefix}actionDeny`).html("").select2({
                            data: d,
                            language: lang["_code"],
                        });
                    }
                },
                {
                    id: "actionAllow",
                    type: "select2",
                    title: i18n("permissions.allowYes"),
                    minimumResultsForSearch: Infinity,
                    multiple: true,
                    color: "success",
                    select: (el, id, prefix) => {
                        let aa = $(`#${prefix}actionAllow`).val();
                        let ad = $(`#${prefix}actionDeny`).val();
                        $(`#${prefix}actionDeny`).val(ad.filter(n => !aa.includes(n))).trigger("change");
                    },
                },
                {
                    id: "actionDeny",
                    type: "select2",
                    title: i18n("permissions.allowNo"),
                    minimumResultsForSearch: Infinity,
                    multiple: true,
                    color: "danger",
                    select: (el, id, prefix) => {
                        let aa = $(`#${prefix}actionAllow`).val();
                        let ad = $(`#${prefix}actionDeny`).val();
                        $(`#${prefix}actionAllow`).val(aa.filter(n => !ad.includes(n))).trigger("change");
                    },
                },
            ],
            callback: function (result) {
                if (group) {
                    modules.permissions.doAddGroupRights(result.gid, result.api, result.method, result.actionAllow, result.actionDeny);
                } else {
                    modules.permissions.doAddUserRights(result.uid, result.api, result.method, result.actionAllow, result.actionDeny);
                }
            },
        }).show();
    },

    editRights: function (group, group_login, api_name, method_name, allow, deny, options, guid, api, method) {
        cardForm({
            title: i18n("permissions.edit"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            fields: [
                {
                    id: group?"gid":"uid",
                    type: "text",
                    title: group?i18n("groups.group"):i18n("users.login"),
                    value: group_login,
                    readonly: true,
                },
                {
                    id: "api",
                    type: "text",
                    title: i18n("permissions.api"),
                    value: api_name,
                    readonly: true,
                },
                {
                    id: "method",
                    type: "text",
                    title: i18n("permissions.method"),
                    value: method_name,
                    readonly: true,
                },
                {
                    id: "actionAllow",
                    type: "select2",
                    title: i18n("permissions.allowYes"),
                    minimumResultsForSearch: Infinity,
                    multiple: true,
                    value: allow,
                    options: options,
                    color: "success",
                    select: (el, id, prefix) => {
                        let aa = $(`#${prefix}actionAllow`).val();
                        let ad = $(`#${prefix}actionDeny`).val();
                        $(`#${prefix}actionDeny`).val(ad.filter(n => !aa.includes(n))).trigger("change");
                    },
                },
                {
                    id: "actionDeny",
                    type: "select2",
                    title: i18n("permissions.allowNo"),
                    minimumResultsForSearch: Infinity,
                    multiple: true,
                    value: deny,
                    options: options,
                    color: "danger",
                    select: (el, id, prefix) => {
                        let aa = $(`#${prefix}actionAllow`).val();
                        let ad = $(`#${prefix}actionDeny`).val();
                        $(`#${prefix}actionAllow`).val(aa.filter(n => !ad.includes(n))).trigger("change");
                    },
                },
            ],
            callback: function (result) {
                if (group) {
                    modules.permissions.doAddGroupRights(guid, api, method, result.actionAllow, result.actionDeny);
                } else {
                    modules.permissions.doAddUserRights(guid, api, method, result.actionAllow, result.actionDeny);
                }
            },
        }).show();
    },

    /*
        main form (permissions) render function
     */

    rightsForm: function (group, g, u, m, tgt) {
        let x = {};

        if (group) {
            for (let i in modules.permissions.rights.groups) {
                let t = modules.permissions.rights.groups[i];
                if (!x[t.gid]) {
                    x[t.gid] = {};
                }
                if (m[t.aid] && m[t.aid].api && !x[t.gid][m[t.aid].api]) {
                    x[t.gid][m[t.aid].api] = {
                        _aid: t.aid,
                    };
                }
                if (m[t.aid] && m[t.aid].api && !x[t.gid][m[t.aid].api][m[t.aid].method]) {
                    x[t.gid][m[t.aid].api][m[t.aid].method] = {
                        _aid: t.aid,
                    };
                }
                if (m[t.aid] && m[t.aid].api) {
                    x[t.gid][m[t.aid].api][m[t.aid].method][m[t.aid].action] = {
                        _aid: t.aid,
                        allow: t.allow,
                    }
                }
            }
        } else {
            for (let i in modules.permissions.rights.users) {
                let t = modules.permissions.rights.users[i];
                if (!x[t.uid]) {
                    x[t.uid] = {};
                }
                if (!x[t.uid][m[t.aid].api]) {
                    x[t.uid][m[t.aid].api] = {
                        _aid: t.aid,
                    };
                }
                if (!x[t.uid][m[t.aid].api][m[t.aid].method]) {
                    x[t.uid][m[t.aid].api][m[t.aid].method] = {
                        _aid: t.aid,
                    };
                }
                x[t.uid][m[t.aid].api][m[t.aid].method][m[t.aid].action] = {
                    _aid: t.aid,
                    allow: t.allow,
                }
            }
        }

        return cardTable({
            target: tgt,
            title: {
                button: {
                    caption: i18n("permissions.addRights"),
                    click: () => {
                        modules.permissions.addRights(group);
                    },
                },
                caption: group?i18n("permissions.groups"):i18n("permissions.users"),
                filter: true,
            },
            edit: uid => {
                uid = uid.split('-');
                let a = [];
                let d = [];
                let o = [];
                for (let i in x[uid[0]][uid[1]][uid[2]]) {
                    if (i === "_aid") continue;
                    if (x[uid[0]][uid[1]][uid[2]][i].allow) a.push(x[uid[0]][uid[1]][uid[2]][i]._aid);
                    if (!x[uid[0]][uid[1]][uid[2]][i].allow) d.push(x[uid[0]][uid[1]][uid[2]][i]._aid);
                }
                for (let i in modules.permissions.methods[uid[1]][uid[2]]) {
                    o.push({
                        value: modules.permissions.methods[uid[1]][uid[2]][i],
                        text: (lang.methods[uid[1]] && lang.methods[uid[1]][uid[2]] && lang.methods[uid[1]][uid[2]][i])?lang.methods[uid[1]][uid[2]][i]:i,
                    });
                }
                modules.permissions.editRights(
                    group,
                    group?(g[uid[0]].name ? g[uid[0]].name : g[uid[0]].acronym):u[uid[0]].login,
                    (lang.methods[uid[1]] && lang.methods[uid[1]]["_title"])?lang.methods[uid[1]]["_title"]:uid[1],
                    (lang.methods[uid[1]] && lang.methods[uid[1]][uid[2]])?lang.methods[uid[1]][uid[2]]["_title"]:uid[2],
                    a,
                    d,
                    o,
                    uid[0],
                    uid[1],
                    uid[2]
                );
            },
            startPage: group?modules.permissions.groupsStartPage:modules.permissions.usersStartPage,
            pageChange: page => {
                if (group) {
                    modules.permissions.groupsStartPage = page;
                } else {
                    modules.permissions.usersStartPage = page;
                }
            },
            columns: [
                {
                    title: group?i18n("groups.group"):i18n("users.login"),
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
                    title: i18n("permissions.mode"),
                    nowrap: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in x) {
                    if (i == "_aid") continue;
                    for (let j in x[i]) {
                        if (j == "_aid") continue;
                        for (let k in x[i][j]) {
                            if (k == "_aid") continue;
                            let d = "";
                            if (x[i][j][k]["POST"]) {
                                if (x[i][j][k]["POST"].allow) {
                                    d += "<span class='text-success'>C</span>";
                                } else {
                                    d += "<span class='text-danger'>C</span>";
                                }
                            } else {
                                d += "<span>-</span>";
                            }
                            if (x[i][j][k]["GET"]) {
                                if (x[i][j][k]["GET"].allow) {
                                    d += "<span class='text-success'>R</span>";
                                } else {
                                    d += "<span class='text-danger'>R</span>";
                                }
                            } else {
                                d += "<span>-</span>";
                            }
                            if (x[i][j][k]["PUT"]) {
                                if (x[i][j][k]["PUT"].allow) {
                                    d += "<span class='text-success'>U</span>";
                                } else {
                                    d += "<span class='text-danger'>U</span>";
                                }
                            } else {
                                d += "<span>-</span>";
                            }
                            if (x[i][j][k]["DELETE"]) {
                                if (x[i][j][k]["DELETE"].allow) {
                                    d += "<span class='text-success'>D</span>";
                                } else {
                                    d += "<span class='text-danger'>D</span>";
                                }
                            } else {
                                d += "<span>-</span>";
                            }
                            rows.push({
                                uid: i.toString() + '-' + j + '-' + k,
                                cols: [
                                    {
                                        data: group?(g[i].name ? g[i].name : g[i].acronym):u[i].login,
                                        nowrap: true,
                                    },
                                    {
                                        data: m[x[i][j]._aid].api_text,
                                        nowrap: true,
                                    },
                                    {
                                        data: m[x[i][j][k]._aid].method_text,
                                        nowrap: true,
                                    },
                                    {
                                        data: "<span class='text-monospace text-bold'>" + d + "</span>",
                                        nowrap: true,
                                    },
                                ],
                            });
                        }

                    }
                }

                return rows;
            },
        });
    },

    render: function () {
        loadingStart();

        GET("authorization", "rights", false, true).done(r => {
            modules.permissions.rights = r.rights;

            QUERY("authorization", "methods", {
                all: 0,
            }).done(_m => {
                let m = {};

                for (let i in _m.methods) {
                    for (let j in _m.methods[i]) {
                        for (let k in _m.methods[i][j]) {
                            m[_m.methods[i][j][k]] = {
                                api: i,
                                api_text: (lang.methods[i] && lang.methods[i]._title)?lang.methods[i]._title:i,
                                method: j,
                                method_text: (lang.methods[i] && lang.methods[i][j] && lang.methods[i][j]._title)?lang.methods[i][j]._title:j,
                                action: k,
                                action_text: (lang.methods[i] && lang.methods[i][j] && lang.methods[i][j][k])?lang.methods[i][j][k]:k,
                            };
                        }
                    }
                }

                modules.permissions.methods = _m.methods;

                function accountsUsers(g, m) {
                    GET("accounts", "users").done(_u => {
                        modules.permissions.users = _u.users;

                        let u = {};

                        for (let i in _u.users) {
                            u[_u.users[i].uid] = _u.users[i];
                        }

                        if (AVAIL("accounts", "group", "POST")) {
                            modules.permissions.rightsForm(true, g, u, m, "#mainForm");
                            modules.permissions.rightsForm(false, g, u, m, "#altForm").show();
                        } else {
                            modules.permissions.rightsForm(false, g, u, m, "#mainForm");
                        }

                        loadingDone();
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                }

                if (AVAIL("accounts", "group", "POST")) {
                    GET("accounts", "groups").done(_g => {
                        modules.permissions.groups = _g.groups;

                        let g = {};

                        for (let i in _g.groups) {
                            g[_g.groups[i].gid] = _g.groups[i];
                        }
                        accountsUsers(g, m);
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                } else {
                    accountsUsers(false, m);
                }
            }).
            fail(FAIL).
            fail(loadingDone);
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("permissions.permissions");

        modules.permissions.render();
    }
}).init();