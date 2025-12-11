({
    menuItem: false,

    initialHeight: 0,

    isDragging: false,
    dragTarget: undefined,
    gridSize: 10,

    lastOffsetX: 0,
    lastOffsetY: 0,
    lastLeft: 0,
    lastTop: 0,

    notes: {},
    categories: [],

    md: false,

    colors: [
        {
            text: i18n("notes.white"),
            value: "white",
            icon: "p-1 fas fa-palette sticky-bg-color-white",
        },
        {
            text: i18n("notes.coral"),
            value: "coral",
            icon: "p-1 fas fa-palette sticky-bg-color-coral",
        },
        {
            text: i18n("notes.peach"),
            value: "peach",
            icon: "p-1 fas fa-palette sticky-bg-color-peach",
        },
        {
            text: i18n("notes.sand"),
            value: "sand",
            icon: "p-1 fas fa-palette sticky-bg-color-sand",
        },
        {
            text: i18n("notes.mint"),
            value: "mint",
            icon: "p-1 fas fa-palette sticky-bg-color-mint",
        },
        {
            text: i18n("notes.grayGreen"),
            value: "grayGreen",
            icon: "p-1 fas fa-palette sticky-bg-color-grayGreen",
        },
        {
            text: i18n("notes.grayBlue"),
            value: "grayBlue",
            icon: "p-1 fas fa-palette sticky-bg-color-grayBlue",
        },
        {
            text: i18n("notes.gray"),
            value: "gray",
            icon: "p-1 fas fa-palette sticky-bg-color-gray",
        },
        {
            text: i18n("notes.darkBlue"),
            value: "darkBlue",
            icon: "p-1 fas fa-palette sticky-bg-color-darkBlue",
        },
        {
            text: i18n("notes.pink"),
            value: "pink",
            icon: "p-1 fas fa-palette sticky-bg-color-pink",
        },
        {
            text: i18n("notes.terracotta"),
            value: "terracotta",
            icon: "p-1 fas fa-palette sticky-bg-color-terracotta",
        },
        {
            text: i18n("notes.lightGray"),
            value: "lightGray",
            icon: "p-1 fas fa-palette sticky-bg-color-lightGray",
        },
    ],

    init: function () {
        if (parseInt(myself.uid) && AVAIL("notes")) {
            this.menuItem = leftSide("fas fa-fw fa-thumbtack", i18n("notes.notes"), "?#notes", "productivity");
        }

        $(window).on("mousedown", e => {
            let target = $(e.target);

            if (e.button !== 0) return;

            if (target.hasClass('drag')) {
                let z = 1;

                $(".sticky").each(function () {
                    z = Math.max(z, parseInt($(this).css("z-index")));
                });

                target.css({
                    "z-index": z + 1,
                    "cursor": "grab",
                });

                modules.notes.dragTarget = target;

                modules.notes.lastOffsetX = e.offsetX;
                modules.notes.lastOffsetY = e.offsetY;

                modules.notes.isDragging = 1;

                return;
            }

            if (target.attr("id") == "stickiesContainer") {
                target.css("cursor", "grab");

                modules.notes.dragTarget = target;

                modules.notes.lastLeft = target.parent().scrollLeft();
                modules.notes.lastTop = $("html").scrollTop();
                modules.notes.lastOffsetX = e.clientX;
                modules.notes.lastOffsetY = e.clientY;

                modules.notes.isDragging = 2;

                return;
            }
        });

        $(window).on("mousemove", e => {
            if (!modules.notes.isDragging) return;

            let cont = $("#stickiesContainer");

            if (modules.notes.isDragging == 1) {
                let off = cont.offset();

                let newX = Math.max(-off.left + e.clientX - modules.notes.lastOffsetX, 0);
                let newY = Math.max($("html").scrollTop() - off.top + e.clientY - modules.notes.lastOffsetY, 0);

                newX = Math.round(newX / modules.notes.gridSize) * modules.notes.gridSize;
                newY = Math.round(newY / modules.notes.gridSize) * modules.notes.gridSize;

                modules.notes.dragTarget.css({
                    left: newX + 'px',
                    top: newY + 'px',
                });
            }

            if (modules.notes.isDragging == 2) {
                let dx = e.clientX - modules.notes.lastOffsetX;
                let dy = e.clientY - modules.notes.lastOffsetY;

                cont.parent().scrollLeft(modules.notes.lastLeft - dx);
                $("html").scrollTop(modules.notes.lastTop - dy);
            }
        });

        $(window).on("mouseup", e => {
            if (!modules.notes.isDragging) return;

            modules.notes.adjustStickiesContainer();

            modules.notes.dragTarget.css({
                "cursor": "",
            });

            if (modules.notes.dragTarget.hasClass('drag')) {
                let id = modules.notes.dragTarget.attr("id");

                modules.notes.notes[id].x = parseFloat(modules.notes.dragTarget.css("left"));
                modules.notes.notes[id].y = parseFloat(modules.notes.dragTarget.css("top"));
                modules.notes.notes[id].z = parseInt(modules.notes.dragTarget.css("z-index"));

                PUT("notes", "xyz", modules.notes.notes[id].id, {
                    x: parseFloat(modules.notes.dragTarget.css("left")),
                    y: parseFloat(modules.notes.dragTarget.css("top")),
                    z: parseInt(modules.notes.dragTarget.css("z-index")),
                }).
                fail(FAIL);
            }

            return modules.notes.isDragging = false;
        });

        moduleLoaded("notes", this);
    },

    allLoaded: function () {
        modules.notes.md = new remarkable.Remarkable({
            html: true,
            quotes: '“”‘’',

            highlight: function (str, language) {
                if (language && hljs.getLanguage(language)) {
                    try {
                        let h = hljs.highlight(str, { language }).value;
                        return h;
                    } catch (err) {
                        console.log(err);
                    }
                }

                try {
                    return hljs.highlightAuto(str).value;
                } catch (err) {
                    console.log(err);
                }

                return ''; // use external default escaping
            }
        });

        modules.notes.md.core.ruler.enable([
            'abbr'
        ]);

        modules.notes.md.block.ruler.enable([
            'footnote',
            'deflist'
        ]);

        modules.notes.md.inline.ruler.enable([
            'footnote_inline',
            'ins',
            'mark',
            'sub',
            'sup'
        ]);
    },

    renderNote: function (id, subject, body, type, color, icon, font, remind, z, fyeo) {
        let newSticky = `<div id='${id}' class='drag sticky sticky-bg-color-${color}' style='z-index: ${z};'>`;

        // TODO: box-shadow: 2px 2px 7px {shadow-color};

        subject = $.trim(subject);
        if (subject) {
            newSticky += `<h5 class="caption" style='opacity: 100%;'>`;
            if ($.trim(icon)) {
                newSticky += `<i class="fa-fw ${icon} mr-2"></i>`;
            }
            newSticky += convertLinks(nl2br(escapeHTML(subject)));
            newSticky += "</h5>";
            newSticky += "<hr style='opacity: 50%;' />";
        }

        newSticky += `<div data-id=${id} class='body${parseInt(fyeo) ? ' fyeo' : ''}'${$.trim(font) ? " style='font-family: ${font}'" : ''}>`;

        switch (type) {
            case "checks":
                let b = body.split("\n");
                for (let i in b) {
                    newSticky += `<span class='mr-2'><input type='checkbox' class='noteCheckbox' ${(b[i][0] == "+") ? "checked" : ""} data-line='${i}'/></span><span>${convertLinks(nl2br(escapeHTML(b[i].substring(1))))}</span><br />`;
                }
                break;

            case "markdown":
                newSticky += convertLinks(DOMPurify.sanitize(modules.notes.md.render(body)));
                break;

            default:
                newSticky += convertLinks(nl2br(escapeHTML(body)));
                break;
        }

        newSticky += '</div><i class="fas fa-fw fa-edit text-primary editSticky"></i>';

        if (parseInt(fyeo)) {
            newSticky += '<i class="fas fa-fw fa-eye text-info showFyeo"></i>';
        }

        if (remind) {
            newSticky += '<i class="far fa-fw fa-clock text-small reminder"></i>';
        }

        newSticky += '</div>';

        return newSticky;
    },

    createNote: function () {
        let categories = [];
        for (let i in modules.notes.categories) {
            categories.push(
                {
                    text: modules.notes.categories[i],
                    value: modules.notes.categories[i],
                }
            );
        }

        cardForm({
            title: i18n("notes.createNote"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "xl",
            fields: [
                {
                    id: "subject",
                    title: i18n("notes.subject"),
                    type: "text",
                },
                {
                    id: "body",
                    title: i18n("notes.body"),
                    type: "area",
                    validate: a => {
                        return $.trim(a) != '';
                    }
                },
                {
                    id: "type",
                    title: i18n("notes.type"),
                    type: "select",
                    options: [
                        {
                            id: "text",
                            text: i18n("notes.typeText")
                        },
                        {
                            id: "markdown",
                            text: i18n("notes.typeMarkdown")
                        },
                        {
                            id: "checks",
                            text: i18n("notes.typeChecks")
                        },
                    ]
                },
                {
                    id: "fyeo",
                    title: i18n("notes.fyeo"),
                    type: "noyes"
                },
                {
                    id: "category",
                    title: i18n("notes.category"),
                    type: "select2",
                    multiple: false,
                    tags: true,
                    createTags: true,
                    value: $("#notesCategories").val(),
                    options: categories,
                },
                {
                    id: "remind",
                    title: i18n("notes.remind"),
                    type: "datetime-local",
                    sec: true,
                },
                {
                    id: "icon",
                    title: i18n("notes.icon"),
                    type: "icon",
                    value: "",
                },
                {
                    id: "font",
                    title: i18n("notes.font"),
                    type: "font",
                },
                {
                    id: "color",
                    title: i18n("notes.color"),
                    type: "select2",
                    options: modules.notes.colors,
                    value: "peach",
                },
            ],
            callback: r => {
                if (modules.notes.categories.indexOf(r.category) < 0) {
                    modules.notes.categories.push(r.category);
                    modules.notes.categories.sort();
                }

                if (r.category != lStore("notesCategory")) {
                    lStore("notesCategory", r.category);
                    modules.notes.renderNotes();
                }

                let stickyArea = $('#stickiesContainer');

                let id = md5(guid());

                let z = 1;

                $(".sticky").each(function () {
                    z = Math.max(z, parseInt($(this).css("z-index")));
                });

                if (r.type == "checks") {
                    r.body = r.body.split("\n");
                    for (let i in r.body) {
                        if ($.trim(r.body[i])) {
                            r.body[i] = "-" + $.trim(r.body[i]);
                        }
                    }
                    r.body = r.body.join("\n");
                }

                let newSticky = modules.notes.renderNote(
                    id,
                    r.subject,
                    r.body,
                    r.type,
                    r.color,
                    r.icon,
                    r.font,
                    parseInt(r.remind) > (new Date()).getTime() / 1000,
                    z + 1,
                    r.fyeo,
                );

                stickyArea.append(newSticky);

                let sticky = $("#" + id);

                let x = window.innerWidth / 2 - sticky.outerWidth(true) / 2 + (-100 + Math.round(Math.random() * 50));
                let y = window.innerHeight / 2 - sticky.outerHeight(true) / 2 + (-100 + Math.round(Math.random() * 50));
                let w = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("width"))
                let h = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("height"))

                sticky.css({
                    left: x + 'px',
                    top: y + 'px',
                    width: ((Math.floor(w / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                    height: ((Math.floor(h / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                });

                $(".editSticky").off("click").on("click", modules.notes.modifySticky);
                $(".showFyeo").off("click").on("click", modules.notes.showFyeo);
                $(".noteCheckbox").off("click").on("click", modules.notes.stickyCheckbox);

                modules.notes.adjustStickiesContainer();

                loadingStart();

                POST("notes", "note", false, {
                    subject: r.subject,
                    body: r.body,
                    type: r.type,
                    category: r.category,
                    remind: r.remind,
                    icon: r.icon,
                    font: r.font,
                    color: r.color,
                    x: parseFloat(x),
                    y: parseFloat(y),
                    z: parseInt(z),
                    fyeo: parseInt(r.fyeo),
                }).
                done(r => {
                    if (r && r.note) {
                        let id = "note-" + $.trim(r.note.id);
                        sticky.attr("id", id);
                        modules.notes.notes[id] = r.note;
                    }
                }).
                fail(FAIL).
                always(loadingDone);
            },
        });
    },

    adjustStickiesContainer: function () {
        let mw = 0, mh = 0;

        $(".sticky").each(function () {
            let s = $(this);
            mw = Math.max(mw, s.position().left + s.outerWidth(true));
            mh = Math.max(mh, s.position().top + s.outerHeight(true));
        });

        $("#stickiesContainer").css({
            width: Math.max(mw + 4, $("#stickiesTable").width()) + "px",
            height: Math.max(mh + 4, $(window).height() - mainFormTop - 8) + "px",
        });
    },

    modifySticky: function (e) {
        let id = $(e.target).parent().attr("id");

        let categories = [];
        for (let i in modules.notes.categories) {
            categories.push(
                {
                    text: modules.notes.categories[i],
                    value: modules.notes.categories[i],
                }
            );
        }

        let checks = [];
        if (modules.notes.notes[id].type == "checks") {
            let b = modules.notes.notes[id].body.split("\n");
            for (let i in b) {
                checks.push({
                    text: b[i].substring(1),
                    checked: b[i][0] == "+",
                });
            }
        }

        cardForm({
            title: i18n("notes.modifyNote"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("edit"),
            delete: i18n("delete"),
            size: "xl",
            fields: [
                {
                    id: "subject",
                    title: i18n("notes.subject"),
                    type: "text",
                    value: modules.notes.notes[id].subject,
                },
                {
                    id: "body",
                    title: (modules.notes.notes[id].type == "checks") ? i18n("notes.list") : i18n("notes.body"),
                    type: (modules.notes.notes[id].type == "checks") ? "sortable" : ((modules.notes.notes[id].type == "text") ? "area" : "code"),
                    language: "markdown",
                    checkable: true,
                    editable: true,
                    appendable: "input",
                    validate: a => {
                        return (modules.notes.notes[id].type == "checks") ? a.length > 0 : $.trim(a) != '';
                    },
                    value: (modules.notes.notes[id].type == "checks") ? undefined : modules.notes.notes[id].body,
                    options: (modules.notes.notes[id].type == "checks") ? checks : undefined,
                },
                (modules.notes.notes[id].type == "checks") ?
                    {
                        id: "type",
                        title: i18n("notes.type"),
                        type: "select",
                        options: [
                            {
                                id: "checks",
                                text: i18n("notes.typeChecks")
                            },
                        ],
                        value: modules.notes.notes[id].type,
                    } : {
                        id: "type",
                        title: i18n("notes.type"),
                        type: "select",
                        options: [
                            {
                                id: "text",
                                text: i18n("notes.typeText")
                            },
                            {
                                id: "markdown",
                                text: i18n("notes.typeMarkdown")
                            },
                        ],
                        value: modules.notes.notes[id].type,
                    }
                ,
                {
                    id: "fyeo",
                    title: i18n("notes.fyeo"),
                    type: "noyes",
                    value: modules.notes.notes[id].fyeo,
                },
                {
                    id: "category",
                    title: i18n("notes.category"),
                    type: "select2",
                    multiple: false,
                    tags: true,
                    createTags: true,
                    value: modules.notes.notes[id].category,
                    options: categories,
                },
                {
                    id: "remind",
                    title: i18n("notes.remind"),
                    type: "datetime-local",
                    value: modules.notes.notes[id].remind,
                    sec: true,
                },
                {
                    id: "icon",
                    title: i18n("notes.icon"),
                    type: "icon",
                    value: modules.notes.notes[id].icon,
                },
                {
                    id: "font",
                    title: i18n("notes.font"),
                    type: "font",
                    value: modules.notes.notes[id].font,
                },
                {
                    id: "color",
                    title: i18n("notes.color"),
                    type: "select2",
                    options: modules.notes.colors,
                    value: modules.notes.notes[id].color,
                },
            ],
            callback: r => {

                if (modules.notes.notes[id].type == "checks") {
                    let b = '';

                    for (let i in r.body) {
                        b += (r.body[i].checked ? "+" : "-") + r.body[i].text + "\n";
                    }

                    r.body = $.trim(b);
                }

                if (r.delete) {
                    mConfirm(i18n("notes.deleteNote"), i18n("confirm"), i18n("delete"), () => {
                        loadingStart();
                        DELETE("notes", "note", modules.notes.notes[id].id).
                        done(() => {
                            $("#" + id).remove();
                            delete modules.notes.notes[id];
                        }).
                        fail(FAIL).
                        always(loadingDone);
                    });
                } else {
                    $("#" + id).remove();

                    if (modules.notes.categories.indexOf(r.category) < 0) {
                        modules.notes.categories.push(r.category);
                        modules.notes.categories.sort();
                    }

                    if (r.category != lStore("notesCategory")) {
                        lStore("notesCategory", r.category);
                        modules.notes.renderNotes();
                    }

                    let x = modules.notes.notes[id].x;
                    let y = modules.notes.notes[id].y;
                    let z = modules.notes.notes[id].z;

                    modules.notes.notes[id].subject = r.subject;
                    modules.notes.notes[id].body = r.body;
                    modules.notes.notes[id].type = r.type;
                    modules.notes.notes[id].category = r.category;
                    modules.notes.notes[id].remind = r.remind;
                    modules.notes.notes[id].icon = r.icon;
                    modules.notes.notes[id].font = r.font;
                    modules.notes.notes[id].color = r.color;
                    modules.notes.notes[id].fyeo = r.fyeo;

                    modules.notes.notes[id].x = parseFloat(x);
                    modules.notes.notes[id].y = parseFloat(y);
                    modules.notes.notes[id].z = parseInt(z);

                    let stickyArea = $('#stickiesContainer');

                    let newSticky = modules.notes.renderNote(
                        id,
                        r.subject,
                        r.body,
                        r.type,
                        r.color,
                        r.icon,
                        r.font,
                        parseInt(r.remind) > (new Date()).getTime() / 1000,
                        z,
                        r.fyeo
                    );

                    stickyArea.append(newSticky);

                    let sticky = $("#" + id);

                    let w = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("width"))
                    let h = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("height"))

                    sticky.css({
                        left: x + 'px',
                        top: y + 'px',
                        width: ((Math.floor(w / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                        height: ((Math.floor(h / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                    });

                    $(".editSticky").off("click").on("click", modules.notes.modifySticky);
                    $(".showFyeo").off("click").on("click", modules.notes.showFyeo);
                    $(".noteCheckbox").off("click").on("click", modules.notes.stickyCheckbox);

                    loadingStart();

                    PUT("notes", "note", modules.notes.notes[id].id, {
                        subject: r.subject,
                        body: r.body,
                        type: r.type,
                        category: r.category,
                        remind: r.remind,
                        icon: r.icon,
                        font: r.font,
                        color: r.color,
                        x: parseFloat(x),
                        y: parseFloat(y),
                        z: parseInt(z),
                        fyeo: parseInt(r.fyeo),
                    }).
                    fail(FAIL).
                    always(loadingDone);
                }

                modules.notes.adjustStickiesContainer();
            },
        });
    },

    showFyeo: function (e) {
        let fyeo = $(this);
        let id = fyeo.parent().attr("id");
        let body = $(`.sticky .body[data-id="${id}"]`);

        body.removeClass('fyeo');
        fyeo.hide();

        setTimeout(() => {
            body.addClass('fyeo');
            fyeo.show();
        }, 10000);
    },

    stickyCheckbox: function (e) {
        let id = $(e.target).parent().parent().parent().attr("id");
        let b = modules.notes.notes[id].body;
        let l = parseInt($(e.target).attr("data-line"));
        b = b.split("\n");
        b[l] = ($(e.target).prop("checked") ? "+" : "-") + b[l].substring(1);
        modules.notes.notes[id].body = b.join("\n");

        PUT("notes", "check", id.split("-")[1], {
            line: l,
            checked: $(e.target).prop("checked") ? "1" : "0"
        }).
        fail(FAIL);
    },

    renderNotes: function () {
        let category = lStore("notesCategory");

        let h = '';

        for (let i in modules.notes.categories) {
            h += '<option>' + escapeHTML(modules.notes.categories[i]) + '</option>';
        }

        if (!h) {
            h += '<option>' + escapeHTML(i18n("notes.default")) + '</option>';
        }

        $("#notesCategories").html(h);

        if (modules.notes.categories.indexOf(category) >= 0) {
            $("#notesCategories").val(category);
        }

        category = $("#notesCategories").val();
        lStore("notesCategory", category);

        let stickyArea = $('#stickiesContainer');

        stickyArea.html("");

        for (let id in modules.notes.notes) {
            if (modules.notes.notes[id].category == category) {
                let newSticky = modules.notes.renderNote(
                    id,
                    modules.notes.notes[id].subject,
                    modules.notes.notes[id].body,
                    modules.notes.notes[id].type,
                    modules.notes.notes[id].color,
                    modules.notes.notes[id].icon,
                    modules.notes.notes[id].font,
                    parseInt(modules.notes.notes[id].remind) > (new Date()).getTime() / 1000 && !modules.notes.notes[id].reminded,
                    modules.notes.notes[id].z,
                    modules.notes.notes[id].fyeo,
                );

                stickyArea.append(newSticky);

                let sticky = $("#" + id);

                let w = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("width"))
                let h = parseInt(window.getComputedStyle(document.getElementById(id)).getPropertyValue("height"))

                sticky.css({
                    left: modules.notes.notes[id].x + 'px',
                    top: modules.notes.notes[id].y + 'px',
                    width: ((Math.floor(w / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                    height: ((Math.floor(h / modules.notes.gridSize) + 1) * modules.notes.gridSize) + 'px',
                });
            }
        }

        $(".editSticky").off("click").on("click", modules.notes.modifySticky);
        $(".showFyeo").off("click").on("click", modules.notes.showFyeo);
        $(".noteCheckbox").off("click").on("click", modules.notes.stickyCheckbox);

        modules.notes.adjustStickiesContainer();
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("notes.notes");

        if (modules.notes.menuItem) {
            $("#" + modules.notes.menuItem).children().first().attr("href", "?#notes&_=" + Math.random());
        }

        if (parseInt(myself.uid) && AVAIL("notes")) {
            $("#leftTopDynamic").html(`<li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-success text-bold createNote">${i18n("notes.createNote")}</span></li>`);
        }

        $(".createNote").off("click").on("click", () => {
            modules.notes.createNote();
        });

        $("#mainForm").html("<div id='stickiesTable'><div style='overflow-x: scroll; overflow-y: hidden;' class='p-0 m-0 mt-3'><div id='stickiesContainer' style='position: relative;' class='p-0 m-0 resizable mouseEvents dots'></div></div></div>");

        let s = $("#stickiesContainer");

        modules.notes.initialHeight = s.parent().height();

        modules.notes.adjustStickiesContainer();

        $("#stickiesContainer").off("windowResized").on("windowResized", () => {
            modules.notes.adjustStickiesContainer();
        });

        let rtd = '';

        rtd += '<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="notesCategories" class="form-control select-arrow right-top-select top-input"></select></div></div></form>';

        $("#rightTopDynamic").html(rtd);

        $("#notesCategories").off("change").on("change", () => {
            lStore("notesCategory", $("#notesCategories").val());
            modules.notes.renderNotes();
        });

        if (modules.notes.categories.indexOf(i18n("notes.default")) < 0) {
            modules.notes.categories.push(i18n("notes.default"));
        }

        GET("notes", "notes", false, true).
        done(result => {
            if (result && result.notes) {
                for (let i in result.notes) {
                    let id = "note-" + result.notes[i].id;

                    modules.notes.notes[id] = result.notes[i];

                    if (modules.notes.categories.indexOf(result.notes[i].category) < 0) {
                        modules.notes.categories.push(result.notes[i].category);
                    }
                }

                modules.notes.categories.sort();
            }

            modules.notes.renderNotes();
        }).
        fail(FAILPAGE).
        always(loadingDone);
    },

/*
    search: function (search) {
        console.log(search);
    }
*/
}).init();