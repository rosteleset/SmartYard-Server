({
    init: function () {
        moduleLoaded("addresses._search", this);
    },

    search: function (search) {
        window.location = navigateUrl("addresses._search", {
            search: search,
        })
    },

    renderSearch: function (search) {
        console.log(search);

        $("#searchInput").val(search);

        QUERY("addresses", "search", {
            search: search,
        }, true).
        done(as => {
            QUERY("houses", "search", {
                search: search,
            }, true).
            done(hs => {
                QUERY("subscribers", "search", {
                    search: search,
                }, true).
                done(ss => {
                    console.log(as);
                    console.log(hs);
                    console.log(ss);

                    let h = '';

                    if (as && as.addresses && as.addresses.length) {
                    }

                    if (hs && hs.houses && hs.houses.length) {
                        h += `<h5 class="mt-3 ml-2">${i18n('addresses.housesFound')}</h5>`;
                        h += '<ul class="list-unstyled">';
                        for (let i in hs.houses) {
                            h += `<li><i class='fas fa-fw fa-home mr-2 ml-3'></i><a href='?#addresses.houses&houseId=${hs.houses[i].houseId}'>${hs.houses[i].houseFull}</a> (${hs.houses[i].similarity})</li>`;
                        }
                        h += '</ul>';
                    }

                    if (ss && ss.subscribers && ss.subscribers.length) {
                    }

                    if (h) {
                        $("#mainForm").html(h);
                    } else {
                        $("#mainForm").html(`<h5 class="mt-3 ml-2">${i18n('addresses.notFound')}</h5>`);
                    }

                    loadingDone();
                }).fail(FAILPAGE);
            }).fail(FAILPAGE);
        }).fail(FAILPAGE);
    },

    route: function (params) {
        $("#altForm").hide();
        subTop();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.search", params.search);

        modules.addresses._search.renderSearch(params.search);
    },
}).init();