({
    menuItem: false,
    initialHeight: 0,
    stretchedHeight: 0,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("notes")) {
                this.menuItem = leftSide("fas fa-fw fa-thumbtack", i18n("notes.notes"), "?#notes", "notes");
            }
        }

        moduleLoaded("notes", this);
    },

    createNote: function () {
        let icons = [];
        for (let i in faIcons) {
            icons.push({
                icon: faIcons[i].title + " fa-fw",
                text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                value: faIcons[i].title,
            });
        }
        let fonts = [];
        for (let i in availableFonts) {
            fonts.push({
                text: availableFonts[i],
                value: availableFonts[i],
                font: availableFonts[i],
            });
        }
        cardForm({
            title: i18n("notes.addNote"),
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
                    value: "fas fa-thumbtack",
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
                    ],
                    value: "bg-warning",
                },
            ],
            callback: r => {
                //
            },
        });
    },

    adjustStickiesContainer: function (init) {
        let w = $(window);
        let s = $("#stickiesContainer");

        let h = w.height() - mainFormTop - modules.notes.initialHeight;

        if (init) {
            modules.notes.stretchedHeight = h;
            s.css("height", h + "px");
        } else {
            let mh = 0;
            $(".sticky").each(function () {
                let s = $(this);
                mh = Math.max(mh, s.position().top + s.outerHeight(true) + 20);
            });
            mh = Math.max(modules.notes.stretchedHeight, mh - modules.notes.initialHeight);
            s.css("height", mh + "px");
        }
    },

    modifySticky: function (e) {
        console.log(e);
    },

    createSticky: function(x) {
        let stickyArea = $('#stickiesContainer');

        let id = md5(guid());

        let newSticky = `<div id='${id}' class='drag sticky bg-warning' style='z-index: 1;'><h5>subject ${x}</h5><hr /><p>body</p><span class="editSticky"><i class="far fa-fw fa-edit"></i></span></div>`;

        stickyArea.append(newSticky);

        let sticky = $("#" + id);

        let left = window.innerWidth / 2 - sticky.outerWidth(true) / 2 + (-100 + Math.round(Math.random() * 50)) + 'px';
        let top = window.innerHeight / 2 - sticky.outerHeight(true) / 2 + (-100 + Math.round(Math.random() * 50)) + 'px';

        sticky.css({
            left: left,
            top: top,
        });

        $(".editSticky").off("mousedown").on("mousedown", e => {
            e.preventDefault();

            return false;
        });

        $(".editSticky").off("click").on("click", modules.notes.modifySticky)
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

        $("#mainForm").html(`<div style="overflow-x: scroll; overflow-y: hidden;" class="p-0 m-0 mt-3"><div id="stickiesContainer" style="position: relative;" class="p-0 m-0 resizable"></div></div>`);

        let isDragging = false;
        let dragTarget;

        let lastOffsetX = 0;
        let lastOffsetY = 0;
        let lastLeft = 0;
        let lastTop = 0;

        $("#stickiesContainer").off("mousedown").on("mousedown", e => {
            let target = $(e.target);

            if (target.hasClass('drag')) {
                let z = 1;

                $(".sticky").each(function () {
                    let me = $(this);
                    let mz = parseInt(me.css("z-index"));
                    if (mz > z) {
                        z = mz;
                    }
                });

                target.css({
                    "z-index": parseInt(z) + 1,
                    "cursor": "grab",
                });

                dragTarget = target;

                lastOffsetX = e.offsetX;
                lastOffsetY = e.offsetY;

                isDragging = 1;
            } else {
                target.css("cursor", "grab");

                lastLeft = target.parent().scrollLeft();
                lastTop = $("html").scrollTop();
                lastOffsetX = e.clientX;
                lastOffsetY = e.clientY;

                isDragging = 2;
            }
        });

        $("#stickiesContainer").off("mousemove").on("mousemove", e => {
            if (!isDragging) return;

            let cont = $("#stickiesContainer");

            if (isDragging == 1) {
                let off = cont.offset();

                dragTarget.css({
                    left: -off.left + e.clientX - lastOffsetX + 'px',
                    top: $("html").scrollTop() - off.top + e.clientY - lastOffsetY + 'px',
                });
            }

            if (isDragging == 2) {
                let dx = e.clientX - lastOffsetX;
                let dy = e.clientY - lastOffsetY;

                cont.parent().scrollLeft(lastLeft - dx);
                $("html").scrollTop(lastTop - dy);
            }
        });

        $("#stickiesContainer").off("mouseup").on("mouseup", e => {
            let target = $(e.target);

            modules.notes.adjustStickiesContainer();

            target.css({
                "cursor": "",
            });

            return isDragging = false;
        });

        let s = $("#stickiesContainer");
        modules.notes.initialHeight = s.parent().height();

        modules.notes.adjustStickiesContainer(true);

        setTimeout(() => {
            modules.notes.createSticky(1);
        }, 100);

        setTimeout(() => {
            modules.notes.createSticky(2);
        }, 100);

        $("#stickiesContainer").off("windowResized").on("windowResized", () => {
            modules.notes.adjustStickiesContainer(true);
            modules.notes.adjustStickiesContainer();
        });

        loadingDone();
    },

    search: function (search) {
        console.log(search);
    }

}).init();