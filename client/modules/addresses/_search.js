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
                    QUERY("subscribers", "searchFlat", {
                        search: search,
                    }, true).
                    done(fs => {
                        QUERY("subscribers", "searchRf", {
                            search: search,
                        }, true).
                        done(rs => {
                            modules.addresses._search.searchResults = {
                                as: [],
                                hs: [],
                                ss: [],
                                fs: [],
                                rs: [],
                            }

                            let h = '';

                            if (as && as.addresses && as.addresses.length) {
                            }

                            if (hs && hs.houses && hs.houses.length) {
                                modules.addresses._search.searchResults.hs = hs.houses;
                                h += `<h5 class="mt-3 ml-2">${i18n('addresses.housesFound')}</h5>`;
                                h += '<ul class="list-unstyled">';
                                for (let i in hs.houses) {
                                    h += '<li class="mt-2">';
                                    h += '<i class="fas fa-fw fa-city mr-2 ml-3"></i>';
                                    h += `<a href='?#addresses.houses&houseId=${hs.houses[i].houseId}'>${hs.houses[i].houseFull}</a>    `;
                                    h += '</li>';
                                }
                                h += '</ul>';
                            }

                            if (fs && fs.flats && fs.flats.length) {
                                modules.addresses._search.searchResults.fs = fs.flats;
                                h += `<h5 class="mt-3 ml-2">${i18n('addresses.flatsFound')}</h5>`;
                                h += '<ul class="list-unstyled">';
                                for (let i in fs.flats) {
                                    h += '<li class="mt-2">';
                                    h += '<i class="fas fa-fw fa-home mr-2 ml-3"></i>';
                                    h += `<a href='"?#addresses.subscribers&flatId=${fs.flats[i].flatId}&houseId=${fs.flats[i].house.houseId}&flat=${encodeURIComponent(fs.flats[i].flat)}&settlementId=${fs.flats[i].house.settlementId ? fs.flats[i].house.settlementId : 0}&streetId=${fs.flats[i].house.streetId ? fs.flats[i].house.streetId : 0}'>${fs.flats[i].house.houseFull}, ${fs.flats[i].flat}</a>`;
                                    h += '</li>';
                                }
                                h += '</ul>';
                            }

                            if (ss && ss.subscribers && ss.subscribers.length) {
                                modules.addresses._search.searchResults.ss = ss.subscribers;
                                h += `<h5 class="mt-3 ml-2">${i18n('addresses.subscribersFound')}</h5>`;
                                h += '<ul class="list-unstyled">';
                                for (let i in ss.subscribers) {
                                    h += '<li class="mt-2">';
                                    if (ss.subscribers[i].mobile == search) {
                                        h += "<i class='fas fa-fw fa-mobile-alt mr-2 ml-3'></i>";
                                    } else {
                                        h += "<i class='fas fa-fw fa-user mr-2 ml-3'></i>";
                                    }

                                    h += `<a href="javascript:void(0)" class="ss" data-subscriber-id="${i}">${ss.subscribers[i].subscriberFull}</a><br />`;

                                    for (let j in ss.subscribers[i].flats) {
                                        h += '<div class="mt-1">';
                                        h += '<i class="fas fa-fw fa-home mr-2 ml-4"></i>';
                                        h += `<a href='"?#addresses.subscribers&flatId=${ss.subscribers[i].flats[j].flatId}&houseId=${ss.subscribers[i].flats[j].house.houseId}&flat=${encodeURIComponent(ss.subscribers[i].flats[j].flat)}&settlementId=${ss.subscribers[i].flats[j].house.settlementId ? ss.subscribers[i].flats[j].house.settlementId : 0}&streetId=${ss.subscribers[i].flats[j].house.streetId ? ss.subscribers[i].flats[j].house.streetId : 0}'>${ss.subscribers[i].flats[j].house.houseFull}, ${ss.subscribers[i].flats[j].flat}</a><br />`;
                                        h += '</div>';
                                    }

                                    h += `</li>`;
                                }
                                h += '</ul>';
                            }

                            if (rs && rs.rfs && rs.rfs.length) {
                                modules.addresses._search.searchResults.rs = rs.rfs;
                                h += `<h5 class="mt-3 ml-2">${i18n('addresses.rfsFound')}</h5>`;
                                h += '<ul class="list-unstyled">';
                                for (let i in rs.rfs) {
                                    console.log(rs.rfs[i]);
                                    h += '<li class="mt-2">';
                                    /*
                                        type 0 (any)
                                        type 1 (subscriber)
                                        type 2 (flat)
                                        type 3 (entrance)
                                        type 4 (house)
                                        type 5 (company)
                                    */
                                    switch (parseInt(rs.rfs[i].accessType)) {
                                        case 0:
                                            h += i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s"));
                                            h += "<i class='fab fa-fw fa-keycdn mr-2 ml-3'></i>";
                                            h += "<br />";
                                            break;

                                        case 1:
                                            h += "<i class='fas fa-fw fa-user mr-2 ml-3'></i>";
                                            h += i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s"));

                                            break;

                                        case 2:
                                            h += '<i class="fas fa-fw fa-home mr-2 ml-3"></i>';
                                            h += i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s"));

                                            break;

                                        case 3:
                                            h += '<i class="fas fa-fw fa-door-closed mr-2 ml-3"></i>';
                                            h += i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s"));

                                            break;

                                        case 4:
                                            h += '<i class="fas fa-fw fa-key mr-2 ml-3"></i>';
                                            h += rs.rfs[i].rfId;
                                            h += " (" + i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s")) + ")";

                                            h += '<div class="mt-1">';
                                            h += '<i class="fas fa-fw fa-city mr-2 ml-4"></i>';
                                            h += `<a href='?#addresses.houses&houseId=${rs.rfs[i].house.houseId}'>${rs.rfs[i].house.houseFull}</a>    `;
                                            h += '</div>';

                                            break;

                                        case 5:
                                            h += '<i class="far fa-fw fa-building nav-icon"></i>';
                                            h += i18n("addresses.keysKeyType", i18n("addresses.keysType" + rs.rfs[i].accessType + "s"));

                                            break;
                                    }
                                    h += '</li>';
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
                        }).
                        fail(FAILPAGE);
                    }).
                    fail(FAILPAGE);
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