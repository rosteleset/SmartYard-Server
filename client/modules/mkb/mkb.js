({
    menuItem: false,
    md: false,
    desks: [],
    calendars: {},

    init: function () {
        if (parseInt(myself.uid) && AVAIL("mkb")) {
            this.menuItem = leftSide("fas fa-fw fa-layer-group", i18n("mkb.mkb"), "?#mkb", "productivity");
        }
        moduleLoaded("mkb", this);
    },

    allLoaded: function () {
        //
    },

    mdr: function (str) {
        let f = convertLinks(DOMPurify.sanitize(rbtMdRender(str)));
        return f;
    },

    assignHandlers: function () {
        $(".card-content").each(function () {
            let col = $(this);
            new Sortable(document.getElementById(col.attr("id")), {
                "handle": ".card-handle",
                "animation": 150,
                "group": "cols",

                onEnd: e => {
                    let s = $("#" + e.item.id).offset().top - $("#mainForm").offset().top - 8;
                    if ($("html").scrollTop() > s) {
                        $("html").scrollTo(s);
                    }
/*
                    $(`#${e.to.id}`).children().each(function () {
                        let el = $(this);
                    });
*/
                },
            });
        });

        $(".subtasks").each(function () {
            let subtasks = $(this);
            new Sortable(document.getElementById(subtasks.attr("id")), {
                "animation": 150,

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

        new Sortable(document.getElementById("desk"), {
            "handle": ".col-handle",
            "animation": 150,
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

            $(`.progressbar-value[data-card-id="${id}"]`).css("width", p + "%").attr("aria-valuenow", p).text(p + "%");
        });

        $(".btn-min-max").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

            if ($(`.btn-min-max[data-card-id="${id}"]`).children().first().hasClass("fa-window-minimize")) {
                $(`.btn-min-max[data-card-id="${id}"]`).children().first().removeClass("fa-window-minimize").addClass("fa-window-restore");
                $(`.subtasks-progress[data-card-id="${id}"]`).removeClass("pt-1").addClass("pt-3").removeClass("pointer");
                $(`.hr-subject[data-card-id="${id}"]`).hide();
                $(`.min-max[data-card-id="${id}"]`).hide();
                $(`.subtasks[data-card-id="${id}"]`).hide();
            } else {
                $(`.btn-min-max[data-card-id="${id}"]`).children().first().addClass("fa-window-minimize").removeClass("fa-window-restore");
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

            cardForm({
                title: i18n("mkb.modifyColumn"),
                footer: true,
                borderless: true,
                topApply: true,
                apply: i18n("modify"),
                size: "lg",
                delete: i18n("delete"),
                fields: [
                    {
                        id: "title",
                        title: i18n("mkb.title"),
                        type: "text",
                        value: "",
                    },
                    {
                        id: "color",
                        title: i18n("mkb.color"),
                        type: "color",
                        value: "lime",
                    },
                ],
                callback: r => {
                    console.log(r);
                }
            });

            console.log(id);
        });

        $(".card-edit").off("click").on("click", function () {
            let id = $(this).attr("data-card-id");

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
                        id: "title",
                        title: i18n("mkb.title"),
                        tab: i18n("mkb.card"),
                        type: "text",
                        value: "",
                    },
                    {
                        id: "color",
                        title: i18n("mkb.color"),
                        tab: i18n("mkb.card"),
                        type: "color",
                        noEmpty: true,
                        value: "lime",
                    },
                    {
                        id: "tags",
                        title: i18n("mkb.tags"),
                        tab: i18n("mkb.card"),
                        type: "select2",
                        multiple: true,
                        createTags: true,
                        colorizeTags: true,
                        tags: true,
                        value: [ "tag1", "tag2", "tag3" ],
                        tags: [ "tag1", "tag2", "tag3" ],
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
                    console.log(r);
                }
            });
        });
    },

    renderCard: function (card) {
        let s = '';

        if (card.subtasks) {
            s += `<hr class="hr-subject" data-card-id="${card.id}" style="${card.subtasksMinimized ? "display: none;" : ""}" /><div id="subtasks-${card.id}" class="subtasks pb-2" data-card-id="${card.id}" style="${card.subtasksMinimized ? "display: none;" : ""}">`;

            let p = 0;

            for (let i in card.subtasks) {
                s += `
                    <div class="custom-control custom-checkbox"">
                        <input id="card-subtask-${card.id}-${i}" class="subtask-checkbox custom-control-input custom-control-input-primary custom-control-input-outline" type="checkbox"${card.subtasks[i].checked ? " checked " : " " }data-card-id=${card.id}>
                        <label for="card-subtask-${card.id}-${i}" class="pl-1 custom-control-label noselect text-no-bold">${$.trim(escapeHTML(card.subtasks[i].text))}</label>
                    </div>
                `;

                if (card.subtasks[i].checked) {
                    p++;
                }
            }

            p = Math.round((p / card.subtasks.length) * 1000) / 10;

            s += `</div><div class="${card.cardMinimized ? "" : "pointer"} subtasks-progress ${card.subtasksMinimized ? "pt-3" : "pt-1"} pb-1" data-card-id="${card.id}" data-minimized=${card.subtasksMinimized ? "true" : "false"}><div class="progress"><div class="progress-bar progress-bar-danger progress-bar-striped progressbar-value" role="progressbar" style="width: ${p}%" aria-valuenow="${p}" aria-valuemin="0" aria-valuemax="100" data-card-id="${card.id}">${p}%</div></div></div>`;
        }

        let b = '';

        if (card.body) {
            b = `
                <hr class="min-max" data-card-id="${card.id}" style="${card.cardMinimized ? "display: none;" : ""}" />
                <div class="min-max kanban-card-body" data-card-id="${card.id}" style="${card.cardMinimized ? "display: none;" : ""}">${modules.mkb.mdr(card.body)}</div>
            `;
        }

        let c = '';

        if (card.date) {
            let d = Math.ceil((card.date - ((new Date()).getTime() / 1000)) / (60 * 60 * 24));
            c = `
                <span class="dropdown card-calendar" data-card-id="${card.id}" data-date="${card.date}">
                    <span class="btn btn-tool ${(d >= 0) ? "text-success" : "text-danger"} dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-bottom: -8px;">
                        ${Math.abs(d)} ${i18n("mkb.days")}
                        <ul class="dropdown-menu">
                            <li id="dropdown-calendar-${card.id}"></li>
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
                <span class="badge bg-${systemColor(card.tags[i])} kanban-badge pr-2 pl-2 mt-1 pointer" style="border: solid thin #60686f">
                    ${$.trim(escapeHTML(card.tags[i]))}
                </span>
            `;
        }

        if (t) {
            t = `<div class="mt-2" data-card-id="${card.id}" style="font-size: 75%;">${t}</div>`;
        }

        let h = `
            <div id="card-${card.id}" class="kanban-card card card-${card.color} card-outline">
                <div class="card-header card-handle pl-1 pr-3">
                    <h5 class="card-title">${c}</h5>
                    <div class="card-tools">
                        <span class="btn btn-tool text-primary"><i class="fas fa-fw fa-link"></i></span>
                        <span class="btn btn-tool card-edit" data-card-id="${card.id}"><i class="fas fa-fw fa-edit"></i></span>
                        <span class="btn btn-tool btn-min-max" data-card-id="${card.id}"><i class="fas fa-fw fa-${card.cardMinimized ? "window-restore" : "window-minimize"}"></i></span>
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
            c += modules.mkb.renderCard(column.cards[i]);
        }

        let h = `
            <div id="card-${column.id}" class="card card-row card-${column.color} kanban-col">
                <div class="card-header col-handle pl-3 pr-3">
                    <h3 class="card-title pt-1 text-bold">${$.trim(escapeHTML(column.title))}</h3>
                    <div class="card-tools" data-column-id="${column.id}">
                        <span class="btn btn-tool"><i class="far fa-fw fa-clipboard"></i></span>
                        <span class="btn btn-tool"><i class="fas fa-fw fa-plus-circle"></i></span>
                        <span class="btn btn-tool column-edit"><i class="fas fa-fw fa-edit"></i></span>
                    </div>
                </div>
                <div id="card-body-${column.id}" class="card-body card-no-scroll card-content" style="min-height: 100%;">${c}</div>
            </div>
        `;

        return $.trim(h);
    },

    renderDesk: function () {
        loadingStart();

        GET("mkb", "desks", false, true).
        done(r1 => {
            let desk = lStore("mkbDesk");

            let h = '';

            modules.mkb.desks.sort();

            for (let i in modules.mkb.desks) {
                h += '<option>' + escapeHTML(modules.mkb.desks[i]) + '</option>';
            }

            if (!h) {
                h += '<option>' + escapeHTML(i18n("mkb.default")) + '</option>';
            }

            $("#mkbDesks").html(h);

            if (modules.mkb.desks.indexOf(desk) >= 0) {
                $("#mkbDesks").val(desk);
            }

            desk = $("#mkbDesks").val();
            lStore("mkbDesk", desk);

            GET("mkb", "desk", desk, true).
            done(r2 => {
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
                                    date: 1765843200,
                                    subject: 'subject',
                                    body: 'lorm ipsum....',
                                    cardMinimized: true,
                                    subtasksMinimized: true,
                                    color: "info",
                                    subtasks: [
                                        {
                                            text: "1",
                                            checked: true,
                                        },
                                        {
                                            text: "2 lskdjfhlgskjdfhgl ksdhfgl hdf lkshg kdfhg kfhf k ksdfh gkdfh ksjhjdfg ksjdhf ksdfh g",
                                        },
                                        {
                                            text: "3",
                                        },
                                        {
                                            text: "4",
                                        },
                                        {
                                            text: "5",
                                        },
                                        {
                                            text: "6",
                                        },
                                        {
                                            text: "7",
                                        },
                                        {
                                            text: "8",
                                        },
                                        {
                                            text: "9",
                                        },
                                        {
                                            text: "a",
                                        },
                                        {
                                            text: "b",
                                        },
                                        {
                                            text: "c",
                                        },
                                        {
                                            text: "d",
                                        },
                                        {
                                            text: "e",
                                        },
                                        {
                                            text: "f",
                                        },
                                    ],
                                },
                                {
                                    id: md5(guid()),
                                    date: 1766040807,
                                    subject: 'subject',
                                    body: '## Ilorm ipsum....\n\ntoday',
                                    color: "danger",
                                    tags: [
                                        "Violet"
                                    ],
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
                                    date: 1766040807,
                                    subject: 'subject',
                                    body: '## IlQ\n\n1\n2\n3\n4\n\n5\n6\n7',
                                    color: "purple",
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
                                    ],
                                    tags: [
                                        "tag1",
                                        "tag2",
                                        "tag3",
                                        "tag4",
                                        "tag5",
                                        "tag6",
                                        "tag7",
                                        "tag8",
                                    ],
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
            }).
            fail(FAIL).
            fail(loadingDone);
        }).
        fail(FAILPAGE);
    },

    route: function () {
        subTop();

        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

        let rtd = '';

        rtd += '<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1"><select id="mkbDesks" class="form-control select-arrow right-top-select top-input"></select></div></div></form>';

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
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-success text-bold addDesk">${i18n("mkb.addDesk")}</span></li>
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-primary text-bold addColumn">${i18n("mkb.addColumn")}</span></li>
            `);
        }

        modules.mkb.renderDesk();
    },
/*
    search: function (search) {
        console.log(search);
    }
*/
}).init();