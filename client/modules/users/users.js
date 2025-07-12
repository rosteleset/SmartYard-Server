({
    startPage: 1,
    meta: false,

    init: function () {
        if (AVAIL("accounts", "user", "POST") || AVAIL("accounts", "user", "DELETE")) {
            leftSide("fas fa-fw fa-user", i18n("users.users"), "?#users", "accounts");
        }
        moduleLoaded("users", this);
    },

    loadUsers: function (callback, withSessions) {
        let f = {
            meta: false,

            done: function (callback) {
                if (typeof callback == "function") callback(this.meta);
                return this;
            },

            fail: function () {
                return this;
            },

            always: function () {
                return this;
            },
        }

        if (!withSessions && modules.users.meta) {
            f.meta = modules.users.meta;
            if (typeof callback == "function") callback(modules.users.meta);
            return f;
        } else {
            return QUERY("accounts", "users", withSessions ? { withSessions: true } : false).
            done(users => {
                modules.users.meta = users.users;
            }).
            always(() => {
                if (typeof callback == "function") callback(modules.users.meta);
            });
        }
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
        fail(loadingDone).
        done(() => {
            if (user.userGroups && AVAIL("accounts", "userGroups", "PUT")) {
                PUT("accounts", "userGroups", user.uid, {
                    gids: user.userGroups,
                }).
                fail(FAIL).
                fail(loadingDone).
                done(() => {
                    if (user.uid == myself.uid) {
                        whoAmI(true);
                    }
                    message(i18n("users.userWasChanged"));
                    if (currentPage === "users") {
                        modules.users.render();
                    } else {
                        loadingDone();
                    }
                });
            } else {
                if (user.uid == myself.uid) {
                    whoAmI(true);
                }
                message(i18n("users.userWasChanged"));
                if (currentPage === "users") {
                    modules.users.render();
                } else {
                    loadingDone();
                }
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
                    validate: v => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "realName",
                    type: "text",
                    title: i18n("users.realName"),
                    placeholder: i18n("users.realName"),
                    validate: v => {
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
        });
    },

    modifyUser: function (uid) {

        function realModifyUser(uid) {
            GET("accounts", "user", uid, true).done(response => {
                let gs = [];
                let gu = [];

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
                        gu.push({
                            id: modules.groups.meta[i].gid,
                            text: $.trim(modules.groups.meta[i].name + " [" + modules.groups.meta[i].acronym + "]"),
                            checked: parseInt(modules.groups.meta[i].gid) == parseInt(response.user.primaryGroup) || (response.user.groups.filter(group => parseInt(group.gid) == parseInt(modules.groups.meta[i].gid)).length),
                            disabled: parseInt(modules.groups.meta[i].gid) == parseInt(response.user.primaryGroup),
                        });
                    }
                }

                let cropper = false;
                let croppable = false;
                let avatar = false;
                let originalAvatar = false;

                function crop() {
                    let croppedCanvas;

                    croppedCanvas = cropper.getCroppedCanvas();

                    let canvas = document.createElement("canvas");
                    let context = canvas.getContext("2d");
                    let width = croppedCanvas.width;
                    let height = croppedCanvas.height;

                    canvas.width = width;
                    canvas.height = height;
                    context.imageSmoothingEnabled = true;
                    context.drawImage(croppedCanvas, 0, 0, width, height);
                    context.globalCompositeOperation = "destination-in";
                    context.beginPath();
                    context.arc(width / 2, height / 2, Math.min(width, height) / 2, 0, 2 * Math.PI, true);
                    context.fill();
                    croppedCanvas = canvas;

                    cropper.destroy();
                    cropper = false;

                    avatar = croppedCanvas.toDataURL();
                }

                cardForm({
                    title: i18n("users.edit"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    size: "lg",
                    delete: (uid.toString() !== "0" && uid.toString() !== myself.uid.toString() && AVAIL("accounts", "user", "DELETE")) ? i18n("users.delete") : false,
                    deleteTab: (uid.toString() !== "0" && uid.toString() !== myself.uid.toString() && AVAIL("accounts", "user", "DELETE")) ? i18n("users.primary") : false,
                    fields: [
                        {
                            id: "uid",
                            type: "text",
                            readonly: true,
                            value: uid.toString(),
                            title: i18n("users.uid"),
                            tab: i18n("users.primary"),
                        },
                        {
                            id: "login",
                            type: "text",
                            readonly: true,
                            value: response.user.login,
                            title: i18n("users.login"),
                            tab: i18n("users.primary"),
                        },
                        {
                            id: "realName",
                            type: "text",
                            readonly: false,
                            value: response.user.realName,
                            title: i18n("users.realName"),
                            placeholder: i18n("users.realName"),
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            tab: i18n("users.contacts"),
                        },
                        {
                            id: "eMail",
                            type: "email",
                            readonly: false,
                            value: response.user.eMail,
                            title: i18n("eMail"),
                            placeholder: i18n("eMail"),
                            tab: i18n("users.contacts"),
                        },
                        {
                            id: "primaryGroup",
                            type: "select2",
                            value: response.user.primaryGroup,
                            options: gs,
                            title: i18n("users.primaryGroup"),
                            hidden: (!parseInt(response.user.uid) || gs.length == 0) || !AVAIL("accounts", "groupUsers", "PUT"),
                            tab: i18n("users.primary"),
                        },
                        {
                            id: "phone",
                            type: "tel",
                            readonly: false,
                            value: response.user.phone,
                            title: i18n("phone"),
                            placeholder: i18n("phone"),
                            tab: i18n("users.contacts"),
                        },
                        {
                            id: "tg",
                            type: "number",
                            readonly: false,
                            value: response.user.tg,
                            title: i18n("users.tg"),
                            placeholder: i18n("users.tg"),
                            tab: i18n("users.contacts"),
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
                            validate: v => {
                                return $.trim(v) !== "";
                            },
                            tab: i18n("users.contacts"),
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
                            },
                            button: {
                                class: "fas fa-fw fa-magic",
                                hint: i18n("users.generatePassword"),
                                click: prefix => {
                                    PWGen.initialize();
                                    let p = PWGen.generate();
                                    $(`#${prefix}password`).val(p);
                                    $(`#${prefix}confirm`).val(p);
                                },
                            },
                            tab: i18n("users.primary"),
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
                            },
                            button: {
                                class: "fas fa-fw fa-eye",
                                hint: i18n("users.showPassword"),
                                click: prefix => {
                                    if ($(`#${prefix}password`).attr("type") == "password") {
                                        $(`#${prefix}password`).attr("type", "text");
                                        $(`#${prefix}confirm`).attr("type", "text");
                                    } else {
                                        $(`#${prefix}password`).attr("type", "password");
                                        $(`#${prefix}confirm`).attr("type", "password");
                                    }
                                },
                            },
                            tab: i18n("users.primary"),
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
                                    $(`#${prefix}defaultRoute`).val("#" + window.location.href.split("#")[1]);
                                },
                            },
                            validate: v => {
                                return $.trim(v) === "" || $.trim(v)[0] === "#";
                            },
                            tab: i18n("users.primary"),
                        },
                        {
                            id: "persistentToken",
                            type: "text",
                            readonly: false,
                            value: parseInt(uid)?response.user.persistentToken:'',
                            title: i18n("users.persistentToken"),
                            placeholder: i18n("users.persistentToken"),
                            hidden: !parseInt(uid) || parseInt(response.user.twoFA),
                            button: {
                                class: "fas fa-magic",
                                click: prefix => {
                                    $(`#${prefix}persistentToken`).val(md5(Math.random() + (new Date())));
                                },
                            },
                            validate: v => {
                                return $.trim(v) === "" || $.trim(v).length === 32;
                            },
                            tab: i18n("users.primary"),
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
                            ],
                            tab: i18n("users.primary"),
                        },
                        {
                            id: "userGroups",
                            type: "multiselect",
                            title: false,
                            tab: i18n("users.userGroups"),
                            hidden: !parseInt(response.user.uid) || gu.length == 0 || !AVAIL("accounts", "userGroups", "PUT"),
                            noHover: true,
                            allButtons: false,
                            options: gu,
                            filter: true,
                            singleColumn: true,
                        },
                        {
                            id: "2faCode",
                            type: "empty",
                            title: i18n("users.2faCode"),
                            hidden: uid != myself.uid || parseInt(response.user.twoFA),
                            tab: i18n("users.2fa"),
                        },
                        {
                            id: "2faSecret",
                            type: "text",
                            title: i18n("users.2faSecret"),
                            hidden: uid != myself.uid || parseInt(response.user.twoFA),
                            readonly: true,
                            tab: i18n("users.2fa"),
                        },
                        {
                            id: "2faConfirm",
                            type: "text",
                            title: i18n("users.2faConfirm"),
                            hidden: uid != myself.uid || parseInt(response.user.twoFA),
                            tab: i18n("users.2fa"),
                            button: {
                                class: "fas fa-fw fa-power-off",
                                hint: i18n("users.enable2FA"),
                                click: prefix => {
                                    if ($.trim($("#" + prefix + "2faConfirm").val())) {
                                        mConfirm(i18n("users.enable2FA") + "?", i18n("confirm"), i18n("users.enable2FA"), () => {
                                            POST("authentication", "two_fa", false, {
                                                oneCode: $.trim($("#" + prefix + "2faConfirm").val()),
                                            }).done(() => {
                                                doLogout(true);
                                            }).fail(response => {
                                                if (response && response.responseJSON && response.responseJSON.error && response.getResponseHeader("x-last-error")) {
                                                    error(i18n("errors." + response.getResponseHeader("x-last-error")), i18n("error"), 30);
                                                } else {
                                                    FAIL(response);
                                                }
                                            });
                                        });
                                    }
                                },
                            },
                        },
                        {
                            id: "avatar",
                            type: "empty",
                            title: false,
                            tab: i18n("users.avatar"),
                            noHover: true,
                            singleColumn: true,
                        }
                    ],

                    done: function (prefix) {
                        POST("authentication", "two_fa", false, {
                            //
                        }).done(result => {
                            let secret;
                            try {
                                secret = (new URL(result.two_fa)).searchParams.get("secret");
                            } catch (_) {

                            }
                            $("#" + prefix + "2faSecret").val(secret);
                            (new QRCode(document.getElementById(prefix + "2faCode"), {
                                width: 256,
                                height: 256,
                            })).makeCode(result.two_fa);
                            $($("#" + prefix + "2faCode").children()[1]).css("width", "50%");
                        });

                        $("#" + prefix + "avatar").html(`
                            <div id="${prefix}avatar-span" class="paste-target">
                                <img id="${prefix}avatar-image" width="100%" class="cropper-view-box" />
                            </div>
                            <div class="mt-2">
                                <button id="${prefix}avatar-load" type="button" class="btn btn-secondary mr-2" title="${i18n("users.avatarLoad")}"><i class="fas fa-fw fa-user-circle"></i></button>
                                <button id="${prefix}avatar-apply" type="button" class="btn btn-success mr-2" title="${i18n("users.avatarApply")}"><i class="fas fa-fw fa-crop-alt"></i></button>
                                <button id="${prefix}avatar-clear" type="button" class="btn btn-primary mr-2" title="${i18n("users.avatarClear")}"><i class="fas fa-fw fa-undo-alt"></i></button>
                                <button id="${prefix}avatar-delete" type="button" class="btn btn-danger mr-2" title="${i18n("users.avatarDelete")}"><i class="fas fa-fw fa-recycle"></i></button>
                            </div>
                        `).css("width", "50%");

                        function checkABtn() {
                            $("#" + prefix + "avatar-load").removeClass("disabled");
                            if (croppable) {
                                $("#" + prefix + "avatar-apply").removeClass("disabled");
                            } else {
                                $("#" + prefix + "avatar-apply").addClass("disabled");
                            }
                            if ((avatar && avatar != originalAvatar ) || cropper) {
                                $("#" + prefix + "avatar-clear").removeClass("disabled");
                            } else {
                                $("#" + prefix + "avatar-clear").addClass("disabled");
                            }
                            $("#" + prefix + "avatar-delete").removeClass("disabled");
                            if (avatar && avatar == "img/noavatar.png") {
                                $("#" + prefix + "avatar-delete").addClass("disabled");
                            } else {
                                if (originalAvatar && originalAvatar == "img/noavatar.png") {
                                    $("#" + prefix + "avatar-delete").addClass("disabled");
                                }
                            }
                        }

                        $("#" + prefix + "avatar-load").on("click", () => {
                            avatar = false;

                            xblur();

                            $("#fileInput").attr("accept", "image/*");

                            $("#fileInput").off("change").val("").click().on("change", () => {
                                let files = document.querySelector("#fileInput").files;

                                if (files.length === 0) {
                                    error(i18n("noFileSelected"));
                                    return;
                                }

                                if (files.length > 1) {
                                    error(i18n("multiuploadNotSupported"));
                                    return;
                                }

                                let file = files[0];

                                if (file.size > 0.5 * 1024 * 1024) {
                                    error("exceededSize");
                                    return;
                                }

                                fetch(URL.createObjectURL(file)).then(response => {
                                    return response.blob();
                                }).then(blob => {
                                    setTimeout(() => {
                                        let reader = new FileReader();
                                        reader.onloadend = () => {
                                            if (cropper) {
                                                cropper.destroy();
                                                cropper = false;
                                            }

                                            $("#" + prefix + "avatar-image").attr("src", reader.result);

                                            cropper = new Cropper(document.getElementById(prefix + "avatar-image"), {
                                                aspectRatio: 1,
                                                viewMode: 1,
                                                ready: function () {
                                                    croppable = true;
                                                    checkABtn();
                                                },
                                            });
                                        };
                                        reader.readAsDataURL(blob);
                                    }, 100);
                                });
                            });
                        });

                        $("#" + prefix + "avatar-apply").on("click", () => {
                            xblur();

                            if (croppable) {
                                crop();

                                $("#" + prefix + "avatar-image").attr("src", avatar);

                                croppable = false;
                            }

                            checkABtn();
                        });

                        $("#" + prefix + "avatar-clear").on("click", () => {
                            xblur();

                            avatar = false;
                            croppable = false;

                            if (cropper) {
                                cropper.destroy();
                                cropper = false;
                            }

                            checkABtn();

                            QUERYID("user", "avatar", uid, false, true).
                            always(a => {
                                if (a && a.avatar) {
                                    $("#" + prefix + "avatar-image").attr("src", a.avatar);
                                    originalAvatar = a.avatar;
                                    checkABtn();
                                } else {
                                    if ($.trim($("#" + prefix + "eMail").val())) {
                                        let gravUrl = "https://www.gravatar.com/avatar/" + md5($.trim($("#" + prefix + "eMail").val()).toLowerCase()) + "?s=256&d=404";
                                        originalAvatar = gravUrl;
                                        checkABtn();
                                        $("#" + prefix + "avatar-image").on("error", function () {
                                            $("#" + prefix + "avatar-image").attr("src", "img/noimage.png");
                                            originalAvatar = false;
                                            checkABtn();
                                        }).attr("src", gravUrl);
                                    } else {
                                        $("#" + prefix + "avatar-image").attr("src", "img/noimage.png");
                                        originalAvatar = false;
                                        checkABtn();
                                    }
                                }
                            });
                        }).click();

                        $("#" + prefix + "avatar-delete").on("click", () => {
                            xblur();

                            avatar = "img/noavatar.png";
                            croppable = false;

                            if (cropper) {
                                cropper.destroy();
                                cropper = false;
                            }

                            $("#" + prefix + "avatar-image").attr("src", avatar);

                            checkABtn();
                        });

                        $("#" + prefix + "avatar-image").on("click", () => {
                            $("#" + prefix + "avatar-load").click();
                        });

                        $("#" + prefix + "avatar-span").on("proxy-paste", (e, f) => {
                            if (f && f[0]) {
                                if (f[0].type.startsWith('image/')) {
                                    let blob = URL.createObjectURL(f[0]);
                                    if (blob) {
                                        if (cropper) {
                                            cropper.destroy();
                                            cropper = false;
                                        }

                                        $("#" + prefix + "avatar-image").attr("src", blob);

                                        croppable = false;

                                        cropper = new Cropper(document.getElementById(prefix + "avatar-image"), {
                                            aspectRatio: 1,
                                            viewMode: 1,
                                            ready: function () {
                                                croppable = true;
                                                checkABtn();
                                            },
                                        });
                                    }
                                }
                            }
                        });

                    },

                    callback: function (result) {
                        if (croppable) {
                            crop();
                        }

                        if (avatar) {
                            if (myself.uid.toString() == uid.toString()) {
                                $(".userAvatar").attr("src", avatar);
                            }
                            PUT("user", "avatar", uid.toString(), { avatar });
                        }

                        if (!gu.length) {
                            result.userGroups = false;
                        }

                        if (result.delete === "yes") {
                            modules.users.deleteUser(result.uid);
                        } else {
                            if ((!parseInt(response.user.uid) || gs.length == 0) || !AVAIL("accounts", "groupUsers", "PUT")) {
                                delete result.primaryGroup;
                            }
                            result.enabled = result.disabled === "no";
                            modules.users.doModifyUser(result);
                        }
                    },
                });
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
            modules.users.meta = response.users;

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

                    for (let i in modules.users.meta) {
                        if (modules.users.meta[i].uid == uid) {
                            user = modules.users.meta[i];
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
        subTop();

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

            QUERY("accounts", "users", { withSessions: true, withLast: true }, true).done(response => {
                modules.users.meta = response.users;

                cardTable({
                    target: "#mainForm",
                    title: {
                        button: AVAIL("accounts", "user", "POST") ? {
                            caption: i18n("users.addUser"),
                            click: modules.users.addUser,
                        } : undefined,
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
                        },
                        {
                            title: i18n("users.lastAction"),
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
                            title: `<i class="fas fa-fw fa-shield-alt" title="${i18n("users.2fa")}"></i>`,
                        },
                        {
                            title: `<i class="fas fa-fw fa-at" title="${i18n("eMail")}"></i>`,
                        },
                        {
                            title: `<i class="fab fa-fw fa-telegram" title="${i18n("users.tg")}"></i>`,
                        },
                        {
                            title: `<i class="fas fa-fw fa-mobile-alt" title="${i18n("phone")}"></i>`,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        const defaultClass = modules.darkmode && modules.darkmode.isDark() ? 'bg-dark' : 'bg-white';

                        for (let i = 0; i < response.users.length; i++) {
                            if (!parseInt(response.users[i].uid)) continue;

                            let cl = (response.users[i].enabled == 1) ? defaultClass : "bg-light text-decoration-line-through";

                            if ((response.users[i].notification == "emailTg" || response.users[i].notification == "tgEmail" || response.users[i].notification == "email") && !(response.users[i].eMail && response.users[i].eMail != response.users[i].login)) {
                                cl = "bg-warning";
                            }

                            if ((response.users[i].notification == "tgEmail" || response.users[i].notification == "emailTg" || response.users[i].notification == "tg") && !response.users[i].tg) {
                                cl = "bg-warning";
                            }

                            rows.push({
                                uid: response.users[i].uid.toString(),
                                class: cl,
                                cols: [
                                    {
                                        data: response.users[i].uid,
                                    },
                                    {
                                        data: response.users[i].login,
                                        nowrap: true,
                                    },
                                    {
                                        data: response.users[i].lastLogin ? ttDate(response.users[i].lastLogin) : i18n("no"),
                                        nowrap: true,
                                    },
                                    {
                                        data: response.users[i].lastAction ? ttDate(response.users[i].lastAction) : i18n("no"),
                                        nowrap: true,
                                    },
                                    {
                                        data: groups[response.users[i].primaryGroup] ? groups[response.users[i].primaryGroup].name : i18n("no"),
                                        nowrap: true,
                                        hidden: !hasGroups,
                                    },
                                    {
                                        data: response.users[i].realName ? response.users[i].realName : i18n("no"),
                                        nowrap: true,
                                        fullWidth: true,
                                    },
                                    {
                                        data: parseInt(response.users[i].twoFA) ? i18n("yes") : i18n("no"),
                                        nowrap: true,
                                    },
                                    {
                                        data: (response.users[i].eMail && response.users[i].eMail != response.users[i].login) ? i18n("yes") : i18n("no"),
                                        click: response.users[i].eMail ? `mailto:${response.users[i].eMail}` : false,
                                        nowrap: true,
                                    },
                                    {
                                        data: (parseInt(response.users[i].tg) > 0) ? i18n("yes") : i18n("no"),
                                        nowrap: true,
                                    },
                                    {
                                        data: response.users[i].phone ? response.users[i].phone : i18n("no"),
                                        nowrap: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-list-ol",
                                            title: i18n("users.sessions"),
                                            disabled: !response.users[i].sessions || !response.users[i].sessions.length,
                                            click: uid => {
                                                modules.users.showSessions(uid);
                                            },
                                        },
                                        {
                                            title: "-",
                                        },
                                        {
                                            icon: "far fa-comment-alt",
                                            title: i18n("users.testNotify"),
                                            click: uid => {
                                                loadingStart();
                                                POST("user", "notify", uid, {
                                                    subject: i18n("users.testNotify"),
                                                    message: i18n("users.testNotify"),
                                                }).
                                                fail(FAIL).
                                                done(() => {
                                                    message(i18n("users.notificationSent"));
                                                }).
                                                always(loadingDone);
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