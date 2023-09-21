({
    startPage: 1,
    meta: [],

    init: function () {
        if (AVAIL("accounts", "user", "POST")) {
            leftSide("fas fa-fw fa-user", i18n("users.users"), "?#users", "accounts");
        }
        moduleLoaded("users", this);
    },

    loadUsers: function (callback, withSessions) {
        return QUERY("accounts", "users", withSessions?{ withSessions: true }:false).
        done(users => {
            modules.users.meta = users.users;
        }).
        always(() => {
            if (typeof callback == "function") callback(modules.users.meta);
        });
    },

    login2name: function (login) {
        let u = login;
        
        for (let k in modules.users.meta) {
            if (modules.users.meta[k].login == login) {
                if (modules.users.meta[k].realName) {
                    u = modules.users.meta[k].realName;
                }
                break;
            }
        }

        return u;
    },

    /*
        action functions
     */

    doAddUser: function (login, realName, eMail, phone) {
        loadingStart();
        POST("accounts", "user", false, {
            login: login,
            realName: realName,
            eMail: eMail,
            phone: phone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("users.userWasAdded"));
        }).
        always(modules.users.render);
    },

    doModifyUser: function (user) {
        loadingStart();
        PUT("accounts", "user", user.uid, user).
        fail(FAIL).
        done(() => {
            if (user.uid == myself.uid) {
                whoAmI(true);
            }
            message(i18n("users.userWasChanged"));
        }).
        always(() => {
            if (currentPage === "users") {
                modules.users.render();
            } else {
                loadingDone();
            }
        });
    },

    doDeleteUser: function (uid) {
        loadingStart();
        DELETE("accounts", "user", uid).
        fail(FAIL).
        done(() => {
            message(i18n("users.userWasDeleted"));
        }).
        always(() => {
            if (currentPage === "users") {
                modules.users.render();
            } else {
                loadingDone();
            }
        });
    },

    /*
        UI functions
     */

    addUser: function () {
        cardForm({
            title: i18n("users.add"),
            footer: true,
            borderless: true,
            topApply: true,
            fields: [
                {
                    id: "login",
                    type: "text",
                    title: i18n("users.login"),
                    placeholder: i18n("users.login"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "realName",
                    type: "text",
                    title: i18n("users.realName"),
                    placeholder: i18n("users.realName"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "eMail",
                    type: "email",
                    title: i18n("eMail"),
                    placeholder: i18n("eMail"),
                },
                {
                    id: "phone",
                    type: "tel",
                    title: i18n("phone"),
                    placeholder: i18n("phone"),
                },
            ],
            callback: function (result) {
                modules.users.doAddUser(result.login, result.realName, result.eMail, result.phone);
            },
        }).show();
    },

    modifyUser: function (uid) {

        function realModifyUser(uid) {
            GET("accounts", "user", uid, true).done(response => {
                let gs = [];

                if (modules.groups) {
                    gs.push({
                        value: -1,
                        text: "-",
                    });
    
                    for (let i in modules.groups.meta) {
                        gs.push({
                            value: modules.groups.meta[i].gid,
                            text: $.trim(modules.groups.meta[i].name + " [" + modules.groups.meta[i].acronym + "]"),
                        });
                    }
                }

                cardForm({
                    title: i18n("users.edit"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    size: "lg",
                    delete: (uid.toString() !== "0" && uid.toString() !== myself.uid.toString())?i18n("users.delete"):false,
                    fields: [
                        {
                            id: "uid",
                            type: "text",
                            readonly: true,
                            value: response.user.uid.toString(),
                            title: i18n("users.uid"),
                        },
                        {
                            id: "login",
                            type: "text",
                            readonly: true,
                            value: response.user.login,
                            title: i18n("users.login"),
                        },
                        {
                            id: "realName",
                            type: "text",
                            readonly: false,
                            value: response.user.realName,
                            title: i18n("users.realName"),
                            placeholder: i18n("users.realName"),
                            validate: (v) => {
                                return $.trim(v) !== "";
                            }
                        },
                        {
                            id: "eMail",
                            type: "email",
                            readonly: false,
                            value: response.user.eMail,
                            title: i18n("eMail"),
                            placeholder: i18n("eMail"),
                            hidden: !parseInt(response.user.uid),
                        },
                        {
                            id: "primaryGroup",
                            type: "select2",
                            value: response.user.primaryGroup,
                            options: gs,
                            title: i18n("users.primaryGroup"),
                            hidden: !parseInt(response.user.uid) || gs.length == 0,
                        },
                        {
                            id: "phone",
                            type: "tel",
                            readonly: false,
                            value: response.user.phone,
                            title: i18n("phone"),
                            placeholder: i18n("phone"),
                            hidden: !parseInt(response.user.uid),
                        },
                        {
                            id: "tg",
                            type: "number",
                            readonly: false,
                            value: response.user.tg,
                            title: i18n("users.tg"),
                            placeholder: i18n("users.tg"),
                            hidden: !parseInt(response.user.uid),
                        },
                        {
                            id: "notification",
                            type: "select",
                            readonly: false,
                            value: response.user.notification,
                            title: i18n("users.notification"),
                            placeholder: i18n("users.notification"),
                            options: [
                                {
                                    value: "none",
                                    text: i18n("users.notificationNone"),
                                },
                                {
                                    value: "tgEmail",
                                    text: i18n("users.notificationTgEmail"),
                                },
                                {
                                    value: "emailTg",
                                    text: i18n("users.notificationEmailTg"),
                                },
                                {
                                    value: "tg",
                                    text: i18n("users.notificationTg"),
                                },
                                {
                                    value: "email",
                                    text: i18n("users.notificationEmail"),
                                },
                            ],
                            hidden: !parseInt(response.user.uid),
                            validate: (v) => {
                                return $.trim(v) !== "";
                            }
                        },
                        {
                            id: "password",
                            type: "password",
                            title: i18n("password"),
                            placeholder: i18n("password"),
                            readonly: uid.toString() === "0",
                            hidden: uid.toString() === "0",
                            validate: (v, prefix) => {
                                return ($.trim(v).length === 0) || ($.trim(v).length >= 8 && $(`#${prefix}password`).val() === $(`#${prefix}confirm`).val());
                            }
                        },
                        {
                            id: "confirm",
                            type: "password",
                            title: i18n("confirm"),
                            placeholder: i18n("confirm"),
                            readonly: uid.toString() === "0",
                            hidden: uid.toString() === "0",
                            validate: (v, prefix) => {
                                return ($.trim(v).length === 0) || ($.trim(v).length >= 8 && $(`#${prefix}password`).val() === $(`#${prefix}confirm`).val());
                            }
                        },
                        {
                            id: "defaultRoute",
                            type: "text",
                            readonly: false,
                            value: response.user.defaultRoute,
                            title: i18n("users.defaultRoute"),
                            placeholder: "#route",
                            button: {
                                class: "fas fa-bookmark",
                                click: prefix => {
                                    $(`#${prefix}defaultRoute`).val("#" + location.href.split("#")[1]);
                                },
                            },
                            validate: (v) => {
                                return $.trim(v) === "" || $.trim(v)[0] === "#";
                            }
                        },
                        {
                            id: "persistentToken",
                            type: "text",
                            readonly: false,
                            value: parseInt(uid)?response.user.persistentToken:'',
                            title: i18n("users.persistentToken"),
                            placeholder: i18n("users.persistentToken"),
                            hidden: !parseInt(uid),
                            button: {
                                class: "fas fa-magic",
                                click: prefix => {
                                    $(`#${prefix}persistentToken`).val(md5(Math.random() + (new Date())));
                                },
                            },
                            validate: (v) => {
                                return $.trim(v) === "" || $.trim(v).length === 32;
                            }
                        },
                        {
                            id: "disabled",
                            type: "select",
                            value: response.user.enabled?"no":"yes",
                            title: i18n("users.disabled"),
                            readonly: uid.toString() === myself.uid.toString(),
                            hidden: uid.toString() === myself.uid.toString() && !parseInt(response.user.uid),
                            options: [
                                {
                                    value: "yes",
                                    text: i18n("yes"),
                                },
                                {
                                    value: "no",
                                    text: i18n("no"),
                                },
                            ]
                        },
                    ],
                    callback: function (result) {
                        if (result.delete === "yes") {
                            modules.users.deleteUser(result.uid);
                        } else {
                            result.enabled = result.disabled === "no";
                            modules.users.doModifyUser(result);
                        }
                    },
                }).show();
            }).
            fail(FAIL).
            always(loadingDone);
        }

        if (!myself.uid) {
            myself.uid = 0;
        }

        loadingStart();
        if (modules.groups) {
            modules.groups.loadGroups(() => {
                realModifyUser(uid);
            });
        } else {
            realModifyUser(uid);
        }
    },

    deleteUser: function (uid) {
        mConfirm(i18n("users.confirmDelete", uid.toString()), i18n("confirm"), `danger:${i18n("users.delete")}`, () => {
            modules.users.doDeleteUser(uid);
        });
    },

    /*
        main form (users) render function
     */

    dropSession: function (token, uid) {
        mConfirm(i18n("users.confirmDropSession"), i18n("users.dropSession"), i18n("users.dropSession"), () => {
            DELETE("accounts", "user", false, {
                session: token,
            }).
            fail(FAIL).
            fail(loadingDone).
            done(() => {
                modules.users.showSessions(uid);
            });
        });
    },

    showSessions: function (uid) {
        loadingStart();

        QUERY("accounts", "users", { withSessions: true }, true).done(response => {
            modules.users.users = response.users;

            cardTable({
                target: "#altForm",
                title: {
                    caption: i18n("users.sessions") + " " + i18n("users.uid") + uid,
                    altButton: {
                        caption: i18n("close"),
                        click: () => {
                            $("#altForm").hide();
                        },
                    },
                },
                columns: [
                    {
                        title: i18n("users.sessionType"),
                        nowrap: true,
                    },
                    {
                        title: i18n("users.ip"),
                        nowrap: true,
                    },
                    {
                        title: i18n("users.started"),
                        nowrap: true,
                    },
                    {
                        title: i18n("users.updated"),
                        nowrap: true,
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];
    
                    let user = {};
    
                    for (let i in modules.users.users) {
                        if (modules.users.users[i].uid == uid) {
                            user = modules.users.users[i];
                            break;
                        }
                    }
    
                    for (let i in user.sessions) {
                        rows.push({
                            uid: user.sessions[i].token,
                            cols: [
                                {
                                    data: (user.sessions[i].did == "Base64")?i18n("users.sessionBase64"):(user.sessions[i].byPersistentToken?i18n("users.sessionPersistent"):i18n("users.sessionOrdinal")),
                                    nowrap: true,
                                },
                                {
                                    data: user.sessions[i].ip,
                                    nowrap: true,
                                },
                                {
                                    data: ttDate(user.sessions[i].started),
                                    nowrap: true,
                                },
                                {
                                    data: ttDate(user.sessions[i].updated),
                                    nowrap: true,
                                    fullWidth: true,
                                },
                            ],
                            dropDown: {
                                items: [
                                    {
                                        icon: "fas fa-trash-alt",
                                        title: i18n("users.dropSession"),
                                        class: "text-danger",
                                        disabled: user.sessions[i].byPersistentToken || user.sessions[i].token == lStore("_token"),
                                        click: token => {
                                            modules.users.dropSession(token, uid);
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

    loadGroups: function (callback) {
        if (modules.groups) {
            modules.groups.loadGroups(callback);
        } else {
            callback(false);
        }
    },

    render: function (params) {
        $("#altForm").hide();
        $("#subTop").html("");

        loadingStart();

        function realRenderUsers() {
            let groups = {};

            let hasGroups = false;

            if (modules.groups) {
                for (let i in modules.groups.meta) {
                    groups[modules.groups.meta[i].gid] = modules.groups.meta[i];
                    hasGroups = true;
                }
            }

            QUERY("accounts", "users", { withSessions: true }, true).done(response => {
                modules.users.users = response.users;
    
                cardTable({
                    target: "#mainForm",
                    title: {
                        button: {
                            caption: i18n("users.addUser"),
                            click: modules.users.addUser,
                        },
                        caption: i18n("users.users"),
                        filter: true,
                    },
                    startPage: modules.users.startPage,
                    pageChange: page => {
                        modules.users.startPage = page;
                    },
                    edit: modules.users.modifyUser,
                    columns: [
                        {
                            title: i18n("users.uid"),
                        },
                        {
                            title: i18n("users.login"),
                        },
                        {
                            title: i18n("users.lastLogin"),
                            hidden: !(response.users.length || typeof response.users[0].lastLogin == "undefined"),
                        },
                        {
                            title: i18n("users.lastAction"),
                            hidden: !(response.users.length || typeof response.users[0].lastAction == "undefined"),
                        },
                        {
                            title: i18n("users.blockedShort"),
                        },
                        {
                            title: i18n("users.primaryGroup"),
                            hidden: !hasGroups,
                        },
                        {
                            title: i18n("users.realName"),
                            fullWidth: true,
                        },
                        {
                            title: i18n("Почта"),
                        },
                        {
                            title: i18n("users.telegram"),
                            nowrap: true,
                        },
                        {
                            title: i18n("phone"),
                        },
                    ],
                    rows: () => {
                        let rows = [];
    
                        for (let i = 0; i < response.users.length; i++) {
                            if (!parseInt(response.users[i].uid)) continue;

                            rows.push({
                                uid: response.users[i].uid.toString(),
                                class: (response.users[i].enabled == 1)?"bg-white":"bg-light",
                                cols: [
                                    {
                                        data: response.users[i].uid,
                                    },
                                    {
                                        data: response.users[i].login,
                                        nowrap: true,
                                    },
                                    {
                                        data: ttDate(response.users[i].lastLogin),
                                        nowrap: true,
                                        hidden: typeof response.users[i].lastLogin == "undefined",
                                    },
                                    {
                                        data: ttDate(response.users[i].lastAction),
                                        nowrap: true,
                                        hidden: typeof response.users[i].lastAction == "undefined",
                                    },
                                    {
                                        data: response.users[i].enabled?i18n("no"):i18n("yes"),
                                        nowrap: true,
                                    },
                                    {
                                        data: groups[response.users[i].primaryGroup]?groups[response.users[i].primaryGroup].name:("<span class='text-bold text-danger'>" + i18n("no") + "</span>"),
                                        nowrap: true,
                                        hidden: !hasGroups,
                                    },
                                    {
                                        data: response.users[i].realName?response.users[i].realName:i18n("no"),
                                        nowrap: true,
                                        fullWidth: true,
                                    },
                                    {
                                        data: (response.users[i].eMail && response.users[i].eMail != response.users[i].login)?i18n("yes"):("<span class='text-bold text-danger'>" + i18n("no") + "</span>"),
                                        click: response.users[i].eMail?`mailto:${response.users[i].eMail}`:false,
                                        nowrap: true,
                                    },
                                    {
                                        data: response.users[i].tg?i18n("yes"):("<span class='text-bold text-danger'>" + i18n("no") + "</span>"),
                                        nowrap: true,
                                    },
                                    {
                                        data: response.users[i].phone?response.users[i].phone:("<span class='text-bold text-danger'>" + i18n("no") + "</span>"),
                                        nowrap: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-list-ol",
                                            title: i18n("users.sessions"),
                                            disabled: !response.users[0].sessions,
                                            click: uid => {
                                                modules.users.showSessions(uid);
                                            },
                                        },
                                    ],
                                },
                            });
                        }
    
                        return rows;
                    },
                });
    
                if (params && params.sessions && params.sessions !== true) {
                    modules.users.showSessions(params.sessions);
                }
                
                loadingDone();
            }).
            fail(FAIL).
            fail(loadingDone);
        }

        if (modules.groups) {
            modules.groups.loadGroups(realRenderUsers);
        } else {
            realRenderUsers();
        }
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("users.users");

        modules.users.render(params);
    }
}).init();