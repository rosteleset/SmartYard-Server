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
            h += '<ul class="pagination mb-0 ml-0" style="margin-right: -2px!important;">';

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
            let filter = false;
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
                        if (params.column == d.desks[i].columns[j]._id) {
                            if (d.desks[i].columns[j].cards && d.desks[i].columns[j].cards.length) {
                                filter = d.desks[i].columns[j].cards;
                            } else {
                                filter = [];
                            }
                        }
                        columns[d.desks[i].columns[j]._id] = d.desks[i].columns[j].title;
                    }
                }
            }

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
                    desk: false,
                };
                title = i18n("mkb.archived");
            }

            if (params.desk) {
                query = {
                    desk: params.table,
                };
                if (params.column) {
                    // title = params.desk + "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>" + columns[params.column];
                    title = columns[params.column];
                } else {
                    title = params.desk;
                }
            }

            QUERY("mkb", "cards", false, { query, skip, limit }).
            done(r => {
                let cl = [];


                for (let i in r.cards) {
                    let c = false;
                    for (let j in d.desks) {
                        if (d.desks[j].columns && d.desks[j].columns.length) {
                            if (d.desks[j].columns[j].cards && d.desks[j].columns[j].cards.length && d.desks[j].columns[j].cards.indexOf(r.cards[i]._id) >= 0) {
                                c = d.desks[j].columns[j].title;
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
                            if (filter && filter.indexOf(r.cards[i]._id) < 0) {
                                continue;
                            }

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
                                        data: parseInt(i) + skip + 1,
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
                                        nowrap: true,
                                        click: id => {
                                            modules.mkb.cardEdit(id, () => {
                                                modules.mkb.table.renderCards(params);
                                            });
                                        },
                                    },
                                    {
                                        data: r.cards[i].desk ? r.cards[i].desk : i18n("mkb.archived"),
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

        if (parseInt(myself.uid) && AVAIL("mkb")) {
            $("#leftTopDynamic").html(`
                <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 mkb" title="${i18n("mkb.mkb")}"><i class="fas fa-fw fa-layer-group text-primary"></i></li>
                <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 cardsArchive" title="${i18n("mkb.cardsArchive")}"><i class="fas fa-fw fa-archive text-secondary"></i></li>
                <li class="nav-item d-none d-sm-inline-block pointer pl-3 pr-3 cardsAll" title="${i18n("mkb.cardsAll")}"><i class="far fa-fw fa-list-alt text-secondary"></i></li>
            `);
        }

        $(".mkb").off("click").on("click", () => {
            navigateUrl("mkb", false, { run: true });
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
