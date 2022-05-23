({
    startPage: 1,

    init: function () {
        leftSide("fas fa-fw fa-user", i18n("users.users"), "#users");
        moduleLoaded("users", this);
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
        always(window.modules["users"].render);
    },

    doModifyUser: function (uid, realName, eMail, phone) {
        loadingStart();
        PUT("accounts", "user", uid, {
            realName: realName,
            eMail: eMail,
            phone: phone,
        }).
        fail(FAIL).
        always(window.modules["users"].render);
    },

    doDeleteUser: function (uid) {
        loadingStart();
        DELETE("accounts", "user", uid).
        fail(FAIL).
        always(window.modules["users"].render);
    },

    doSetPassword: function (uid, password) {
        loadingStart();
        POST("accounts", "setPassword", uid, {
            password: password,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("users.passwordWasChanged"));
        }).
        always(loadingDone);
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
                    title: i18n("users.phone"),
                    placeholder: i18n("users.phone"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                window.modules["users"].doAddUser(result.login, result.realName, result.eMail, result.phone);
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
                        title: i18n("users.phone"),
                        placeholder: i18n("users.phone"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        }
                    },
                    {
                        id: "delete",
                        type: "select",
                        readonly: false,
                        value: "",
                        title: i18n("users.delete"),
                        options: [
                            {
                                value: "",
                                text: "",
                            },
                            {
                                value: "yes",
                                text: i18n("yes"),
                            },
                        ]
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        window.modules["users"].deleteUser(result.uid);
                    } else {
                        window.modules["users"].doModifyUser(result.uid, result.realName, result.eMail, result.phone);
                    }
                },
            }).show();
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },

    deleteUser: function (uid) {
        mConfirm(i18n("users.confirmDelete", uid.toString()), i18n("confirm"), `danger:${i18n("users.delete")}`, () => {
            indow.modules["users"].doDeleteUser(uid);
        });
    },

    setPassword: function (uid) {
        cardForm({
            title: i18n("users.setPassword"),
            footer: true,
            borderless: true,
            topApply: false,
            fields: [
                {
                    id: "uid",
                    type: "text",
                    title: i18n("users.uid"),
                    placeholder: i18n("users.uid"),
                    value: uid.toString(),
                    readonly: true,
                },
                {
                    id: "password",
                    type: "password",
                    title: i18n("users.password"),
                    placeholder: i18n("users.password"),
                    validate: (v) => {
                        return $.trim(v).length >= 8;
                    }
                },
            ],
            callback: function (result) {
                window.modules["users"].doSetPassword(result.uid, result.password);
            },
        }).show();
    },

    contextItemClick: function (uid, action) {
        console.log(uid, action);
    },

    /*
        main form (users) render function
     */

    render: function () {
        loadingStart();

        GET("accounts", "users", false, true).done(response => {
            cardTable({
                addButton: {
                    title: i18n("users.addUser"),
                    click: window.modules["users"].addUser,
                },
                title: i18n("users.users"),
                filter: true,
                startPage: window.modules["users"].startPage,
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
                        title: i18n("users.phone"),
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < response.users.length; i++) {
                        rows.push({
                            uid: response.users[i].uid.toString(),
                            cols: [
                                {
                                    data: response.users[i].uid,
                                    click: window.modules["users"].modifyUser,
                                },
                                {
                                    data: response.users[i].login,
                                    click: window.modules["users"].modifyUser,
                                    nowrap: true,
                                },
                                {
                                    data: response.users[i].realName?response.users[i].realName:i18n("no"),
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
                            dropDown: [
                                {
                                    icon: "fas fa-tv",
                                    title: "Action 1",
                                    click: window.modules["users"].contextItemClick,
                                },
                                {
                                    icon: "fas fa-coffee",
                                    action: "coffee",
                                    title: "Action 2",
                                    click: window.modules["users"].contextItemClick,
                                },
                                {
                                    title: "-",
                                },
                                {
                                    icon: "fas fa-key",
                                    title: i18n("users.setPassword"),
                                    text: "text-primary",
                                    disabled: response.users[i].uid.toString() === "0",
                                    click: window.modules["users"].setPassword,
                                },
                                {
                                    title: "-",
                                },
                                {
                                    icon: "fas fa-trash-alt",
                                    title: i18n("users.delete"),
                                    text: "text-danger",
                                    disabled: response.users[i].uid.toString() === "0",
                                    click: window.modules["users"].deleteUser,
                                },
                            ],
                        });
                    }

                    return rows;
                },
                target: "#mainForm",
                pageChange: page => {
                    window.modules["users"].startPage = page;
                },
            });
        }).fail(FAIL).always(() => {
            loadingDone();
        });
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("users.users");

        window.modules["users"].render();
    }
}).init();