({
    // how many PAGER buttons to show
    defaultPagerItemsCount: 10,
    defaulCardsPerPage: 50,

    init: function () {
        moduleLoaded("mkb.table", this);
    },

    renderCards: function (params) {
        loadingStart();

        let skip = parseInt(params.skip ? params.skip : 0);
        let limit = parseInt(params.limit ? params.limit : modules.tt.defaultIssuesPerPage);

        function pager(count) {
            let h = '';

            let page = Math.floor(skip / limit) + 1;
            let pages = Math.ceil(count / limit);
            let delta = Math.floor(modules.mkb.table.defaultPagerItemsCount / 2);

            let first, last;

            if (pages <= modules.mkb.table.defaultPagerItemsCount) {
                first = 1;
                last = pages;
            } else {
                if (page <= delta) {
                    first = 1;
                    last = modules.mkb.table.defaultPagerItemsCount;
                } else {
                    first = page - delta + 1;
                    last = first + modules.mkb.table.defaultPagerItemsCount - 1;
                    if (last > pages) {
                        last = pages;
                        first = last - modules.mkb.table.defaultPagerItemsCount + 1;
                    }
                }
            }

            h += `<nav class="pager">`;
            h += '<ul class="pagination mb-0 ml-0" style="margin-right: -2px !important;">';

            if (first > 1) {
                h += `<li class="page-item pointer mkbPager" data-page="1" ><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
            } else {
                h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
            }

            for (let i = first; i <= last; i++) {
                if (page == i) {
                    h += `<li class="page-item font-weight-bold disabled" data-page="${i}"><span class="page-link">${i}</span></li>`;
                } else {
                    h += `<li class="page-item pointer mkbPager" data-page="${i}"><span class="page-link">${i}</span></li>`;
                }
            }

            if (last < pages) {
                h += `<li class="page-item pointer mkbPager" data-page="${pages}"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
            } else {
                h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
            }

            h += '</ul>';
            h += '</nav>';

            return h;
        }

        let h = '';

        GET("mkb", "desks", false, true).
        done(d => {
            let query;
            let title;
            let columns = {};

            modules.mkb.desks = [];

            if (d && d.desks) {
                modules.mkb.desks = d.desks;
            }

            let dropDownItems = [
                {
                    icon: "fas fa-edit",
                    title: i18n("mkb.edit"),
                    click: id => {
                        modules.mkb.cardEdit(id, () => {
                            modules.mkb.table.renderCards(params);
                        });
                    },
                },
                {
                    icon: "fas fa-comments",
                    title: i18n("mkb.comments"),
                    click: id => {
                        modules.mkb.cardComments(id);
                    },
                },
                {
                    icon: "fas fa-share-from-square",
                    title: i18n("mkb.send"),
                    click: id => {
                        modules.mkb.cardSend(id);
                    },
                },
                {
                    title: "-",
                    hint: i18n("mkb.moveTo"),
                },
                {
                    icon: "fas fa-archive",
                    title: i18n("mkb.cardsArchive"),
                    click: id => {
                        modules.mkb.cardArchive(id, () => {
                            modules.mkb.table.renderCards(params);
                        });
                    },
                },
            ];

            let h = '';

            for (let i in d.desks) {
                dropDownItems.push({
                    icon: "fas fa-layer-group",
                    title: d.desks[i].name,
                    click: id => {
                        modules.mkb.cardMove(id, d.desks[i].name, () => {
                            modules.mkb.table.renderCards(params);
                        });
                    },
                });
                if (d.desks[i].columns && d.desks[i].columns.length) {
                    for (let j in d.desks[i].columns) {
                        columns[d.desks[i].columns[j]._id] = d.desks[i].columns[j].title;
                    }
                }
                if (d.desks[i].name == params.desk) {
                    h += '<option selected>' + escapeHTML(d.desks[i].name) + '</option>';
                } else {
                    h += '<option>' + escapeHTML(d.desks[i].name) + '</option>';
                }
            }

            $("#mkbDesks").html(h);

            h = '';

            if (params.search) {
                query = {
                    $text: {
                        $search: params.search
                    }
                };
                title = i18n("mkb.searchResults");
            }

            if (params.all) {
                query = {};
                title = i18n("mkb.cardsAll");
            }

            if (params.archive) {
                query = {
                    "$and": [
                        {
                            "$or": [
                                {
                                    desk: false,
                                },
                                {
                                    desk: {
                                        "$exists": false
                                    },
                                },
                            ],
                        },
                        {
                            "$or": [
                                {
                                    inbox: false,
                                },
                                {
                                    inbox: {
                                        "$exists": false
                                    },
                                },
                            ],
                        },
                    ],
                };
                title = i18n("mkb.archived");
            }

            if (params.inbox) {
                query = {
                    inbox: true,
                };
                title = i18n("mkb.cardsInbox");
            }

            if (params.desk) {
                query = {
                    desk: params.desk,
                };
                title = params.desk;
            }

            QUERY("mkb", "cards", { query, skip, limit, sort: { date: 1 } }, true).
            done(r => {
                let cl = [];


                for (let i in r.cards) {
                    let c = false;
                    for (let j in d.desks) {
                        if (d.desks[j].columns && d.desks[j].columns.length) {
                            for (k in d.desks[j].columns) {
                                if (d.desks[j].columns[k].cards && d.desks[j].columns[k].cards.length && d.desks[j].columns[k].cards.indexOf(r.cards[i]._id) >= 0) {
                                    c = d.desks[j].columns[k].title;
                                    break;
                                }
                            }
                        }
                    }
                    if (c) {
                        cl[r.cards[i]._id] = c;
                    }
                }

                h += `
                    <table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;"><span class="text-bold">${title}</span><br /><span class="small">${i18n("mkb.showCounts", parseInt(skip) ? (parseInt(skip) + 1) : (r.count ? '1' : '0'), parseInt(skip) + r.cards.length, r.count)}</span></td><td>${pager(r.count)}</td></tr></table>
                    <div id="cards"></div>
                    <table class="cardsBottomPager mt-2" style="width: 100%; display: none;"><tr><td style="width: 100%;"><span class="text-bold">&nbsp;</span><br /><span class="small">&nbsp;</span></td><td>${pager(r.count)}</td></tr></table>
                `;

                $("#mainForm").html(h);

                cardTable({
                    target: "#cards",
                    edit: modules.mkb.modifyCard,
                    columns: [
                        {
                            title: "#",
                            class: "center",
                        },
                        {
                            title: i18n("mkb.date"),
                        },
                        {
                            title: i18n("mkb.done"),
                        },
                        {
                            title: i18n("mkb.desk"),
                        },
                        {
                            title: i18n("mkb.column"),
                        },
                        {
                            title: i18n("mkb.progress"),
                        },
                        {
                            title: i18n("mkb.subject"),
                            fullWidth: true,
                        },
                    ],
                    rows: () => {
                        let rows = [];

                        for (let i in r.cards) {
                            let progress = '-';

                            if (r.cards[i].subtasks && r.cards[i].subtasks.length) {
                                let p = 0;

                                for (let j in r.cards[i].subtasks) {
                                    if (r.cards[i].subtasks[j].checked) {
                                        p++;
                                    }
                                }

                                progress = (Math.round((p / r.cards[i].subtasks.length) * 1000) / 10) + "%";
                            }

                            modules.mkb.cards[r.cards[i]._id] = r.cards[i];

                            rows.push({
                                uid: r.cards[i]._id,
                                cols: [
                                    {
                                        data: `<span class="${"bg-" + r.cards[i].color} p-1 pl-2 pr-2 border-no-shadow">${parseInt(i) + skip + 1}</span>`,
                                        // data: parseInt(i) + skip + 1,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: date("Y-m-d", r.cards[i].date),
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: r.cards[i].done ? i18n("yes") : i18n("no"),
                                        class: "center",
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: r.cards[i].desk ? r.cards[i].desk : "-",
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: cl[r.cards[i]._id] ? cl[r.cards[i]._id] : "-",
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: progress,
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: convertLinks(DOMPurify.sanitize(r.cards[i].subject)),
                                        ellipses: true,
                                    },
                                ],
                                dropDown: {
                                    items: dropDownItems,
                                },
                            });
                        }

                        return rows;
                    },
                });

                $(".mkbPager").off("click").on("click", function () {
                    params.skip = Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit);
                    params.limit = limit;
                    modules.mkb.table.renderCards(params);
                });

                if ($("#cards").height() > $(window).height()) {
                    $(".cardsBottomPager").show();
                }

                modules.mkb.refreshInbox();

                loadingDone();
            }).
            fail(FAILPAGE);
        }).
        fail(FAILPAGE);
    },

    route: function (params) {
        subTop();

        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

        let rtd = '';

        if (params.desk) {
            rtd += `<form autocomplete="off"><div class="form-inline ml-3 mr-3"><div class="input-group input-group-sm mt-1" title="${i18n("mkb.desks")}"><select id="mkbDesks" class="form-control select-arrow right-top-select top-input"></select></div></div></form>`;

            $("#rightTopDynamic").html(rtd);
        }

        $("#rightTopDynamic").html(rtd);


        $("#mkbDesks").off("change").on("change", () => {
            lStore("mkbDesk", $("#mkbDesks").val());
            params.desk = $("#mkbDesks").val();
            modules.mkb.table.renderCards(params);
        });

        if (parseInt(myself.uid) && AVAIL("mkb")) {
            $("#leftTopDynamic").html(`
                <li class="nav-item d-none d-sm-inline-block pointer cardsInbox" title="${i18n("mkb.cardsInbox")}">
                    <span class="nav-link">
                        <i class="fas fa-fw fa-inbox text-secondary"></i>
                        <span class="badge badge-danger navbar-badge kanban-inbox-count" style="display: none; font-weight: 600; padding: 2px 4px 0px 3px;">0</span>
                    </span>
                </li>
                <li class="nav-item d-none d-sm-inline-block pointer cardsArchive" title="${i18n("mkb.cardsArchive")}">
                    <span class="nav-link">
                        <i class="fas fa-fw fa-archive text-secondary"></i>
                    </span>
                </li>
                <li class="nav-item d-none d-sm-inline-block pointer cardsAll" title="${i18n("mkb.cardsAll")}">
                    <span class="nav-link">
                        <i class="fas fa-fw fa-box-tissue text-secondary"></i>
                    </span>
                </li>
            `);
        }

        $(".cardsInbox").off("click").on("click", () => {
            navigateUrl("mkb.table", { inbox: true }, { run: true });
        });

        $(".cardsArchive").off("click").on("click", () => {
            navigateUrl("mkb.table", { archive: true }, { run: true });
        });

        $(".cardsAll").off("click").on("click", () => {
            navigateUrl("mkb.table", { all: true }, { run: true });
        });

        modules.users.loadUsers(() => {
            modules.mkb.table.renderCards(params);
        });
    },

    search: function (search) {
        navigateUrl("mkb.table", { search }, { run: true });
    },
}).init();
