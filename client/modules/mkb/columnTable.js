({
    // how many PAGER buttons to show
    defaultPagerItemsCount: 10,
    defaulCardsPerPage: 50,

    init: function () {
        moduleLoaded("mkb.columnTable", this);
    },

    renderCards: function (params) {
        loadingStart();

        let skip = parseInt(params.skip ? params.skip : 0);
        let limit = parseInt(params.limit ? params.limit : modules.tt.defaultIssuesPerPage);

        function pager(count) {
            let h = '';

            let page = Math.floor(skip / limit) + 1;
            let pages = Math.ceil(count / limit);
            let delta = Math.floor(modules.mkb.columnTable.defaultPagerItemsCount / 2);

            let first, last;

            if (pages <= modules.mkb.columnTable.defaultPagerItemsCount) {
                first = 1;
                last = pages;
            } else {
                if (page <= delta) {
                    first = 1;
                    last = modules.mkb.columnTable.defaultPagerItemsCount;
                } else {
                    first = page - delta + 1;
                    last = first + modules.mkb.columnTable.defaultPagerItemsCount - 1;
                    if (last > pages) {
                        last = pages;
                        first = last - modules.mkb.columnTable.defaultPagerItemsCount + 1;
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

        POST("mkb", "cards", false, { query: { $text: { $search: params.search } }, skip, limit }).
        done(r => {
            h += `
                <table class="mt-2" style="width: 100%;"><tr><td style="width: 100%;">?</td><td>${pager(r.count)}</td></tr></table>
                <div id="cards"></div>
                <table class="mt-2 cardsBottomPager" style="width: 100%;"><tr><td style="width: 100%;">?</td><td>${pager(r.count)}</td></tr></table>
            `;

            $("#mainForm").html(h);

            cardTable({
                target: "#cards",
                title: {
                    caption: i18n("mkb.cards"),
                },
                edit: modules.mkb.modifyCard,
                columns: [
                    {
                        title: i18n("mkb.date"),
                    },
                    {
                        title: i18n("mkb.subject"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in r.cards) {
                        rows.push({
                            uid: r.cards[i]._id,
                            cols: [
                                {
                                    data: date("Y-m-d", r.cards[i].date),
                                    nowrap: true,
                                },
                                {
                                    data: r.cards[i].subject,
                                },
                            ],
                        });
                    }

                    return rows;
                },
            });

            $(".mkbPager").off("click").on("click", function () {
                params.skip = Math.max(0, (parseInt($(this).attr("data-page")) - 1) * limit);
                params.limit = limit;
                modules.mkb.columnTable.renderCards(params);
            });

            if ($("#cards").height() > $(window).height()) {
                $(".cardsBottomPager").show();
            }

            loadingDone();
        }).
        fail(FAILPAGE);
    },

    route: function (params) {
        subTop();

        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

        modules.users.loadUsers(() => {
            modules.mkb.columnTable.renderCards(params);
        });
    },

    search: function (search) {
        navigateUrl("mkb.columnTable", { search }, { run: true });
    },
}).init();
