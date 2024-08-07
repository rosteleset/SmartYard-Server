({
    searchResults: {},

    init: function () {
        moduleLoaded("addresses._search", this);
    },

    search: function (search) {
        window.location = navigateUrl("addresses._search", {
            search: search,
        })
    },

/*
<div class="list-group">
    <div class="list-group-item">
        <div class="row">
            <div class="col px-4">
                <div>
                    <div class="float-right">2021-04-20 04:04pm</div>
                    <h3>Lorem ipsum dolor sit amet</h3>
                    <p class="mb-0">consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="list-group-item">
        <div class="row">
            <div class="col-auto">
                <img class="img-fluid" src="../../dist/img/photo1.png" alt="Photo" style="max-height: 160px;">
            </div>
            <div class="col px-4">
                <div>
                    <div class="float-right">2021-04-20 10:14pm</div>
                    <h3>Lorem ipsum dolor sit amet</h3>
                    <p class="mb-0">consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="list-group-item">
        <div class="row">
            <div class="col-auto">
                <iframe width="240" height="160" src="https://www.youtube.com/embed/WEkSYw3o5is?controls=0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" class="border-0" allowfullscreen=""></iframe>
            </div>
            <div class="col px-4">
                <div>
                    <div class="float-right">2021-04-20 11:54pm</div>
                    <h3>Lorem ipsum dolor sit amet</h3>
                    <p class="mb-0">consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
                </div>
            </div>
        </div>
    </div>
</div>
*/

    renderSearch: function (search) {
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
                    modules.addresses._search.searchResults = {
                        as: [],
                        hs: [],
                        ss: [],
                    }

                    let h = '';

                    if (as && as.addresses && as.addresses.length) {
                    }

                    if (hs && hs.houses && hs.houses.length) {
                        modules.addresses._search.searchResults.hs = hs.houses;
                        h += `<h5 class="mt-3 ml-2">${i18n('addresses.housesFound')}</h5>`;
                        h += '<ul class="list-unstyled">';
                        for (let i in hs.houses) {
                            h += `<li><i class='fas fa-fw fa-home mr-2 ml-3'></i><a href='?#addresses.houses&houseId=${hs.houses[i].houseId}'>${hs.houses[i].houseFull}</a> (${hs.houses[i].similarity})</li>`;
                        }
                        h += '</ul>';
                    }

                    if (ss && ss.subscribers && ss.subscribers.length) {
                        modules.addresses._search.searchResults.ss = ss.subscribers;
                        h += `<h5 class="mt-3 ml-2">${i18n('addresses.subscribersFound')}</h5>`;
                        h += '<ul class="list-unstyled">';
                        for (let i in ss.subscribers) {
                            h += '<li>';
                            if (ss.subscribers[i].id == search) {
                                h += "<i class='fas fa-fw fa-mobile-alt mr-2 ml-3'></i>";
                            } else {
                                h += "<i class='far fa-fw fa-user mr-2 ml-3'></i>";
                            }
                            h += `<a href="javascript:void(0)" class="ss" data-subscriber-id="${i}">${ss.subscribers[i].subscriberFull}</a> (${ss.subscribers[i].similarity})<br />`;

                            for (let j in ss.subscribers[i].flats) {
                                h += `<span class="ml-4 mt-1"><a href='?#addresses.houses&houseId=${ss.subscribers[i].flats[j].houseId}'>${ss.subscribers[i].flats[j].house.houseFull}, ${ss.subscribers[i].flats[j].flat}</a></span><br />`;
                            }

                            h += `</li>`;
                        }
                        h += '</ul>';
                    }

                    if (h) {
                        $("#mainForm").html(h);
                    } else {
                        $("#mainForm").html(`<h5 class="mt-3 ml-2">${i18n('addresses.notFound')}</h5>`);
                    }

                    $(".ss").off("click").on("click", function () {
                        modules.addresses.subscribers.modifySubscriberLim(modules.addresses._search.searchResults.ss[$(this).attr("data-subscriber-id")]);
                    });

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