({
    menuItem: false,
    md: false,
    desks: [],
    deskNames: [],
    cards: {},
    deskCards: [],
    calendars: {},
    tags: [],

    subModules: [
        "table",
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

    updateCard: function (id, busy) {
        if (busy) {
            modules.mkb.cardLoadingStart(id);
        }
        POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
        fail(FAIL).
        always(() => {
            modules.mkb.cardLoadingDone(id);
        });
    },

    reassembleDesk: function () {
        let desk = modules.mkb.desk();
        let cols = {};

        for (let i in desk.columns) {
            cols[desk.columns[i]._id] = {
                _id: desk.columns[i]._id,
                title: desk.columns[i].title,
                color: desk.columns[i].color,
            };
        }

        let newColumns = [];

        $("#desk").children().each(function () {
            let col = $(this);
            let newCards = [];
            col.children().first().next().children().each(function () {
                newCards.push($(this).attr("data-card-id"));
            });
            cols[col.attr("data-column-id")].cards = newCards;
            newColumns.push(cols[col.attr("data-column-id")]);
        });

        desk.columns = newColumns;

        document.body.style.cursor = 'wait';
        POST("mkb", "desk", false, { desk }).
        fail(FAIL).
        always(() => {
            document.body.style.cursor = 'default';
        });
    },

    cardLoadingStart: function (id) {
        $(`.cardLoading[data-card-id="${id}"]`).show();
        $(`.cardDone[data-card-id="${id}"]`).hide();
        $(document.body).addClass("cursor-wait");
    },

    cardLoadingDone: function (id,) {
        $(document.body).removeClass("cursor-wait");
        $(`.cardLoading[data-card-id="${id}"]`).hide();
        $(`.cardDone[data-card-id="${id}"]`).show();
    },

    cardEdit: function (id, done) {
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
                    value: modules.mkb.cards[id]._id,
                    readonly: true,
                    hidden: true,
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
                    mConfirm(i18n("mkb.confirmDeleteCard", r.subject), i18n("confirm"), i18n("delete"), () => {
                        loadingStart();
                        DELETE("mkb", "card", r._id).
                        done(modules.mkb.renderDesk).
                        fail(FAIL).
                        fail(loadingDone);
                    });
                } else {
                    r.tags.sort();

                    modules.mkb.cards[id].subject = r.subject;
                    modules.mkb.cards[id].color = r.color;
                    modules.mkb.cards[id].tags = r.tags;
                    modules.mkb.cards[id].body = r.body;
                    modules.mkb.cards[id].subtasks = r.subtasks;

                    loadingStart();
                    POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
                    done(done).
                    fail(FAIL).
                    fail(loadingDone);
                }
            }
        });
    },

    cardComments: function (id) {
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
                ${convertLinks(rbtMdRender(modules.mkb.cards[id].comments[i].body))}
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
                            h += `<div class="mb-4"><hr class='hr-text-white-no-padding' data-content='${d}' style='margin-block: 0px !important;' /></div>`;
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
                $(`.comment[data-comment-index="${ci}"]`).attr("style", "position: relative; border-color: #007bff !important;");
                $("#mkbCommentCancel").css("right", $("#mkbCommentAdd").outerWidth() + 20).show();

                editor.setValue(modules.mkb.cards[id].comments[ci].body);
                editor.clearSelection();
                editor.focus();

                $("#mkbCommentAdd").html(i18n("edit"));
            });

            $(".deleteComment").off("click").on("click", function () {
                let i = $(this).parent().attr("data-comment-index");
                modules.mkb.cards[id].comments.splice(i, 1);
                modules.mkb.cardLoadingStart(id);
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

                    if (modules.mkb.cards[id].comments.length) {
                        $(`.cardComments[data-card-id="${id}"]`).addClass("text-success").children().first().removeClass("far").addClass("fas");
                    } else {
                        $(`.cardComments[data-card-id="${id}"]`).removeClass("text-success").children().first().removeClass("fas").addClass("far");
                    }

                    modules.mkb.cardLoadingDone(id);
                    editor.focus();
                });
            });
        }

        setTimeout(() => {
            let h = '';

            h += `
                <div style='width: 100%;'>
                    <div id='mkbCommentsCaption' class='text-bold'><div class="ml-2 mt-2 mb-3 mr-2" title="${escapeHTML(modules.mkb.cards[id].subject)}">
                        <div class="ellipses-parent">
                            <div class="ellipses-children">
                                ${escapeHTML(modules.mkb.cards[id].subject)}
                            </div>
                        </div>
                    </div>
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
                <div id="mkbResizer" style="position: absolute; left: -101px; top: calc(100vh / 2 - 150px); color: lightgray; text-align: center; padding-left: 90px; padding-top: 100px; padding-bottom: 100px; cursor: w-resize;">
                    <i class="fas fa-grip-vertical p-1 bg-white border-no-shadow" style="color: lightgray !important;"></i>
                </div>
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

            let z = 406 - 24 - $("#mkbCommentsCaption").children().first().outerHeight(); // $("#mkbCommentsCaption").outerHeight();

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
                        body: editor.getValue(),
                        modified: true,
                        author: myself.login,
                    };
                } else {
                    modules.mkb.cards[id].comments.push({
                        date: Math.round((new Date()).getTime() / 1000),
                        body: editor.getValue(),
                        modified: false,
                        author: myself.login,
                    });
                }

                modules.mkb.cardLoadingStart(id);
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
                    $(`.cardComments[data-card-id="${id}"]`).addClass("text-success").children().first().removeClass("far").addClass("fas");
                    modules.mkb.cardLoadingDone(id);
                });
            });

            editor.focus();
        }, 25);
    },

    assignHandlers: function () {
        $(".subtasks").each(function () {
            let subtasks = $(this);
            new Sortable(document.getElementById(subtasks.attr("id")), {
                animation: 150,

                onEnd: e => {
                    let id = e.to.dataset["cardId"];

                    let i = 0;
                    $(`.subtasks[data-card-id="${id}"]`).children().each(function () {
                        let t = $.trim($(this).children().first().next().text());
                        modules.mkb.cards[id].subtasks[i].id = t;
                        modules.mkb.cards[id].subtasks[i].text = t;
                        modules.mkb.cards[id].subtasks[i].value = t;
                        modules.mkb.cards[id].subtasks[i].checked = $(this).children().first().prop("checked");
                        i++;
                    });

                    modules.mkb.updateCard(id, true);
                },
            });
        });

        $(".cardContent").each(function () {
            let col = $(this);
            new Sortable(document.getElementById(col.attr("id")), {
                handle: ".card-handle",
                animation: 150,
                group: "cols",
                scroll: true,
                forceAutoScrollFallback: true,
                scrollSpeed: 25,

                onEnd: modules.mkb.reassembleDesk,
            });
        });

        new Sortable(document.getElementById("desk"), {
            handle: ".col-handle",
            animation: 150,

            onEnd: modules.mkb.reassembleDesk,
        });

        $(".cardCalendar").off("show.bs.dropdown").on("show.bs.dropdown", function () {
            let id = $(this).attr("data-card-id");
            let d = $(this).attr("data-date");

            if (!modules.mkb.calendars[id]) {
                $(`#dropdown-calendar-${id}`).
                html(`<span id='calendar-${id}'></span><div style="text-align: center;"><input class="btn btn-primary cardCalendarApply" type="button" value="${i18n("apply")}" data-card-id="${id}" /></div>`).
                off("click dragstart pointerdown mousedown touchstart dragover dragenter").
                on("click dragstart pointerdown mousedown touchstart dragover dragenter", e => {
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

                $(".cardCalendarApply").off("click").on("click", function () {
                    let id = $(this).attr("data-card-id");

                    if (modules.mkb.calendars[id].context.selectedDates.length) {
                        modules.mkb.cards[id].date = strtotime($.trim(modules.mkb.calendars[id].context.selectedDates) + " " + modules.mkb.calendars[id].context.selectedTime);

                        let s = $(`.cardCalendar[data-card-id="${id}"]`);

                        let d = Math.ceil((modules.mkb.cards[id].date - ((new Date()).getTime() / 1000)) / (60 * 60 * 24));

                        s.attr("data-date", modules.mkb.cards[id].date);

                        if (d >= 0) {
                            s.children().first().removeClass("text-danger").addClass("text-success");
                        } else {
                            s.children().first().addClass("text-danger").removeClass("text-success");
                        }

                        s.children().first().children().first().html(Math.abs(d) + " " + i18n("mkb.days"));
                    } else {
                        modules.mkb.cards[id].date = false;

                        let s = $(`.cardCalendar[data-card-id="${id}"]`);

                        s.attr("data-date", false);

                        s.children().first().removeClass("text-danger").removeClass("text-success");

                        s.children().first().children().first().html("<i class='far fa-fw fa-calendar'></i>");
                    }

                    loadingStart();
                    setTimeout(() => {
                        $(`.cardCalendar[data-card-id="${id}"]`).dropdown("hide");

                        modules.mkb.updateCard(id);

                        loadingDone();
                    }, 100);
                });
            }
        });

        $(".subtasksProgress").off("click").on("click", function () {
            let pb = $(this);
            let id = pb.attr("data-card-id");

            if (pb.hasClass("pointer")) {
                if (pb.attr("data-minimized") == "true") {
                    $(`.subtasks[data-card-id="${id}"]`).show();
                    $(`.hr-subject[data-card-id="${id}"]`).show();
                    pb.attr("data-minimized", "false").removeClass("pt-3").addClass("pt-1");
                    modules.mkb.cards[id].subtasksMinimized = false;
                } else {
                    $(`.subtasks[data-card-id="${id}"]`).hide();
                    $(`.hr-subject[data-card-id="${id}"]`).hide();
                    pb.attr("data-minimized", "true").addClass("pt-3").removeClass("pt-1");
                    modules.mkb.cards[id].subtasksMinimized = true;
                }

                modules.mkb.updateCard(id, true);
            }
        });

        $(".subtaskCheckbox").off("change").on("change", function () {
            let id = $(this).attr("data-card-id");
            let p = 0, i = 0;

            $(this).parent().parent().children().each(function () {
                let c = $(this).children().first().prop("checked");
                let t = $.trim($(this).children().first().next().text());

                if (c) {
                    p++;
                }

                modules.mkb.cards[id].subtasks[i].id = t;
                modules.mkb.cards[id].subtasks[i].text = t;
                modules.mkb.cards[id].subtasks[i].value = t;
                modules.mkb.cards[id].subtasks[i].checked = c;
                i++;
            });

            p = Math.round((p / i) * 1000) / 10;

            $(`.progressbar-value[data-card-id="${id}"]`).css("width", p + "%").attr("aria-valuenow", p).attr("title", p + "%").text(p + "%");

            modules.mkb.updateCard(id, true);
        });

        $(".cardMinMax").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            if ($(`.cardMinMax[data-card-id="${id}"]`).children().first().hasClass("fa-compress-arrows-alt")) {
                $(`.cardMinMax[data-card-id="${id}"]`).children().first().removeClass("fa-compress-arrows-alt").addClass("fa-expand-arrows-alt").attr("title", i18n("mkb.restore"));
                $(`.subtasksProgress[data-card-id="${id}"]`).removeClass("pt-1").addClass("pt-3").removeClass("pointer");
                $(`.hr-subject[data-card-id="${id}"]`).hide();
                $(`.min-max[data-card-id="${id}"]`).hide();
                $(`.subtasks[data-card-id="${id}"]`).hide();
                modules.mkb.cards[id].cardMinimized = true;
            } else {
                $(`.cardMinMax[data-card-id="${id}"]`).children().first().addClass("fa-compress-arrows-alt").removeClass("fa-expand-arrows-alt").attr("title", i18n("mkb.minimize"));
                let pb = $(`.subtasksProgress[data-card-id="${id}"]`);
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
                modules.mkb.cards[id].cardMinimized = false;
            }

            modules.mkb.updateCard(id, true);
        });

        $(".columnEdit").off("click").on("click", function () {
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
                        id: "_id",
                        title: i18n("mkb.id"),
                        type: "text",
                        value: column._id,
                        readonly: true,
                        hidden: true,
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

        $(".cardAdd").off("click").on("click", function () {
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

            modules.mkb.cardEdit(id, () => {
                $(`#card-${id}`).replaceWith($(modules.mkb.renderCard(modules.mkb.cards[id])));
                modules.mkb.assignHandlers();
                loadingDone();
            });
        });

        $(".cardComments").off("click").on("click", function () {
            modules.mkb.cardComments($(this).attr("data-card-id"));
        });

        $(".cardTag").off("click").on("click", function () {
            let tag = $(this).attr("title");

            if (modules.mkb.tags.indexOf(tag) >= 0) {
                modules.mkb.tags.splice(modules.mkb.tags.indexOf(tag), 1);
            } else {
                modules.mkb.tags.push(tag);
            }

            $(".cardTag").css("border-width", "thin");

            if (modules.mkb.tags.length) {
                for (let i in modules.mkb.tags) {
                    $(`.cardTag[title="${modules.mkb.tags[i]}"]`).css("border-width", "medium");
                }

                $(".kanban-card").attr("data-card-filtered", "false");

                for (let i in modules.mkb.cards) {
                    let v = false;

                    for (let j in modules.mkb.tags) {
                        if (modules.mkb.cards[i].tags.indexOf(modules.mkb.tags[j]) >= 0) {
                            v = true;
                            break;
                        }
                    }

                    if (v) {
                        $("#card-" + i).attr("data-card-filtered", "true");
                    }
                }

                $('.kanban-card[data-card-filtered="false"]').hide();
                $('.kanban-card[data-card-filtered="true"]').show();
            } else {
                $(".kanban-card").show();
            }
        });

        $(".cardArchive").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            loadingStart();

            let desk = modules.mkb.desk();

            for (let i in desk.columns) {
                if ((j = desk.columns[i].cards.indexOf(id)) >= 0) {
                    desk.columns[i].cards.splice(j, 1);
                    break;
                }
            }

            modules.mkb.cards[id].desk = false;

            POST("mkb", "card", false, { card: modules.mkb.cards[id] }).
            done(() => {
                POST("mkb", "desk", false, { desk }).
                done(modules.mkb.renderDesk).
                fail(FAIL).
                fail(loadingDone);
            }).
            fail(FAIL).
            fail(loadingDone);
        });

        $(".cardDone").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            if ($(`.cardDone[data-card-id="${id}"]`).hasClass("text-success")) {
                $(`.cardDone[data-card-id="${id}"]`).removeClass("text-success");
                modules.mkb.cards[id].done = false;
            } else {
                $(`.cardDone[data-card-id="${id}"]`).addClass("text-success");
                modules.mkb.cards[id].done = true;
            }

            modules.mkb.updateCard(id, true);
        });

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

        $(".cardsArchive").off("click").on("click", () => {
            navigateUrl("mkb.table", { archive: true }, { run: true });
        });

        $(".cardsAll").off("click").on("click", () => {
            navigateUrl("mkb.table", { all: true }, { run: true });
        });
    },

    renderCard: function (card) {
        let s = '';

        if (card.subtasks && card.subtasks.length) {
            s += `
                <hr class="hr-subject" data-card-id="${card._id}" style="${(card.subtasksMinimized || card.cardMinimized) ? "display: none;" : ""}" />
                <div id="subtasks-${card._id}" class="subtasks pb-2" data-card-id="${card._id}" style="${(card.subtasksMinimized || card.cardMinimized) ? "display: none;" : ""}">
            `;

            let p = 0;

            for (let i in card.subtasks) {
                s += `
                    <div class="custom-control custom-checkbox noselect">
                        <input id="card-subtask-${card._id}-${i}" class="subtaskCheckbox custom-control-input custom-control-input-primary custom-control-input-outline" type="checkbox"${card.subtasks[i].checked ? " checked " : " " }data-card-id=${card._id}>
                        <label for="card-subtask-${card._id}-${i}" class="pl-1 custom-control-label text-no-bold">${$.trim(escapeHTML(card.subtasks[i].text))}</label>
                    </div>
                `;

                if (card.subtasks[i].checked) {
                    p++;
                }
            }

            p = Math.round((p / card.subtasks.length) * 1000) / 10;

            s += `
                </div>
                <div class="${card.cardMinimized ? "" : "pointer"} subtasksProgress ${(card.subtasksMinimized || card.cardMinimized) ? "pt-3" : "pt-1"} pb-1" data-card-id="${card._id}" data-minimized=${card.subtasksMinimized ? "true" : "false"} title="${p}%">
                    <div class="progress" style="border-radius: 4px;">
                        <div class="progress-bar progress-bar-striped progressbar-value" role="progressbar" style="width: ${p}%" aria-valuenow="${p}" aria-valuemin="0" aria-valuemax="100" data-card-id="${card._id}">
                            ${p}%
                        </div>
                    </div>
                </div>
            `;
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
                <span class="dropdown cardCalendar" data-card-id="${card._id}" data-date="${card.date}" title="${i18n("mkb.date")}">
                    <span class="btn btn-tool ${(d >= 0) ? "text-success" : "text-danger"} dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-top: -12px;">
                        <span>${Math.abs(d)} ${i18n("mkb.days")}</span>
                        <ul class="dropdown-menu">
                            <li id="dropdown-calendar-${card._id}"></li>
                        </ul>
                    </span>
                </span>
            `;
        } else {
            c = `
                <span class="dropdown cardCalendar" data-card-id="${card._id}" data-date="false" title="${i18n("mkb.date")}">
                    <span class="btn btn-tool dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-top: -12px;">
                        <span><i class="far fa-fw fa-calendar"></i></span>
                        <ul class="dropdown-menu">
                            <li id="dropdown-calendar-${card._id}"></li>
                        </ul>
                    </span>
                </span>
            `;
        }

        let t = "";
        let v = false;

        for (let i in card.tags) {
            t += `
                <span class="badge cardTag bg-${systemColor(card.tags[i])} kanban-badge pr-2 pl-2 mt-1 pointer" style="border: solid ${modules.mkb.tags.indexOf(card.tags[i]) >= 0 ? "medium" : "thin"} #60686f" title="${$.trim(escapeHTML(card.tags[i]))}">
                    ${$.trim(escapeHTML(card.tags[i]))}
                </span>
            `;

            for (let j in modules.mkb.tags) {
                if (card.tags[i] == modules.mkb.tags[j]) {
                    v = true;
                    break;
                }
            }
        }

        if (t) {
            t = `<div class="mt-2 min-max" data-card-id="${card._id}" style="font-size: 75%; display: flex; align-items: center; ${card.cardMinimized ? "display: none;" : ""}">${t}</div>`;
        }

        let h = `
            <div id="card-${card._id}" data-card-id="${card._id}" class="kanban-card card card-${card.color} card-outline" style="${(modules.mkb.tags.length && !v) ? "display: none;" : ""}">
                <div class="card-header card-handle pl-1 pr-3">
                    <h5 class="card-title">
                        <span class="btn btn-tool cardDone pr-0 ${card.done ? "text-success" : ""}" title="${i18n("mkb.done")}" data-card-id="${card._id}" style="padding-top: 8px;"><i class="fas fa-fw fa-check-circle"></i></span>
                        <span class="btn btn-tool text-black cardLoading pr-0" title="${i18n("mkb.loading")}" style="display: none; padding-top: 8px;" data-card-id="${card._id}"><i class="fas fa-fw fa-spinner rotate"></i></span>
                        ${c}
                    </h5>
                    <div class="card-tools">
                        <!-- TODO -->
                        <span class="btn btn-tool cardAttachments pr-0 pl-0" title="${i18n("mkb.attachments")}" style="display: none;"><i class="fas fa-fw fa-paperclip"></i></span>
                        <span class="btn btn-tool cardComments pr-0 ${(card.comments && card.comments.length) ? " text-success" : ""}" title="${i18n("mkb.comments")}" data-card-id="${card._id}"><i class="${(card.comments && card.comments.length) ? "fas" : "far"} fa-fw fa-comments"></i></span>
                        <span class="btn btn-tool cardArchive pr-0" title="${i18n("mkb.archive")}" data-card-id="${card._id}"><i class="fas fa-fw fa-archive"></i></span>
                        <span class="btn btn-tool cardEdit pr-0" title="${i18n("mkb.edit")}" data-card-id="${card._id}"><i class="fas fa-fw fa-edit"></i></span>
                        <span class="btn btn-tool cardMinMax" title="${card.cardMinimized ? i18n("mkb.restore") : i18n("mkb.minimize")}" data-card-id="${card._id}"><i class="fas fa-fw ${card.cardMinimized ? "fa-expand-arrows-alt" : "fa-compress-arrows-alt"}"></i></span>
                    </div>
                </div>
                <div class="card-body" data-card-id="${card._id}">
                    <div>${$.trim(escapeHTML(card.subject))}</div>
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

        modules.mkb.deskCards = [];

        for (let i in column.cards) {
            if (modules.mkb.cards[column.cards[i]]) {
                c += modules.mkb.renderCard(modules.mkb.cards[column.cards[i]]);
                modules.mkb.deskCards.push(column.cards[i]);
            }
        }

        let h = `
            <div id="column-${column._id}" data-column-id="${column._id}" class="card card-row card-${column.color} kanban-col">
                <div class="card-header col-handle pl-3 pr-3">
                    <h3 class="card-title pt-1 text-bold">${$.trim(escapeHTML(column.title))}</h3>
                    <div class="card-tools" data-column-id="${column._id}">
                        <span class="btn btn-tool column-table pr-0 pl-0" title="${i18n("mkb.tableView")}"><i class="fas fa-fw fa-table"></i></span>
                        <span class="btn btn-tool cardAdd pr-0" title="${i18n("mkb.addCard")}"><i class="fas fa-fw fa-plus-circle"></i></span>
                        <span class="btn btn-tool columnEdit" title="${i18n("mkb.edit")}"><i class="fas fa-fw fa-edit"></i></span>
                    </div>
                </div>
                <div id="card-body-${column._id}" class="card-body cardContent" style="min-height: 100%;">${c}</div>
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

            POST("mkb", "cards", false, { query: { desk } }).
            done(r => {
                desk = modules.mkb.desk();

                modules.mkb.cards = {};

                for (let i in r.cards) {
                    modules.mkb.cards[r.cards[i]._id] = r.cards[i];
                }

                let h = `
                    <div class="content-wrapper kanban pt-2" style="margin-left: 0px!important; margin-top: 0px!important;">
                        <section class="content pb-2 pl-0 pr-0">
                            <div id="desk" class="h-100 kanban-desk" style="display: flex;">
                `;

                if (desk.columns) {
                    for (let i in desk.columns) {
                        h += modules.mkb.renderColumn(desk.columns[i]);
                    }
                }

                h += `
                            </div>
                        </section>
                    </div>
                `;

                $("#mainForm").html($.trim(h));

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
                modules.mkb.tags = [];
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
                    <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 addColumn" title="${i18n("mkb.addColumn")}"><i class="fas fa-fw fa-plus-circle text-primary"></i></li>
                    <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 cardsArchive" title="${i18n("mkb.cardsArchive")}"><i class="fas fa-fw fa-archive text-secondary"></i></li>
                    <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 cardsAll" title="${i18n("mkb.cardsAll")}"><i class="far fa-fw fa-list-alt text-secondary"></i></li>
                `);
            }

            modules.mkb.renderDesk();
        }).
        fail(FAILPAGE);
    },

    search: function (search) {
        navigateUrl("mkb.table", { search }, { run: true });
    }
}).init();