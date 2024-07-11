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
        moduleLoaded("notes", this);
    },

    createNote: function () {
        let icons = [
            {
                text: i18n("notes.withoutIcon"),
                value: "",
            },
        ];

        for (let i in faIcons) {
            icons.push({
                icon: faIcons[i].title + " fa-fw",
                text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                value: faIcons[i].title,
            });
        }

        let fonts = [
            {
                text: i18n("notes.default"),
                value: "",
            },
        ];

        for (let i in availableFonts) {
            fonts.push({
                text: availableFonts[i],
                value: availableFonts[i],
                font: availableFonts[i],
            });
        }

        let already = {};
        already[i18n("notes.default")] = true;

        let categories = [
            {
                text: i18n("notes.default"),
                value: i18n("notes.default"),
            },
        ];

        for (let i in modules.notes.categories) {
            if (!already[modules.notes.categories[i]]) {
                categories.push(
                    {
                        text: modules.notes.categories[i],
                        value: modules.notes.categories[i],
                    }
                );
            }
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
                    value: i18n("notes.default"),
                    options: categories,
                },
                {
                    id: "remind",
                    title: i18n("notes.remind"),
                    type: "datetime-local",
                },
                {
                    id: "icon",
                    title: i18n("notes.icon"),
                    type: "select2",
                    options: icons,
                    value: "",
                },
                {
                    id: "font",
                    title: i18n("notes.font"),
                    type: "select2",
                    options: fonts,
                },
                {
                    id: "color",
                    title: i18n("notes.color"),
                    type: "select2",
                    options: [
                        {
                            text: i18n("notes.default"),
                            value: "bg-warning",
                            icon: "p-1 fas fa-palette bg-warning",
                        },
                        {
                            text: "Primary",
                            value: "bg-primary",
                            icon: "p-1 fas fa-palette bg-primary",
                        },
                        {
                            text: "Secondary",
                            value: "bg-secondary",
                            icon: "p-1 fas fa-palette bg-secondary",
                        },
                        {
                            text: "Success",
                            value: "bg-success",
                            icon: "p-1 fas fa-palette bg-success",
                        },
                        {
                            text: "Danger",
                            value: "bg-danger",
                            icon: "p-1 fas fa-palette bg-danger",
                        },
                        {
                            text: "Info",
                            value: "bg-info",
                            icon: "p-1 fas fa-palette bg-info",
                        },
                        {
                            text: "Purple",
                            value: "bg-purple",
                            icon: "p-1 fas fa-palette bg-purple",
                        },
                        {
                            text: "Orange",
                            value: "bg-orange",
                            icon: "p-1 fas fa-palette bg-orange",
                        },
                        {
                            text: "Lightblue",
                            value: "bg-lightblue",
                            icon: "p-1 fas fa-palette bg-lightblue",
                        },
                        {
                            text: "Fuchsia",
                            value: "bg-fuchsia",
                            icon: "p-1 fas fa-palette bg-fuchsia",
                        },
                        {
                            text: "Black",
                            value: "bg-black",
                            icon: "p-1 fas fa-palette bg-black",
                        },
                        {
                            text: "Gray",
                            value: "bg-gray",
                            icon: "p-1 fas fa-palette bg-gray",
                        },
                        {
                            text: "Lime",
                            value: "bg-lime",
                            icon: "p-1 fas fa-palette bg-lime",
                        },
                    ],
                    value: "bg-warning",
                },
            ],
            callback: r => {
                let stickyArea = $('#stickiesContainer');

                let id = md5(guid());

                let z = 1;

                $(".sticky").each(function () {
                    z = Math.max(z, parseInt($(this).css("z-index")));
                });

                let newSticky = `<div id='${id}' class='drag sticky ${r.color}' style='z-index: ${z + 1};'>`;
                if (convertLinks(nl2br(escapeHTML($.trim(r.subject))))) {
                    newSticky += `<h5 class="caption">`;
                    if ($.trim(r.icon)) {
                        newSticky += `<i class="fa-fw ${r.icon} mr-1"></i>`;
                    }
                    newSticky += r.subject;
                    newSticky += "</h5><hr />";
                }
                newSticky += "<p class='body'";
                if ($.trim(r.font)) {
                    newSticky += `style='font-family: ${r.font}'`
                }
                newSticky += ">";
                newSticky += convertLinks(nl2br(escapeHTML(r.body)));
                newSticky += '</p><i class="far fa-fw fa-edit editSticky"></i>';
                if (r.remind) {
                    newSticky += '<i class="far fa-fw fa-clock text-small reminder"></i>';
                }
                newSticky += '</div>';

                stickyArea.append(newSticky);

                let sticky = $("#" + id);

                let x = window.innerWidth / 2 - sticky.outerWidth(true) / 2 + (-100 + Math.round(Math.random() * 50));
                let y = window.innerHeight / 2 - sticky.outerHeight(true) / 2 + (-100 + Math.round(Math.random() * 50));

                sticky.css({
                    left: x + 'px',
                    top: y + 'px',
                });

                $(".editSticky").off("mousedown").on("mousedown", e => {
                    e.preventDefault();
                    return false;
                });
                $(".editSticky").off("click").on("click", modules.notes.modifySticky);

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
                        let id = "note-" + $.trim(r);
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

        let icons = [
            {
                text: i18n("notes.withoutIcon"),
                value: "",
            },
        ];

        for (let i in faIcons) {
            icons.push({
                icon: faIcons[i].title + " fa-fw",
                text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                value: faIcons[i].title,
            });
        }

        let fonts = [
            {
                text: i18n("notes.default"),
                value: "",
            },
        ];

        for (let i in availableFonts) {
            fonts.push({
                text: availableFonts[i],
                value: availableFonts[i],
                font: availableFonts[i],
            });
        }

        let already = {};
        already[i18n("notes.default")] = true;

        let categories = [
            {
                text: i18n("notes.default"),
                value: i18n("notes.default"),
            },
        ];

        for (let i in modules.notes.categories) {
            if (!already[modules.notes.categories[i]]) {
                categories.push(
                    {
                        text: modules.notes.categories[i],
                        value: modules.notes.categories[i],
                    }
                );
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
                    title: i18n("notes.body"),
                    type: "area",
                    validate: a => {
                        return $.trim(a) != '';
                    },
                    value: modules.notes.notes[id].body,
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
                },
                {
                    id: "icon",
                    title: i18n("notes.icon"),
                    type: "select2",
                    options: icons,
                    value: modules.notes.notes[id].icon,
                },
                {
                    id: "font",
                    title: i18n("notes.font"),
                    type: "select2",
                    options: fonts,
                    value: modules.notes.notes[id].font,
                },
                {
                    id: "color",
                    title: i18n("notes.color"),
                    type: "select2",
                    options: [
                        {
                            text: i18n("notes.default"),
                            value: "bg-warning",
                            icon: "p-1 fas fa-palette bg-warning",
                        },
                        {
                            text: "Primary",
                            value: "bg-primary",
                            icon: "p-1 fas fa-palette bg-primary",
                        },
                        {
                            text: "Secondary",
                            value: "bg-secondary",
                            icon: "p-1 fas fa-palette bg-secondary",
                        },
                        {
                            text: "Success",
                            value: "bg-success",
                            icon: "p-1 fas fa-palette bg-success",
                        },
                        {
                            text: "Danger",
                            value: "bg-danger",
                            icon: "p-1 fas fa-palette bg-danger",
                        },
                        {
                            text: "Info",
                            value: "bg-info",
                            icon: "p-1 fas fa-palette bg-info",
                        },
                        {
                            text: "Purple",
                            value: "bg-purple",
                            icon: "p-1 fas fa-palette bg-purple",
                        },
                        {
                            text: "Orange",
                            value: "bg-orange",
                            icon: "p-1 fas fa-palette bg-orange",
                        },
                        {
                            text: "Lightblue",
                            value: "bg-lightblue",
                            icon: "p-1 fas fa-palette bg-lightblue",
                        },
                        {
                            text: "Fuchsia",
                            value: "bg-fuchsia",
                            icon: "p-1 fas fa-palette bg-fuchsia",
                        },
                        {
                            text: "Black",
                            value: "bg-black",
                            icon: "p-1 fas fa-palette bg-black",
                        },
                        {
                            text: "Gray",
                            value: "bg-gray",
                            icon: "p-1 fas fa-palette bg-gray",
                        },
                        {
                            text: "Lime",
                            value: "bg-lime",
                            icon: "p-1 fas fa-palette bg-lime",
                        },
                    ],
                    value: modules.notes.notes[id].color,
                },
            ],
            callback: r => {
                $("#" + id).remove();

                if (r.delete) {
                    mConfirm(i18n("notes.deleteNote"), i18n("confirm"), i18n("delete"), () => {
                        DELETE("notes", "note", modules.notes.notes[id].id).
                        fail(FAIL).
                        always(loadingDone);
                    });
                } else {
                    let stickyArea = $('#stickiesContainer');

                    let z = modules.notes.notes[id].z;

                    let newSticky = `<div id='${id}' class='drag sticky ${r.color}' style='z-index: ${z};'>`;
                    if (convertLinks(nl2br(escapeHTML($.trim(r.subject))))) {
                        newSticky += `<h5 class="caption">`;
                        if ($.trim(r.icon)) {
                            newSticky += `<i class="fa-fw ${r.icon} mr-1"></i>`;
                        }
                        newSticky += r.subject;
                        newSticky += "</h5><hr />";
                    }
                    newSticky += "<p class='body'";
                    if ($.trim(r.font)) {
                        newSticky += `style='font-family: ${r.font}'`
                    }
                    newSticky += ">";
                    newSticky += convertLinks(nl2br(escapeHTML(r.body)));
                    newSticky += '</p><i class="far fa-fw fa-edit editSticky"></i>';
                    if (r.remind) {
                        newSticky += '<i class="far fa-fw fa-clock text-small reminder"></i>';
                    }
                    newSticky += '</div>';

                    stickyArea.append(newSticky);

                    let sticky = $("#" + id);

                    let x = modules.notes.notes[id].x;
                    let y = modules.notes.notes[id].y;

                    sticky.css({
                        left: x + 'px',
                        top: y + 'px',
                    });

                    $(".editSticky").off("mousedown").on("mousedown", e => {
                        e.preventDefault();
                        return false;
                    });
                    $(".editSticky").off("click").on("click", modules.notes.modifySticky);

                    loadingStart();

                    if (modules.notes.categories.indexOf(r.category) < 0) {
                        modules.notes.categories.push(r.category);
                        modules.notes.categories.sort();
                    }

                    modules.notes.notes[id].subject = r.subject;
                    modules.notes.notes[id].body = r.body;
                    modules.notes.notes[id].category= r.category;
                    modules.notes.notes[id].remind = r.remind;
                    modules.notes.notes[id].icon = r.icon;
                    modules.notes.notes[id].font = r.font;
                    modules.notes.notes[id].color = r.color;
                    modules.notes.notes[id].x = parseFloat(x);
                    modules.notes.notes[id].y = parseFloat(y);
                    modules.notes.notes[id].z = parseInt(z);

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
                    done(r => {
                        sticky.attr("id", "note-" + $.trim(r));
                    }).
                    fail(FAIL).
                    always(loadingDone);
                }

                modules.notes.adjustStickiesContainer();
            },
        });
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

        $(window).off("mousedown").on("mousedown", e => {
            let target = $(e.target);

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

        $(window).off("mousemove").on("mousemove", e => {
            if (!modules.notes.isDragging) return;

            let cont = $("#stickiesContainer");

            if (modules.notes.isDragging == 1) {
                let off = cont.offset();

                modules.notes.dragTarget.css({
                    left: -off.left + e.clientX - modules.notes.lastOffsetX + 'px',
                    top: $("html").scrollTop() - off.top + e.clientY - modules.notes.lastOffsetY + 'px',
                });
            }

            if (modules.notes.isDragging == 2) {
                let dx = e.clientX - modules.notes.lastOffsetX;
                let dy = e.clientY - modules.notes.lastOffsetY;

                cont.parent().scrollLeft(modules.notes.lastLeft - dx);
                $("html").scrollTop(modules.notes.lastTop - dy);
            }
        });

        $(window).off("mouseup").on("mouseup", e => {
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

        $(".createNote").off("click").on("click", () => {
            modules.notes.createNote();
        });

        $("#mainForm").html(`<div style="overflow-x: scroll; overflow-y: hidden;" class="p-0 m-0 mt-3"><div id="stickiesContainer" style="position: relative;" class="p-0 m-0 resizable mouseEvents"></div></div>`);

        let s = $("#stickiesContainer");

        modules.notes.initialHeight = s.parent().height();

        modules.notes.adjustStickiesContainer(true);

        $("#stickiesContainer").off("windowResized").on("windowResized", () => {
            modules.notes.adjustStickiesContainer(true);
            modules.notes.adjustStickiesContainer();
        });

        let rtd = "";
        rtd += `<div class="form-inline mt-1 mr-3"><div class="input-group input-group-sm"><select id="notesCategories" class="form-control select-arrow" style="width: 259px;">`;
        rtd += `</select></div>`;

        $("#rightTopDynamic").html(rtd);

        GET("notes", "notes", false, true).
        done(result => {
            console.log(result);

            if (result && result.notes) {
                for (let i in result.notes) {

                    let stickyArea = $('#stickiesContainer');

                    let id = "note-" + result.notes[i].id;

                    modules.notes.notes[id] = result.notes[i];

                    let z = result.notes[i].z;

                    let newSticky = `<div id='${id}' class='drag sticky ${result.notes[i].color ? result.notes[i].color : "bg-warning"}' style='z-index: ${z};'>`;
                    let subject = $.trim(result.notes[i].subject);
                    if (subject) {
                        newSticky += `<h5 class="caption">`;
                        if ($.trim(result.notes[i].icon)) {
                            newSticky += `<i class="fa-fw ${result.notes[i].icon} mr-1"></i>`;
                        }
                        newSticky += convertLinks(nl2br(escapeHTML(subject)));
                        newSticky += "</h5><hr />";
                    }
                    newSticky += "<p class='body'";
                    if ($.trim(result.notes[i].font)) {
                        newSticky += `style='font-family: ${result.notes[i].font}'`
                    }
                    newSticky += ">";
                    newSticky += convertLinks(nl2br(escapeHTML(result.notes[i].body)));
                    newSticky += '</p><i class="far fa-fw fa-edit editSticky"></i>';
                    if (result.notes[i].remind) {
                        newSticky += '<i class="far fa-fw fa-clock text-small reminder"></i>';
                    }
                    newSticky += '</div>';

                    stickyArea.append(newSticky);

                    let sticky = $("#" + id);

                    sticky.css({
                        left: result.notes[i].x + 'px',
                        top: result.notes[i].y + 'px',
                    });

                    if (modules.notes.categories.indexOf(result.notes[i].category) < 0) {
                        modules.notes.categories.push(result.notes[i].category);
                    }
                }

                modules.notes.categories.sort();

                $(".editSticky").off("mousedown").on("mousedown", e => {
                    e.preventDefault();
                    return false;
                });
                $(".editSticky").off("click").on("click", modules.notes.modifySticky);

                modules.notes.adjustStickiesContainer();
            }
        }).
        fail(FAILPAGE).
        always(loadingDone);
    },

    search: function (search) {
        console.log(search);
    }

}).init();