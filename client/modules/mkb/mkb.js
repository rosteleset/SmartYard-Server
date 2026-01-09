({
    menuItem: false,
    md: false,
    desks: [],
    deskNames: [],
    cards: {},
    calendars: {},

    subModules: [
        "columnTable",
    ],

    init: function () {
        if (parseInt(myself.uid) && AVAIL("mkb")) {
            this.menuItem = leftSide("fas fa-fw fa-layer-group", i18n("mkb.mkb"), "?#mkb", "productivity");
        }

        loadSubModules("mkb", JSON.parse(JSON.stringify(this.subModules)), this);
    },

    allLoaded: function () {
        //
    },

    mdr: function (str) {
        let f = convertLinks(DOMPurify.sanitize(rbtMdRender(str)));
        return f;
    },

    assignHandlers: function () {
        $(".subtasks").each(function () {
            let subtasks = $(this);
            new Sortable(document.getElementById(subtasks.attr("id")), {
                animation: 150,

                onEnd: e => {
                    console.log(e);
/*
                    $(`#${e.to.id}`).children().each(function () {
                        let el = $(this);
                    });
*/
                },
            });
        });

        $(".card-content").each(function () {
            let col = $(this);
            new Sortable(document.getElementById(col.attr("id")), {
                handle: ".card-handle",
                animation: 150,
                group: "cols",
                scroll: true,
                forceAutoScrollFallback: true,
                scrollSpeed: 25,

                onEnd: e => {
                    let s = $("#" + e.item.id).offset().top - $("#mainForm").offset().top - 8;
                    if ($("html").scrollTop() > s) {
                        $("html").scrollTo(s);
                    }

                    $(`#${e.to.id}`).children().each(function () {
                        console.log($(this).attr("id"));
                    });
                },
            });
        });

        new Sortable(document.getElementById("desk"), {
            handle: ".col-handle",
            animation: 150,

            onEnd: e => {
                $("#desk").children().each(function () {
                    console.log($(this).attr("id"));
                });
            },
        });

        $(".card-calendar").off("show.bs.dropdown").on("show.bs.dropdown", function () {
            let id = $(this).attr("data-card-id");
            let d = $(this).attr("data-date");

            if (!modules.mkb.calendars[id]) {
                $(`#dropdown-calendar-${id}`).html(`<span id='calendar-${id}'></span>`).off("click dragstart pointerdown mousedown touchstart dragover dragenter").on("click dragstart pointerdown mousedown touchstart dragover dragenter", e => {
                    e.stopPropagation();
                });

                modules.mkb.calendars[id] = new VanillaCalendarPro.Calendar(`#calendar-${id}`, {
                    locale: config.defaultLanguage,
                    selectionTimeMode: 24,
                    selectedMonth: date("m", d) - 1,
                    selectedYear: date("Y", d),
                    selectedDates: [ date("Y-m-d", d) ],
                    selectedTime: date("H:i", d),
                });

                modules.mkb.calendars[id].init();
            }
        });

        $(".card-calendar").off("hide.bs.dropdown").on("hide.bs.dropdown", function () {
            let id = $(this).attr("data-card-id");

            console.log(modules.mkb.calendars[id].context.selectedTime, modules.mkb.calendars[id].context.selectedDates);
        });

        $(".subtasks-progress").off("click").on("click", function () {
            let pb = $(this);
            let id = pb.attr("data-card-id");

            if (pb.hasClass("pointer")) {
                if (pb.attr("data-minimized") == "true") {
                    $(`.subtasks[data-card-id="${id}"]`).show();
                    $(`.hr-subject[data-card-id="${id}"]`).show();
                    pb.attr("data-minimized", "false").removeClass("pt-3").addClass("pt-1");
                } else {
                    $(`.subtasks[data-card-id="${id}"]`).hide();
                    $(`.hr-subject[data-card-id="${id}"]`).hide();
                    pb.attr("data-minimized", "true").addClass("pt-3").removeClass("pt-1");
                }
            }
        });

        $(".subtask-checkbox").off("change").on("change", function () {
            let id = $(this).attr("data-card-id");
            let p = 0, c = 0;

            $(this).parent().parent().children().each(function () {
                if ($(this).children().first().prop("checked")) {
                    p++;
                }
                c++;
            });

            p = Math.round((p / c) * 1000) / 10;

            $(`.progressbar-value[data-card-id="${id}"]`).css("width", p + "%").attr("aria-valuenow", p).attr("title", p + "%").text(p + "%");
        });

        $(".btn-min-max").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            if ($(`.btn-min-max[data-card-id="${id}"]`).children().first().hasClass("fa-compress-arrows-alt")) {
                $(`.btn-min-max[data-card-id="${id}"]`).children().first().removeClass("fa-compress-arrows-alt").addClass("fa-expand-arrows-alt").attr("title", i18n("mkb.restore"));
                $(`.subtasks-progress[data-card-id="${id}"]`).removeClass("pt-1").addClass("pt-3").removeClass("pointer");
                $(`.hr-subject[data-card-id="${id}"]`).hide();
                $(`.min-max[data-card-id="${id}"]`).hide();
                $(`.subtasks[data-card-id="${id}"]`).hide();
            } else {
                $(`.btn-min-max[data-card-id="${id}"]`).children().first().addClass("fa-compress-arrows-alt").removeClass("fa-expand-arrows-alt").attr("title", i18n("mkb.minimize"));
                let pb = $(`.subtasks-progress[data-card-id="${id}"]`);
                if (pb.attr("data-minimized") == "true") {
                    $(`.subtasks[data-card-id="${id}"]`).hide();
                    $(`.hr-subject[data-card-id="${id}"]`).hide();
                    pb.attr("data-minimized", "true").addClass("pt-3").removeClass("pt-1");
                } else {
                    $(`.subtasks[data-card-id="${id}"]`).show();
                    $(`.hr-subject[data-card-id="${id}"]`).show();
                    pb.attr("data-minimized", "false").removeClass("pt-3").addClass("pt-1");
                }
                pb.addClass("pointer");
                $(`.min-max[data-card-id="${id}"]`).show();
            }
        });

        $(".column-edit").off("click").on("click", function () {
            let id = $(this).parent().attr("data-column-id");

            let desk = modules.mkb.desk();

            let column = {};

            for (let i in desk.columns) {
                if (desk.columns[i]._id == id) {
                    column = desk.columns[i];
                    break;
                }
            }

            cardForm({
                title: i18n("mkb.modifyColumn"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("edit"),
                size: "lg",
                delete: i18n("delete"),
                fields: [
                    {
                        id: "id",
                        title: i18n("mkb.id"),
                        type: "text",
                        value: column._id,
                        readonly: true,
                    },
                    {
                        id: "title",
                        title: i18n("mkb.title"),
                        type: "text",
                        value: column.title,
                    },
                    {
                        id: "color",
                        title: i18n("mkb.color"),
                        type: "color",
                        value: column.color,
                        noEmpty: true,
                    },
                ],
                callback: r => {
                    for (let i in desk.columns) {
                        if (desk.columns[i]._id == id) {
                            if (r.delete) {
                                mConfirm(i18n("mkb.confirmDeleteColumn", r.title), i18n("confirm"), i18n("delete"), () => {
                                    desk.columns.splice(i, 1);

                                    loadingStart();
                                    POST("mkb", "desk", false, { desk }).
                                    done(() => {
                                        modules.mkb.renderDesk();
                                    }).
                                    fail(FAIL).
                                    fail(loadingDone);
                                });
                            } else {
                                desk.columns[i].title = r.title;
                                desk.columns[i].color = r.color;

                                loadingStart();
                                POST("mkb", "desk", false, { desk }).
                                done(() => {
                                    modules.mkb.renderDesk();
                                }).
                                fail(FAIL).
                                fail(loadingDone);
                            }
                            break;
                        }
                    }
                }
            });
        });

        $(".card-add").off("click").on("click", function () {
            let id = $(this).parent().attr("data-column-id");

            let desk = modules.mkb.desk();

            let column;

            for (let i in desk.columns) {
                if (desk.columns[i]._id == id) {
                    column = i;
                    break;
                }
            }

            let tags = [];

            for (let i in modules.mkb.cards) {
                for (let j in modules.mkb.cards[i].tags) {
                    tags.push(modules.mkb.cards[i].tags[j]);
                }
            }

            tags = tags.filter((v, i, a) => {
                return a.indexOf(v) === i;
            });

            tags.sort();

            cardForm({
                title: i18n("mkb.addCard"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("apply"),
                size: "xl",
                deleteTab: i18n("mkb.card"),
                fields: [
                    {
                        id: "subject",
                        title: i18n("mkb.subject"),
                        tab: i18n("mkb.card"),
                        type: "text",
                        validate: a => {
                            return !!$.trim(a);
                        }
                    },
                    {
                        id: "color",
                        title: i18n("mkb.color"),
                        tab: i18n("mkb.card"),
                        type: "color",
                        noEmpty: true,
                    },
                    {
                        id: "tags",
                        title: i18n("mkb.tags"),
                        tab: i18n("mkb.card"),
                        type: "select2",
                        multiple: true,
                        createTags: true,
                        colorizeTags: true,
                        tags: tags,
                    },
                    {
                        id: "body",
                        title: i18n("mkb.body"),
                        tab: i18n("mkb.card"),
                        noHover: true,
                        type: "code",
                        language: "markdown",
                    },
                    {
                        id: "subtasks",
                        title: false,
                        tab: i18n("mkb.subtasks"),
                        noHover: true,
                        type: "sortable",
                        options: [],
                        appendable: "input",
                        checkable: true,
                        editable: true,
                    },
                ],
                callback: r => {
                    r.desk = lStore("mkbDesk");
                    r.date = Math.round((new Date()).getTime() / 1000);

                    r.tags.sort();

                    loadingStart();
                    POST("mkb", "card", false, { card: r }).
                    done(a => {
                        r._id = $.trim(a);

                        if (!desk.columns[column].cards) {
                            desk.columns[column].cards = [];
                        }

                        desk.columns[column].cards.push(r._id);

                        POST("mkb", "desk", false, { desk }).
                        done(() => {
                            $(`#card-body-${id}`).append($(modules.mkb.renderCard(r)));
                            modules.mkb.cards[r._id] = r;
                            modules.mkb.assignHandlers();
                            loadingDone();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                }
            });
        });

        $(".cardEdit").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            let tags = [];

            for (let i in modules.mkb.cards) {
                for (let j in modules.mkb.cards[i].tags) {
                    tags.push(modules.mkb.cards[i].tags[j]);
                }
            }

            tags = tags.filter((v, i, a) => {
                return a.indexOf(v) === i;
            });

            tags.sort();

            cardForm({
                title: i18n("mkb.modifyCard"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("apply"),
                size: "xl",
                delete: i18n("delete"),
                deleteTab: i18n("mkb.card"),
                fields: [
                    {
                        id: "_id",
                        title: i18n("mkb.id"),
                        tab: i18n("mkb.card"),
                        type: "text",
                        readonly: true,
                        value: modules.mkb.cards[id]._id,
                    },
                    {
                        id: "subject",
                        title: i18n("mkb.subject"),
                        tab: i18n("mkb.card"),
                        type: "text",
                        value: modules.mkb.cards[id].subject,
                        validate: a => {
                            return !!$.trim(a);
                        }
                    },
                    {
                        id: "color",
                        title: i18n("mkb.color"),
                        tab: i18n("mkb.card"),
                        type: "color",
                        noEmpty: true,
                        value: modules.mkb.cards[id].color,
                    },
                    {
                        id: "tags",
                        title: i18n("mkb.tags"),
                        tab: i18n("mkb.card"),
                        type: "select2",
                        multiple: true,
                        createTags: true,
                        colorizeTags: true,
                        value: modules.mkb.cards[id].tags,
                        tags: tags,
                    },
                    {
                        id: "body",
                        title: i18n("mkb.body"),
                        tab: i18n("mkb.card"),
                        noHover: true,
                        type: "code",
                        language: "markdown",
                        value: modules.mkb.cards[id].body,
                    },
                    {
                        id: "subtasks",
                        title: false,
                        tab: i18n("mkb.subtasks"),
                        noHover: true,
                        type: "sortable",
                        options: [],
                        appendable: "input",
                        checkable: true,
                        editable: true,
                        value: modules.mkb.cards[id].subtasks,
                    },
                ],
                callback: r => {
                    if (r.delete) {

                    } else {
                        r.tags.sort();

                        modules.mkb.cards[id].subject = r.subject;
                        modules.mkb.cards[id].color = r.color;
                        modules.mkb.cards[id].tags = r.tags;
                        modules.mkb.cards[id].body = r.body;
                        modules.mkb.cards[id].subtasks = r.subtasks;

                        loadingStart();
                        POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
                        done(() => {
                            $(`#card-${id}`).replaceWith($(modules.mkb.renderCard(modules.mkb.cards[id])));
                            modules.mkb.assignHandlers();
                            loadingDone();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }
                }
            });
        });

        $(".cardComments").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");
            let ci = -1;
            let editor;

            function comment(i) {
                return `
                    <div class='noselect ${modules.mkb.cards[id].comments[i].modified ? "bg-warning" : "bg-info"} border-no-shadow pl-2 pr-2' style='font-size: 0.7rem; position: absolute; left: 6px; top: -10px;'>
                        ${date("H:i", modules.mkb.cards[id].comments[i].date)}
                    </div>
                    <div class='noselect bg-white border-no-shadow pl-2 pr-2' style='font-size: 0.7rem; position: absolute; left: 56px; top: -10px;'>
                        ${modules.users.login2name(modules.mkb.cards[id].comments[i].author)}
                    </div>
                    <div class='pointer noselect bg-danger border-no-shadow pl-1 pr-1 deleteComment' style='font-size: 0.7rem; position: absolute; right: 6px; top: -10px;'>
                        <i class='fas fa-fw fa-trash-alt'></i>
                    </div>
                    <div class='pointer noselect bg-primary border-no-shadow pl-1 pr-1 modifyComment' style='font-size: 0.7rem; position: absolute; right: 36px; top: -10px;'>
                        <i class='fas fa-fw fa-pencil-alt'></i>
                    </div>
                    ${convertLinks(rbtMdRender(modules.mkb.cards[id].comments[i].comment))}
                `;
            }

            function comments() {
                let h = '';
                let f = true;

                if (modules.mkb.cards[id].comments && modules.mkb.cards[id].comments.length) {
                    let d = "";
                    for (let i in modules.mkb.cards[id].comments) {
                        if (modules.mkb.cards[id].comments[i]) {
                            let x = date("Y-m-d", modules.mkb.cards[id].comments[i].date);
                            if (x != d) {
                                d = x;
                                if (!f) {
                                    h += '</div>';
                                }
                                f = false;
                                h += '<div class="commentsDay kanban-card-body mb-3">';
                                h += `<div class="mb-4"><hr class='hr-text-white-no-padding' data-content='${d}' style='margin-block: 0px ! important;' /></div>`;
                            }
                            h += `
                                <div class="ml-2 mb-3 mr-2 p-2 pt-3 kanban-card-body border-no-shadow comment" data-comment-index="${i}" style="position: relative;">
                                ${comment(i)}
                                </div>
                            `;
                        }
                    }
                }

                return h;
            }

            function assignHandlers() {
                $(".modifyComment").off("click").on("click", function () {
                    ci = $(this).parent().attr("data-comment-index");

                    $(`.comment`).css("border-color", "#dee2e6");
                    $(`.comment[data-comment-index="${ci}"]`).attr("style", "position: relative; border-color: #007bff ! important;");
                    $("#mkbCommentCancel").css("right", $("#mkbCommentAdd").outerWidth() + 20).show();

                    editor.setValue(modules.mkb.cards[id].comments[ci].comment);
                    editor.clearSelection();
                    editor.focus();

                    $("#mkbCommentAdd").html(i18n("edit"));
                });

                $(".deleteComment").off("click").on("click", function () {
                    let i = $(this).parent().attr("data-comment-index");
                    modules.mkb.cards[id].comments.splice(i, 1);
                    $(`.loading[data-card-id="${id}"]`).show();
                    POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
                    fail(FAIL).
                    always(() => {
                        $(`.comment[data-comment-index="${i}"]`).remove();
                        let j = 0;
                        $(".comment").each(function () {
                            $(this).attr("data-comment-index", j);
                            j++;
                        });
                        $(".commentsDay").each(function () {
                            if ($(this).children().length <= 1) {
                                $(this).remove();
                            }
                        });
                        $(`.loading[data-card-id="${id}"]`).hide();
                        editor.focus();
                    });
                });
            }

            setTimeout(() => {
                let h = '';

                h += `
                    <div style='width: 100%;'>
                        <div id='mkbCommentsCaption' class='text-bold'><div class="ml-2 mt-2 mb-3">${modules.mkb.cards[id].subject}</div></div>
                        <div id='mkbComments' class='mb-4 kanban-card-body' style='width: 100%; height: 100px; overflow-y: auto;'>
                        ${comments()}
                        </div>
                    </div>
                    <div style='width: 100%; height: 200px; position: relative;'>
                        <pre class='ace-editor' id='mkbComment'></pre>
                        <div id='mkbCommentPreview' style='display: none; border: solid thin #ced4da; border-radius: 0.25rem; overflow-y: auto; padding-left: 4px; padding-top: 4px;'></div>
                        <div id='mkbCommentPreviewToggle' class='pointer noselect bg-white pl-2 pr-2 border-no-shadow' style='font-size: 0.8rem; position: absolute; right: 10px; top: -10px;'>
                            ${i18n("preview")}
                        </div>
                        <div id='mkbCommentAdd' class='pointer noselect bg-primary text-bold pl-2 pr-2 border-no-shadow' title='Ctrl+Enter' style='font-size: 0.8rem; position: absolute; right: 10px; bottom: -10px;'>
                            ${i18n("add")}
                        </div>
                        <div id='mkbCommentCancel' class='pointer noselect bg-danger pl-2 pr-2 border-no-shadow' title='Esc' style='font-size: 0.8rem; position: absolute; right: 100px; bottom: -10px; display: none;'>
                            ${i18n("cancel")}
                        </div>
                    </div>
                    <div id="mkbResizer" style="position: absolute; left: -21px; top: calc(100vh / 2 - 150px); color: lightgray; width: 40px; text-align: center; padding-top: 100px; padding-bottom: 100px; cursor: w-resize;"><i class="fas fa-grip-vertical"></i></div>
                `;

                $("#aside-right-body").html(h);

                $(".aside-right").resizableAside({
                    resizeWidthFrom: "left",
                    handleSelector: "#mkbResizer",
                });

                assignHandlers();

                setTimeout(() => {
                    $("#mkbComments").scrollTo($("#mkbComments").get(0).scrollHeight);
                }, 5);

                editor = ace.edit("mkbComment");

                if (modules.darkmode && modules.darkmode.isDark()) {
                    editor.setTheme("ace/theme/one_dark");
                } else {
                    editor.setTheme("ace/theme/chrome");
                }

                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: false
                });

                editor.session.setMode("ace/mode/markdown");

                editor.setFontSize(14);

                editor.commands.removeCommand("removeline");
                editor.commands.removeCommand("redo");

                editor.commands.addCommand({
                    name: "removeline",
                    description: "Remove line",
                    bindKey: {
                        win: "Ctrl-Y",
                        mac: "Cmd-Y"
                    },
                    exec: function (editor) { editor.removeLines(); },
                    scrollIntoView: "cursor",
                    multiSelectAction: "forEachLine"
                });

                editor.commands.addCommand({
                    name: "redo",
                    description: "Redo",
                    bindKey: {
                        win: "Ctrl-Shift-Z",
                        mac: "Command-Shift-Z"
                    },
                    exec: function (editor) { editor.redo(); }
                });

                editor.commands.addCommand({
                    name: "add",
                    description: "Add",
                    bindKey: {
                        win: "Ctrl-Enter",
                        mac: "Command-Enter"
                    },
                    exec: () => { $("#mkbCommentAdd").click(); }
                });

                $("#mkbCommentPreviewToggle").off("click").on("click", function () {
                    if ($("#mkbCommentPreview:visible").length) {
                        $("#mkbCommentPreviewToggle").text(i18n("preview"));
                        $("#mkbComment").show();
                        $("#mkbCommentPreview").hide();
                        editor.focus();
                    } else {
                        $("#mkbCommentPreviewToggle").text(i18n("editor"));
                        $("#mkbComment").hide();
                        $("#mkbCommentPreview").css("height", "200px").html(convertLinks(rbtMdRender($.trim(editor.getValue())))).show();
                    }
                });

                $("#aside-right").modal("show");

                let z = 406 - 24 - $("#mkbCommentsCaption").outerHeight();

                $("#mkbComments").css("height", `calc(100vh - ${z}px`);

                $("#mkbCommentCancel").off("click").on("click", () => {
                    $(`.comment[data-comment-index="${ci}"]`).html(comment(ci));
                    $(`.comment`).css("border-color", "#dee2e6");
                    assignHandlers();
                    editor.setValue("");
                    editor.clearSelection();
                    editor.focus();
                    ci = -1;
                    $("#mkbCommentAdd").html(i18n("add"));
                    $("#mkbCommentCancel").hide();
                });

                $("#mkbCommentAdd").off("click").on("click", () => {
                    if (!$.trim(editor.getValue())) {
                        return;
                    }

                    if (!modules.mkb.cards[id].comments) {
                        modules.mkb.cards[id].comments = [];
                    }

                    if (ci >= 0) {
                        modules.mkb.cards[id].comments[ci] = {
                            date: Math.round((new Date()).getTime() / 1000),
                            comment: editor.getValue(),
                            modified: true,
                            author: myself.login,
                        };
                    } else {
                        modules.mkb.cards[id].comments.push({
                            date: Math.round((new Date()).getTime() / 1000),
                            comment: editor.getValue(),
                            modified: false,
                            author: myself.login,
                        });
                    }

                    $(`.loading[data-card-id="${id}"]`).show();
                    POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
                    fail(FAIL).
                    always(() => {
                        if (ci >= 0) {
                            $(`.comment[data-comment-index="${ci}"]`).html(comment(ci));
                            $(`.comment[data-comment-index="${ci}"]`).css("border-color", "#dee2e6");
                        } else {
                            $("#mkbComments").html(comments());
                            $("#mkbComments").scrollTo($("#mkbComments").get(0).scrollHeight);
                        }
                        editor.setValue("");
                        editor.clearSelection();
                        editor.focus();
                        ci = -1;
                        $("#mkbCommentAdd").html(i18n("add"));
                        $("#mkbCommentCancel").hide();
                        assignHandlers();
                        $(`.loading[data-card-id="${id}"]`).hide();
                    });
                });

                editor.focus();
            }, 25);
        });
    },

    renderCard: function (card) {
        let s = '';

        if (card.subtasks && card.subtasks.length) {
            s += `<hr class="hr-subject" data-card-id="${card._id}" style="${card.subtasksMinimized ? "display: none;" : ""}" /><div id="subtasks-${card._id}" class="subtasks pb-2" data-card-id="${card._id}" style="${card.subtasksMinimized ? "display: none;" : ""}">`;

            let p = 0;

            for (let i in card.subtasks) {
                s += `
                    <div class="custom-control custom-checkbox">
                        <input id="card-subtask-${card._id}-${i}" class="subtask-checkbox custom-control-input custom-control-input-primary custom-control-input-outline" type="checkbox"${card.subtasks[i].checked ? " checked " : " " }data-card-id=${card._id}>
                        <label for="card-subtask-${card._id}-${i}" class="pl-1 custom-control-label noselect text-no-bold">${$.trim(escapeHTML(card.subtasks[i].text))}</label>
                    </div>
                `;

                if (card.subtasks[i].checked) {
                    p++;
                }
            }

            p = Math.round((p / card.subtasks.length) * 1000) / 10;

            s += `</div><div class="${card.cardMinimized ? "" : "pointer"} subtasks-progress ${card.subtasksMinimized ? "pt-3" : "pt-1"} pb-1" data-card-id="${card._id}" data-minimized=${card.subtasksMinimized ? "true" : "false"} title="${p}%"><div class="progress"><div class="progress-bar progress-bar-danger progress-bar-striped progressbar-value" role="progressbar" style="width: ${p}%" aria-valuenow="${p}" aria-valuemin="0" aria-valuemax="100" data-card-id="${card._id}">${p}%</div></div></div>`;
        }

        let b = '';

        if (card.body) {
            b = `
                <hr class="min-max" data-card-id="${card._id}" style="${card.cardMinimized ? "display: none;" : ""}" />
                <div class="min-max kanban-card-body" data-card-id="${card._id}" style="${card.cardMinimized ? "display: none;" : ""}">${modules.mkb.mdr(card.body)}</div>
            `;
        }

        let c = '';

        if (card.date) {
            let d = Math.ceil((card.date - ((new Date()).getTime() / 1000)) / (60 * 60 * 24));
            c = `
                <span class="dropdown card-calendar" data-card-id="${card._id}" data-date="${card.date}" title="${i18n("mkb.date")}">
                    <span class="btn btn-tool ${(d >= 0) ? "text-success" : "text-danger"} dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-bottom: -8px;">
                        ${Math.abs(d)} ${i18n("mkb.days")}
                        <ul class="dropdown-menu">
                            <li id="dropdown-calendar-${card._id}"></li>
                        </ul>
                    </span>
                </span>
            `;
        } else {
            c = "&nbsp;";
        }

        let t = "";

        for (let i in card.tags) {
            t += `
                <span class="badge bg-${systemColor(card.tags[i])} kanban-badge pr-2 pl-2 mt-1 pointer" style="border: solid thin #60686f" title="${$.trim(escapeHTML(card.tags[i]))}">
                    ${$.trim(escapeHTML(card.tags[i]))}
                </span>
            `;
        }

        if (t) {
            t = `<div class="mt-2 min-max" data-card-id="${card._id}" style="font-size: 75%; ${card.cardMinimized ? "display: none;" : ""}">${t}</div>`;
        }

        let h = `
            <div id="card-${card._id}" class="kanban-card card card-${card.color} card-outline noselect">
                <div class="card-header card-handle pl-1 pr-3">
                    <h5 class="card-title">${c}</h5>
                    <div class="card-tools">
                        <span class="btn btn-tool text-black loading" title="${i18n("mkb.loading")}" style="cursor: default ! important; display: none;" data-card-id="${card._id}"><i class="fas fa-fw fa-spinner rotate"></i></span>
                        <span class="btn btn-tool" title="${i18n("mkb.attachments")}"><i class="fas fa-fw fa-paperclip"></i></span>
                        <span class="btn btn-tool cardComments" title="${i18n("mkb.comments")}" data-card-id="${card._id}"><i class="far fa-fw fa-comments"></i></span>
                        <span class="btn btn-tool cardEdit" title="${i18n("mkb.edit")}" data-card-id="${card._id}"><i class="fas fa-fw fa-edit"></i></span>
                        <span class="btn btn-tool btn-min-max" title="${card.cardMinimized ? i18n("mkb.restore") : i18n("mkb.minimize")}" data-card-id="${card._id}"><i class="fas fa-fw ${card.cardMinimized ? "fa-expand-arrows-alt" : "fa-compress-arrows-alt"}"></i></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-bold">${$.trim(escapeHTML(card.subject))}</div>
                    ${t}
                    ${s}
                    ${b}
                </div>
            </div>
        `;

        return $.trim(h);
    },

    renderColumn: function (column) {
        let c = '';

        for (let i in column.cards) {
            c += modules.mkb.renderCard(modules.mkb.cards[column.cards[i]]);
        }

        let h = `
            <div id="column-${column._id}" class="card card-row card-${column.color} kanban-col">
                <div class="card-header col-handle pl-3 pr-3">
                    <h3 class="card-title pt-1 text-bold">${$.trim(escapeHTML(column.title))}</h3>
                    <div class="card-tools" data-column-id="${column._id}">
                        <span class="btn btn-tool column-table" title="${i18n("mkb.tableView")}"><i class="fas fa-fw fa-table"></i></span>
                        <span class="btn btn-tool card-add" title="${i18n("mkb.addCard")}"><i class="fas fa-fw fa-plus-circle"></i></span>
                        <span class="btn btn-tool column-edit" title="${i18n("mkb.edit")}"><i class="fas fa-fw fa-edit"></i></span>
                    </div>
                </div>
                <div id="card-body-${column._id}" class="card-body card-content" style="min-height: 100%;">${c}</div>
            </div>
        `;

        return $.trim(h);
    },

    renderDesk: function () {
        loadingStart();

        GET("mkb", "desks", false, true).
        done(r => {
            modules.mkb.desks = [];

            if (r && r.desks) {
                modules.mkb.desks = r.desks;
            }

            let desk = lStore("mkbDesk");

            let h = '';

            modules.mkb.desks.sort((a, b) => {
                if (a.name > b.name) {
                    return 1;
                }
                if (a.name < b.name) {
                    return -1;
                }
                return 0;
            });

            for (let i in modules.mkb.desks) {
                if (modules.mkb.desks[i].name == lStore("mkbDesk")) {
                    h += '<option selected>' + escapeHTML(modules.mkb.desks[i].name) + '</option>';
                } else {
                    h += '<option>' + escapeHTML(modules.mkb.desks[i].name) + '</option>';
                }
                modules.mkb.deskNames.push(modules.mkb.desks[i].name);
            }

            if (!h) {
                h += '<option selected>' + escapeHTML(i18n("mkb.default")) + '</option>';
                modules.mkb.desks.push({
                    name: i18n("mkb.default"),
                    columns: [],
                });
                modules.mkb.deskNames.push(i18n("mkb.default"));
            }

            $("#mkbDesks").html(h);

            desk = $("#mkbDesks").val();
            lStore("mkbDesk", desk);

            POST("mkb", "cards", false, { desk }).
            done(r => {
                desk = modules.mkb.desk();

                modules.mkb.cards = {};

                for (let i in r.cards) {
                    modules.mkb.cards[r.cards[i]._id] = r.cards[i];
                }

                let h = `
                    <div class="content-wrapper kanban pt-2" style="margin-left: 0px!important; margin-top: 0px!important;">
                        <section class="content pb-2 pl-0 pr-0">
                            <div id="desk" class="h-100 kanban-desk" style="display: flex;"></div>
                        </section>
                    </div>
                `;

                $("#mainForm").html($.trim(h));

                h = '';

                if (desk.columns) {
                    for (let i in desk.columns) {
                        h += modules.mkb.renderColumn(desk.columns[i]);
                    }
                }

                $("#desk").html($.trim(h));

                modules.mkb.assignHandlers();

                loadingDone();
            }).
            fail(FAILPAGE);
        }).
        fail(FAILPAGE);
    },

    desk: function () {
        let desk = {
            name: lStore("mkbDesk"),
            columns: [],
        };

        for (let i in modules.mkb.desks) {
            if (modules.mkb.desks[i].name == lStore("mkbDesk")) {
                desk = modules.mkb.desks[i];
                break;
            }
        }

        return desk;
    },

    route: function () {
        modules.users.loadUsers(() => {
            subTop();

            $("#altForm").hide();

            document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

            let rtd = '';

            rtd += '<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="mkbDesks" class="form-control select-arrow right-top-select top-input"></select></div></div></form>';

            rtd += `<li class="nav-item nav-item-back-hover"><span class="addDesk nav-link pointer" role="button" title="${i18n("mkb.addDesk")}"><i class="fas fa-lg fa-fw fa-plus-square"></i></span></li>`;
            rtd += `<li class="nav-item nav-item-back-hover"><span class="editDesk nav-link pointer" role="button" title="${i18n("mkb.editDesk")}"><i class="fas fa-lg fa-fw fa-pen-square"></i></span></li>`;
            rtd += `<li class="nav-item nav-item-back-hover"><span class="deleteDesk nav-link pointer" role="button" title="${i18n("mkb.deleteDesk")}"><i class="fas fa-lg fa-fw fa-minus-square"></i></span></li>`;

            $("#rightTopDynamic").html(rtd);

            $("#mkbDesks").off("change").on("change", () => {
                lStore("mkbDesk", $("#mkbDesks").val());
                modules.mkb.renderDesk();
            });

            if (modules.mkb.desks.indexOf(i18n("mkb.default")) < 0) {
                modules.mkb.desks.push(i18n("mkb.default"));
            }

            if (modules.mkb.menuItem) {
                $("#" + modules.mkb.menuItem).children().first().attr("href", navigateUrl("mkb"));
            }

            if (parseInt(myself.uid) && AVAIL("mkb")) {
                $("#leftTopDynamic").html(`
                    <li class="nav-item d-none d-sm-inline-block"><span class="pointer nav-link text-primary text-bold addColumn">${i18n("mkb.addColumn")}</span></li>
                `);
            }

            modules.mkb.renderDesk();

            $(".addDesk").off("click").on("click", () => {
                mPrompt(i18n("mkb.desk"), i18n("mkb.addDesk"), "", desk => {
                    if ($.trim(desk) && modules.mkb.deskNames.indexOf($.trim(desk)) < 0) {
                        loadingStart();
                        POST("mkb", "desk", false, { desk: { name: desk, columns: [] } }).
                        done(() => {
                            lStore("mkbDesk", desk);
                            modules.mkb.renderDesk();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }
                });
            });

            $(".editDesk").off("click").on("click", () => {
                let desk = modules.mkb.desk();

                mPrompt(i18n("mkb.desk"), i18n("mkb.renameDesk"), desk.name, newName => {
                    if ($.trim(newName)) {
                        loadingStart();
                        desk.name = newName;
                        POST("mkb", "desk", false, { desk }).
                        done(() => {
                            lStore("mkbDesk", newName);
                            modules.mkb.renderDesk();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }
                });
            });

            $(".deleteDesk").off("click").on("click", () => {
                mConfirm(i18n("mkb.confirmDeleteDesk", lStore("mkbDesk")), i18n("confirm"), i18n("delete"), () => {
                    loadingStart();
                    DELETE("mkb", "desk", lStore("mkbDesk")).
                    done(modules.mkb.renderDesk).
                    fail(FAIL).
                    fail(loadingDone);
                });
            });

            $(".addColumn").off("click").on("click", () => {
                cardForm({
                    title: i18n("mkb.addColumn"),
                    footer: true,
                    borderless: true,
                    topApply: true,
                    apply: i18n("add"),
                    fields: [
                        {
                            id: "title",
                            title: i18n("mkb.title"),
                            type: "text",
                            value: "",
                            validate: a => {
                                return !!$.trim(a);
                            }
                        },
                        {
                            id: "color",
                            title: i18n("mkb.color"),
                            type: "color",
                            noEmpty: true,
                        },
                    ],
                    callback: r => {
                        let desk = modules.mkb.desk();

                        desk.columns.push({
                            _id: guid(),
                            title: r.title,
                            color: r.color,
                        });

                        loadingStart();
                        POST("mkb", "desk", false, { desk }).
                        done(() => {
                            modules.mkb.renderDesk();
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }
                });
            });
        }).
        fail(FAILPAGE);
    },
/*
    search: function (search) {
        console.log(search);
    }
*/
}).init();