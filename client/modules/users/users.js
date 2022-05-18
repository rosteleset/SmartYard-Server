({
    init: function () {
        $("#leftside-menu").append(`
            <li class="nav-item" title="${i18n("users.users")}">
                <a href="#users" class="nav-link">
                    <i class="fas fa-fw fa-user nav-icon"></i>
                    <p>${i18n("users.users")}</p>
                </a>
            </li>
        `);
        moduleLoaded("users", this);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("users.users");
        GET("accounts", "users").done(response => {
            cardTable({
                addButton: {
                    title: i18n("users.addUser"),
                    id: "userAddButton"
                },
                title: i18n("users.users"),
                filter: true,
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
                        title: i18n("users.eMail"),
                    },
                    {
                        title: i18n("users.phone"),
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in response.users) {
                        rows.push({
                            cols: [
                                {
                                    data: response.users[i].uid,
                                },
                                {
                                    data: response.users[i].login,
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
                            class: "userRow pointer",
                            uid: response.users[i].uid,
                        });
                    }

                    return rows;
                },
                target: "#mainForm",
            });

            cardForm({
//                title: "Заголовок карточки",
                tableHeader: "Заголовок формы (таблицы)",
//                target: "#mainForm",
                borderless: true,
                modal: true,
                fields: [
                    {
                        id: "uid",
                        type: "text",
                        readonly: true,
                        value: "0",
                        title: i18n("users.uid"),
                    },
                    {
                        id: "login",
                        type: "text",
                        readonly: true,
                        value: "admin",
                        title: i18n("users.login"),
                    },
                    {
                        id: "realName",
                        type: "text",
                        readonly: false,
                        value: "",
                        title: i18n("users.realName"),
                        placeholder: i18n("users.realName"),
                    },
                    {
                        id: "eMail",
                        type: "email",
                        readonly: false,
                        value: "",
                        title: i18n("users.eMail"),
                        placeholder: i18n("users.eMail"),
                    },
                    {
                        id: "phone",
                        type: "tel",
                        readonly: false,
                        value: "",
                        title: i18n("users.phone"),
                        placeholder: i18n("users.phone"),
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
                callback: result => {
                    console.log(result);
                },
            });

            $("#userAddButton").off("click").on("click", () => {
                alert("add user");
            });

            $(".userRow").off("click").on("click", function () {
                alert("user click: " + $(this).attr("uid"));
            });

        }).fail(response => {
            if (response && response.responseJSON && response.responseJSON.error) {
                error(i18n("errors." + response.responseJSON.error), "[" + i18n("users.users") + "]: " + i18n("error"), 30);
            } else {
                error(i18n("errors.unknown"), "[" + i18n("users.users") + "]: " + i18n("error"), 30);
            }
        }).always(() => {
            loadingDone();
        });
    }
}).init();