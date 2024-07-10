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

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("notes")) {
                this.menuItem = leftSide("fas fa-fw fa-thumbtack", i18n("notes.notes"), "?#notes", "notes");
            }
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

            return modules.notes.isDragging = false;
        });

        moduleLoaded("notes", this);
    },

    createNote: function () {
        let icons = [
            {
                icon: "fa fa-fw",
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
                text: i18n("notes.fontByDefault"),
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
                    id: "category",
                    title: i18n("notes.category"),
                    type: "select2",
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
                            text: "По умолчанию",
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

                sticky.css({
                    left: window.innerWidth / 2 - sticky.outerWidth(true) / 2 + (-100 + Math.round(Math.random() * 50)) + 'px',
                    top: window.innerHeight / 2 - sticky.outerHeight(true) / 2 + (-100 + Math.round(Math.random() * 50)) + 'px',
                });

                $(".editSticky").off("mousedown").on("mousedown", e => {
                    e.preventDefault();

                    return false;
                });

                $(".editSticky").off("click").on("click", modules.notes.modifySticky)
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
        console.log(e);
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

        loadingDone();
    },

    search: function (search) {
        console.log(search);
    }

}).init();