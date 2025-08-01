({
    menuItem: false,

    initialHeight: 0,

    stretchedWidth: 0,
    stretchedHeight: 0,

    isDragging: false,
    dragTarget: undefined,

    lastOffsetX: 0,
    lastOffsetY: 0,
    lastLeft: 0,
    lastTop: 0,

    notes: {},
    categories: [],

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("notes")) {
                this.menuItem = leftSide("fas fa-fw fa-thumbtack", i18n("notes.notes"), "?#notes", "notes");
            }
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

                let gridSize = 10;

                let newX = Math.max(-off.left + e.clientX - modules.notes.lastOffsetX, 0);
                let newY = Math.max($("html").scrollTop() - off.top + e.clientY - modules.notes.lastOffsetY, 0);

                newX = Math.round(newX / gridSize) * gridSize;
                newY = Math.round(newY / gridSize) * gridSize;

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

    renderNote: function (id, subject, body, color, icon, font, checks, remind, z) {
        let newSticky = `<div id='${id}' class='drag sticky ${color ? color : "bg-warning"}' style='z-index: ${z};'>`;

        let ch = {
            "bg-warning": "background-color: #3498db;",
            "bg-primary": "background-color: white;",
            "bg-secondary": "background-color: black;",
            "bg-success": "background-color: white;",
            "bg-danger": "background-color: yellow;",
            "bg-info": "",
            "bg-purple": "background-color: yellow;",
            "bg-orange": "",
            "bg-lightblue": "",
            "bg-fuchsia": "",
            "bg-black": "background-color: yellow;",
            "bg-lime": "",
        };

        let cc = {
            "bg-warning": "color: black; background-color: white;",
            "bg-primary": "color: blue; background-color: white;",
            "bg-secondary": "color: white; background-color: black;",
            "bg-success": "color: green; background-color: white;",
            "bg-danger": "color: black; background-color: yellow;",
            "bg-info": "",
            "bg-purple": "color: black; background-color: yellow;",
            "bg-orange": "",
            "bg-lightblue": "",
            "bg-fuchsia": "",
            "bg-black": "color: black; background-color: yellow;",
            "bg-lime": "",
        };

        subject = $.trim(subject);
        if (subject) {
            newSticky += `<h5 class="caption">`;
            if ($.trim(icon)) {
                newSticky += `<i class="fa-fw ${icon} mr-2"></i>`;
            }
            newSticky += convertLinks(nl2br(escapeHTML(subject)));
            newSticky += "</h5>";
            if (ch[color]) {
                newSticky += `<hr style="${ch[color]}" />`;
            } else {
                newSticky += "<hr />";
            }
        }

        newSticky += "<p class='body'";
        if ($.trim(font)) {
            newSticky += `style='font-family: ${font}'`
        }
        newSticky += ">";

        if (parseInt(checks)) {
            let b = body.split("\n");
            for (let i in b) {
                newSticky += `<span class='mr-2'><input type='checkbox' class='noteCheckbox' ${(b[i][0] == "+") ? "checked" : ""} data-line='${i}'/></span><span>${convertLinks(nl2br(escapeHTML(b[i].substring(1))))}</span><br />`;
            }
        } else {
            newSticky += convertLinks(nl2br(escapeHTML(body)));
        }

        if (cc[color]) {
            newSticky += `</p><i class="far fa-fw fa-edit editSticky" style="${cc[color]}"></i>`;
        } else {
            newSticky += '</p><i class="far fa-fw fa-edit editSticky"></i>';
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
            size: "lg",
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
                    id: "checks",
                    title: i18n("notes.checks"),
                    type: "noyes",
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
                    type: "themeColor",
                    value: "bg-warning",
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

                if (parseInt(r.checks)) {
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
                    r.color ? ("text-" + r.color) : "",
                    r.icon,
                    r.font,
                    r.checks,
                    parseInt(r.remind) > (new Date()).getTime() / 1000,
                    z + 1
                );

                stickyArea.append(newSticky);

                let sticky = $("#" + id);

                let x = window.innerWidth / 2 - sticky.outerWidth(true) / 2 + (-100 + Math.round(Math.random() * 50));
                let y = window.innerHeight / 2 - sticky.outerHeight(true) / 2 + (-100 + Math.round(Math.random() * 50));

                sticky.css({
                    left: x + 'px',
                    top: y + 'px',
                });

                $(".editSticky").off("click").on("click", modules.notes.modifySticky);
                $(".noteCheckbox").off("click").on("click", modules.notes.stickyCheckbox);

                modules.notes.adjustStickiesContainer();

                loadingStart();

                POST("notes", "note", false, {
                    subject: r.subject,
                    body: r.body,
                    checks: r.checks,
                    category: r.category,
                    remind: r.remind,
                    icon: r.icon,
                    font: r.font,
                    color: r.color,
                    x: parseFloat(x),
                    y: parseFloat(y),
                    z: parseInt(z),
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

    adjustStickiesContainer: function (init) {
        let wi = $(window);
        let ct = $("#stickiesContainer");

        let w = ct.width();
        let h = wi.height() - mainFormTop - modules.notes.initialHeight;

        if (init) {
            modules.notes.stretchedWidth = w;
            modules.notes.stretchedHeight = h;
            ct.css({
                width: w + "px",
                height: h + "px",
            });
        }

        let mh = 0, mw = 0;

        $(".sticky").each(function () {
            let s = $(this);
            mw = Math.max(mw, s.position().left + s.outerWidth(true));
            mh = Math.max(mh, s.position().top + s.outerHeight(true) + 20);
        });

        mw = Math.max(modules.notes.stretchedWidth, mw);
        mh = Math.max(modules.notes.stretchedHeight, mh - modules.notes.initialHeight);

        ct.css({
            width: mw + "px",
            height: mh + "px",
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
        if (parseInt(modules.notes.notes[id].checks)) {
            let b = modules.notes.notes[id].body.split("\n");
            let d = md5(guid());
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
            size: "lg",
            fields: [
                {
                    id: "subject",
                    title: i18n("notes.subject"),
                    type: "text",
                    value: modules.notes.notes[id].subject,
                },
                {
                    id: "body",
                    title: parseInt(modules.notes.notes[id].checks) ? i18n("notes.list") : i18n("notes.body"),
                    type: parseInt(modules.notes.notes[id].checks) ? "sortable" : "area",
                    validate: a => {
                        return parseInt(modules.notes.notes[id].checks) ? a.length > 0 : $.trim(a) != '';
                    },
                    value: parseInt(modules.notes.notes[id].checks) ? undefined : modules.notes.notes[id].body,
                    options: parseInt(modules.notes.notes[id].checks) ? checks : undefined,
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
                    type: "themeColor",
                    value: modules.notes.notes[id].color,
                },
            ],
            callback: r => {

                if (parseInt(modules.notes.notes[id].checks)) {
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
                    modules.notes.notes[id].category = r.category;
                    modules.notes.notes[id].remind = r.remind;
                    modules.notes.notes[id].icon = r.icon;
                    modules.notes.notes[id].font = r.font;
                    modules.notes.notes[id].color = r.color ? ("text-" + r.color) : "";

                    modules.notes.notes[id].x = parseFloat(x);
                    modules.notes.notes[id].y = parseFloat(y);
                    modules.notes.notes[id].z = parseInt(z);

                    let stickyArea = $('#stickiesContainer');

                    let newSticky = modules.notes.renderNote(
                        id,
                        r.subject,
                        r.body,
                        r.color,
                        r.icon,
                        r.font,
                        modules.notes.notes[id].checks,
                        parseInt(r.remind) > (new Date()).getTime() / 1000,
                        z
                    );

                    stickyArea.append(newSticky);

                    let sticky = $("#" + id);

                    sticky.css({
                        left: x + 'px',
                        top: y + 'px',
                    });

                    $(".editSticky").off("click").on("click", modules.notes.modifySticky);
                    $(".noteCheckbox").off("click").on("click", modules.notes.stickyCheckbox);

                    loadingStart();

                    PUT("notes", "note", modules.notes.notes[id].id, {
                        subject: r.subject,
                        body: r.body,
                        category: r.category,
                        remind: r.remind,
                        icon: r.icon,
                        font: r.font,
                        color: r.color,
                        x: parseFloat(x),
                        y: parseFloat(y),
                        z: parseInt(z),
                    }).
                    fail(FAIL).
                    always(loadingDone);
                }

                modules.notes.adjustStickiesContainer();
            },
        });
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
                    modules.notes.notes[id].color,
                    modules.notes.notes[id].icon,
                    modules.notes.notes[id].font,
                    modules.notes.notes[id].checks,
                    parseInt(modules.notes.notes[id].remind) > (new Date()).getTime() / 1000 && !modules.notes.notes[id].reminded,
                    modules.notes.notes[id].z
                );

                stickyArea.append(newSticky);

                let sticky = $("#" + id);

                sticky.css({
                    left: modules.notes.notes[id].x + 'px',
                    top: modules.notes.notes[id].y + 'px',
                });
            }
        }

        $(".editSticky").off("click").on("click", modules.notes.modifySticky);
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

        $("#mainForm").html(`<div style="overflow-x: scroll; overflow-y: hidden;" class="p-0 m-0 mt-3"><div id="stickiesContainer" style="position: relative;" class="p-0 m-0 resizable mouseEvents dots"></div></div>`);

        let s = $("#stickiesContainer");

        modules.notes.initialHeight = s.parent().height();

        modules.notes.adjustStickiesContainer(true);

        $("#stickiesContainer").off("windowResized").on("windowResized", () => {
            modules.notes.adjustStickiesContainer(true);
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