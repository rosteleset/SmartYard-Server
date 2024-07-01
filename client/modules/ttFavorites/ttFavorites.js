({
    init: function () {
        if (parseInt(myself.uid) && AVAIL("tt", "favoriteFilter")) {
            GET("tt", "tt", false, true).
            done(modules.tt.tt).
            done(() => {
                for (let i in modules.tt.meta.favoriteFilters) {
                    if (parseInt(modules.tt.meta.favoriteFilters[i].rightSide)) {
                        let title = '';
                        $("#leftside-menu").append(`
                            <li class="nav-item" title="${escapeHTML(title)}">
                                <a href="?#tt&filter=${modules.tt.meta.favoriteFilters[i].filter}" class="nav-link">
                                    <i class="nav-icon fa fa-fw ${modules.tt.meta.favoriteFilters[i].icon}"></i>
                                    <p class="text-nowrap">${title}</p>
                                </a>
                            </li>
                        `);
                    }
                }
            }).
            fail(FAIL);
        }
        moduleLoaded("ttFavorites", this);
    },
}).init();