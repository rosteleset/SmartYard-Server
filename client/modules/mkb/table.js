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

        let query;
        let title;

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

        GET("mkb", "desks", false, true).
        done(d => {
            console.log(d);

            POST("mkb", "cards", false, { query, skip, limit }).
            done(r => {
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
                            title: i18n("mkb.desk"),
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
                                        data: parseInt(i) + skip + 1,
                                    },
                                    {
                                        data: date("Y-m-d", r.cards[i].date),
                                        nowrap: true,
                                    },
                                    {
                                        data: r.cards[i].desk ? r.cards[i].desk : i18n("mkb.archived"),
                                        nowrap: true,
                                    },
                                    {
                                        data: progress,
                                        nowrap: true,
                                    },
                                    {
                                        data: r.cards[i].subject,
                                        ellipses: true,
                                    },
                                ],
                                dropDown: {
                                    items: [
                                        {
                                            icon: "fas fa-comments",
                                            title: i18n("mkb.comments"),
                                            click: id => {
                                                modules.mkb.cardComments(id);
                                            },
                                        },
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
                                            icon: "fas fa-eye",
                                            title: i18n("addresses.watchers"),
                                            click: cardId => {
                                            },
                                        },
                                        {
                                            title: "-",
                                            hint: "123",
                                        },
                                        {
                                            icon: "fas fa-mobile-alt",
                                            class: "sipIdle",
                                            title: i18n("addresses.mobileCall"),
                                            click: cardId => {
                                            },
                                        },
                                        {
                                            title: "-",
                                        },
                                        {
                                            icon: "fas fa-home",
                                            class: "sipIdle",
                                            title: i18n("addresses.flatCall"),
                                            click: cardId => {
                                            },
                                        },
                                    ],
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

        modules.users.loadUsers(() => {
            modules.mkb.table.renderCards(params);
        });
    },

    search: function (search) {
        navigateUrl("mkb.table", { search }, { run: true });
    },
}).init();
