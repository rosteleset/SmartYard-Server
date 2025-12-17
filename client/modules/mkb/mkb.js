({
    menuItem: false,
    md: false,
    calendars: {},

    init: function () {
        if (parseInt(myself.uid) && AVAIL("mkb")) {
            this.menuItem = leftSide("fas fa-fw fa-layer-group", i18n("mkb.mkb"), "?#mkb", "productivity");
        }
        moduleLoaded("mkb", this);
    },

    allLoaded: function () {
        modules.mkb.md = new remarkable.Remarkable({
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

        modules.mkb.md.core.ruler.enable([
            'abbr'
        ]);

        modules.mkb.md.block.ruler.enable([
            'footnote',
            'deflist'
        ]);

        modules.mkb.md.inline.ruler.enable([
            'footnote_inline',
            'ins',
            'mark',
            'sub',
            'sup'
        ]);
    },

    renderCard: function (card) {
        let s = '';

        if (card.subtasks) {
            s += '<hr class="hr-subject" /><div class="subtasks pb-2">';

            let p = 0;

            for (let i in card.subtasks) {
                s += `
                    <div>
                        <span id="card-subtask-${i}" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="${card.subtasks[i].checked ? "1" : "0"}"><i class="${card.subtasks[i].checked ? "fas fa-check-circle text-success" : "far fa-circle"}"></i></span>
                        <span data-for="card-subtask-${i}" class="btn-checkbox-label text-no-bold">${card.subtasks[i].text}</span>
                    </div>
                `;

                if (card.subtasks[i].checked) {
                    p++;
                }
            }

            p = Math.round((p / card.subtasks.length) * 1000) / 10;

            s += `</div><div class="pointer subtasks-progress pt-1 pb-1"><div class="progress"><div class="progress-bar progress-bar-danger progress-bar-striped" role="progressbar" style="width: ${p}%" aria-valuenow="${p}" aria-valuemin="0" aria-valuemax="100">${p}%</div></div></div>`;
        }

        let b = '';

        if (card.body) {
            b = `<hr class="min-max" /><div class="min-max">${card.body}</div>`;
        }

        let h = `
            <div id="card-${card.id}" class="card card-info card-outline">
                <div class="card-header card-handle">
                    <h5 class="card-title">
                        <span class="btn btn-tool btn-checkbox pl-0" data-checked="0"><i class="far fa-circle"></i></span>
                        <span class="btn btn-tool">#1</span>
                        <span class="btn btn-tool text-danger">5дн</span>
                    </h5>
                    <div class="card-tools">
                        <span class="dropdown card-calendar" data-card-id="${card.id}">
                            <span class="btn btn-tool text-info dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-bottom: -8px;">
                                <i class="far fa-fw fa-calendar-alt"></i>
                                <ul class="dropdown-menu">
                                    <li id="dropdown-calendar-${card.id}"></li>
                                </ul>
                            </span>
                        </span>
                        <span class="btn btn-tool text-primary"><i class="fas fa-fw fa-link"></i></span>
                        <span class="btn btn-tool"><i class="fas fa-fw fa-edit"></i></span>
                        <span class="btn btn-tool btn-min-max"><i class="fas fa-fw fa-minus"></i></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-bold">${card.subject}</div>
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
            c += modules.mkb.renderCard(column.cards[i]);
        }

        let h = `
            <div id="card-${column.id}" class="card card-row card-${column.color} kanban-col">
                <div class="card-header col-handle">
                    <h3 class="card-title">${column.title}</h3>
                    <div class="card-tools">
                        <span class="btn btn-tool"><i class="far fa-fw fa-clipboard"></i></span>
                        <span class="btn btn-tool"><i class="fas fa-fw fa-plus-circle"></i></span>
                        <span class="btn btn-tool"><i class="fas fa-fw fa-edit"></i></span>
                    </div>
                </div>
                <div id="card-body-${column.id}" class="card-body card-no-scroll card-content" style="min-height: 100%;">${c}</div>
            </div>
        `;

        return $.trim(h);
    },

    assignHandlers: function () {
        $(".card-content").each(function () {
            let col = $(this);
            new Sortable(document.getElementById(col.attr("id")), {
                "handle": ".card-handle",
                "animation": 150,
                "group": "cols",
            });
        });

        new Sortable(document.getElementById("desk"), {
            "handle": ".col-handle",
            "animation": 150,
        });

        $(".subtasks-progress").off("click").on("click", function () {
            let pb = $(this);
            if (pb.hasClass("pointer")) {
                if (pb.attr("data-minimized") == "true") {
                    $(".subtasks").show();
                    $(".hr-subject").show();
                    pb.attr("data-minimized", "false").removeClass("pt-3").addClass("pt-1");
                } else {
                    $(".subtasks").hide();
                    $(".hr-subject").hide();
                    pb.attr("data-minimized", "true").addClass("pt-3").removeClass("pt-1");
                }
            }
        });

        $(".btn-checkbox").off("click").on("click", function () {
            let btn = $(this);
            if (btn.attr("data-checked") == "1") {
                btn.attr("data-checked", "0").removeClass("text-success").children().first().addClass("far fa-circle").removeClass("fas fa-check-circle");
            } else {
                btn.attr("data-checked", "1").addClass("text-success").children().first().removeClass("far fa-circle").addClass("fas fa-check-circle");
            }
        });

        $(".btn-checkbox-label").off("click").on("click", function () {
            $("#" + $(this).attr("data-for")).trigger("click");
        });

        $(".card-calendar").off("show.bs.dropdown").on("show.bs.dropdown", function () {
            let id = $(this).attr("data-card-id");

            if (!modules.mkb.calendars[id]) {
                $(`#dropdown-calendar-${id}`).html(`<span id='calendar-${id}'></span>`).off("click dragstart pointerdown mousedown touchstart dragover dragenter").on("click dragstart pointerdown mousedown touchstart dragover dragenter", e => {
                    e.stopPropagation();
                });

                modules.mkb.calendars[id] = new VanillaCalendarPro.Calendar(`#calendar-${id}`, {
                    locale: config.defaultLanguage,
                    selectedMonth: 6,
                    selectedYear: 2024,
                    selectedDates: [ '2024-07-22' ],
                    selectionTimeMode: 24,
                    selectedTime: '12:15',
                });

                modules.mkb.calendars[id].init();
            }
        });

        $(".card-calendar").off("hide.bs.dropdown").on("hide.bs.dropdown", function () {
            let id = $(this).attr("data-card-id");

            console.log(modules.mkb.calendars[id].context.selectedTime, modules.mkb.calendars[id].context.selectedDates);
        });

        $(".btn-min-max").off("click").on("click", function () {
            if ($(".btn-min-max").children().first().hasClass("fa-minus")) {
                $(".btn-min-max").children().first().removeClass("fa-minus").addClass("fa-plus");
                $(".subtasks-progress").removeClass("pt-1").addClass("pt-3").removeClass("pointer");
                $(".hr-subject").hide();
                $(".min-max").hide();
                $(".subtasks").hide();
            } else {
                $(".btn-min-max").children().first().addClass("fa-minus").removeClass("fa-plus");
                let pb = $(".subtasks-progress");
                if (pb.attr("data-minimized") == "true") {
                    $(".subtasks").hide();
                    $(".hr-subject").hide();
                    pb.attr("data-minimized", "true").addClass("pt-3").removeClass("pt-1");
                } else {
                    $(".subtasks").show();
                    $(".hr-subject").show();
                    pb.attr("data-minimized", "false").removeClass("pt-3").addClass("pt-1");
                }
                pb.addClass("pointer");
                $(".min-max").show();
            }
        });
    },

    route: function (params) {
        subTop();

        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

        if (modules.mkb.menuItem) {
            $("#" + modules.mkb.menuItem).children().first().attr("href", navigateUrl("mkb"));
        }

        if (parseInt(myself.uid) && AVAIL("mkb")) {
            $("#leftTopDynamic").html(`
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-success text-bold addDesk">${i18n("mkb.addDesk")}</span></li>
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-primary text-bold addColumn">${i18n("mkb.addColumn")}</span></li>
            `);
        }

        let h = `
            <div class="content-wrapper kanban pt-3" style="margin-left: 0px!important; margin-top: 0px!important;">
                <section class="content pb-3 pl-0 pr-0">
                    <div id="desk" class="h-100 kanban-desk" style="display: flex;"></div>
                </section>
            </div>
        `;

        $("#mainForm").html($.trim(h));

        h = '';

        let desk = {
            title: 'first desk',
            columns: [
                {
                    id: md5(guid()),
                    title: 'first column',
                    color: 'purple',
                    cards: [
                        {
                            id: md5(guid()),
                            subject: 'subject',
                            body: 'lorm ipsum....',
                            subtasks: [
                                {
                                    text: "1",
                                },
                                {
                                    text: "2",
                                    checked: true,
                                },
                                {
                                    text: "3",
                                    checked: true,
                                },
                                {
                                    text: "4",
                                },
                            ]
                        },
                        {
                            id: md5(guid()),
                            subject: 'subject',
                            body: 'lorm ipsum....',
                            subtasks: [
                                {
                                    text: "1",
                                },
                                {
                                    text: "2",
                                    checked: true,
                                },
                                {
                                    text: "3",
                                    checked: true,
                                },
                                {
                                    text: "4",
                                },
                            ]
                        },
                    ],
                },
                {
                    id: md5(guid()),
                    title: 'second column',
                    color: 'red',
                    cards: [
                        {
                            id: md5(guid()),
                            subject: 'subject',
                            body: 'lorm ipsum....',
                            subtasks: [
                                {
                                    text: "1",
                                },
                                {
                                    text: "2",
                                    checked: true,
                                },
                                {
                                    text: "3",
                                    checked: true,
                                },
                                {
                                    text: "4",
                                },
                            ]
                        },
                    ],
                },
            ],
        };

        if (desk.columns) {
            for (let i in desk.columns) {
                h += modules.mkb.renderColumn(desk.columns[i]);
            }
        }

        $("#desk").html($.trim(h));

        modules.mkb.assignHandlers();

        loadingDone();
    },

    search: function (search) {
        console.log(search);
    }
}).init();