({
    startPage: 1,
    meta: [],

    init: function () {
        if (AVAIL("accounts", "user", "POST")) {
            leftSide("fas fa-fw fa-user", i18n("users.users"), "#users", "accounts");
        }
        moduleLoaded("users", this);
    },

    loadUsers: function (callback) {
        return GET("accounts", "users").
        done(users => {
            modules.users.meta = users.users;
        }).
        always(() => {
            if (typeof callback) callback();
        });
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

    doModifyUser: function (uid, realName, eMail, phone, enabled, password, defaultRoute) {
        loadingStart();
        PUT("accounts", "user", uid, {
            realName: realName,
            eMail: eMail,
            phone: phone,
            enabled: enabled,
            password: password,
            defaultRoute: defaultRoute,
        }).
        fail(FAIL).
        done(() => {
            if (uid == myself.uid) {
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
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "phone",
                    type: "tel",
                    title: i18n("phone"),
                    placeholder: i18n("phone"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.users.doAddUser(result.login, result.realName, result.eMail, result.phone);
            },
        }).show();
    },

    modifyUser: function (uid) {
        loadingStart();
        GET("accounts", "user", uid, true).done(response => {
            cardForm({
                title: i18n("users.edit"),
                footer: true,
                borderless: true,
                topApply: true,
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
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "phone",
                        type: "tel",
                        readonly: false,
                        value: response.user.phone,
                        title: i18n("phone"),
                        placeholder: i18n("phone"),
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
                        id: "disabled",
                        type: "select",
                        value: response.user.enabled?"no":"yes",
                        title: i18n("users.disabled"),
                        readonly: uid.toString() === myself.uid.toString(),
                        hidden: uid.toString() === myself.uid.toString(),
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
                        modules.users.doModifyUser(result.uid, result.realName, result.eMail, result.phone, result.disabled === "no", result.password, result.defaultRoute);
                    }
                },
            }).show();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    deleteUser: function (uid) {
        mConfirm(i18n("users.confirmDelete", uid.toString()), i18n("confirm"), `danger:${i18n("users.delete")}`, () => {
            modules.users.doDeleteUser(uid);
        });
    },

    /*
        main form (users) render function
     */

    render: function () {
        $("#altForm").hide();
        $("#subTop").html("");

        loadingStart();

        GET("accounts", "users", false, true).done(response => {
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
                        title: i18n("users.realName"),
                        fullWidth: true,
                    },
                    {
                        title: i18n("eMail"),
                    },
                    {
                        title: i18n("phone"),
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < response.users.length; i++) {
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
                                    data: response.users[i].realName?response.users[i].realName:i18n("no"),
                                    nowrap: true,
                                    fullWidth: true,
                                },
                                {
                                    data: response.users[i].eMail?response.users[i].eMail:i18n("no"),
                                    nowrap: true,
                                },
                                {
                                    data: response.users[i].phone?response.users[i].phone:i18n("no"),
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("users.users");

        modules.users.render();
    }
}).init();