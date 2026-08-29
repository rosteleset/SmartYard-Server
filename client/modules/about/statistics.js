({
    menuItem: false,

    init: function () {
        this.menuItem = leftSide("fas fa-fw fa-chart-bar", i18n("about.statistics"), "?#about.statistics", "about");
        if (modules.about.systemInfo && modules.about.systemInfo.menuItem) {
            $("#" + this.menuItem).after($("#" + modules.about.systemInfo.menuItem));
        } else {
            $("#" + this.menuItem).after($("#" + modules.about.menuItem));
        }
        moduleLoaded("about.statistics", this);
    },

    equalizeCards: function () {
        let $grid = $("#mainForm .about-statistics-grid");
        let $cards = $grid.children(".card");
        if (!$cards.length) {
            return;
        }

        $cards.css("min-height", "");

        // Equalize only when several cards share the same grid row.
        let columns = 0;
        let firstTop = $cards.first().position().top;
        $cards.each(function () {
            if (Math.abs($(this).position().top - firstTop) < 1) {
                columns++;
            }
        });
        if (columns < 2) {
            return;
        }

        let maxH = 0;
        $cards.each(function () {
            maxH = Math.max(maxH, $(this).outerHeight());
        });
        $cards.css("min-height", maxH + "px");
    },

    route: function () {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.statistics");

        if (modules.about.statistics.menuItem) {
            $("#" + modules.about.statistics.menuItem).children().first().attr("href", "?#about.statistics&_=" + Math.random());
        }

        GET("server", "statistics", false, true).
        done(r => {
            let s = r.statistics || {};

            let row = (label, value, bold) => `
                <div class="about-statistics-row">
                    <span class="about-statistics-label">${label}</span>
                    <span class="about-statistics-value${bold ? " text-bold" : ""}">${value ?? 0}</span>
                </div>
            `;

            let card = (color, title, body) => `
                <div class="card card-${color} card-outline">
                    <div class="card-header">
                        <h5 class="card-title text-bold">${title}</h5>
                    </div>
                    <div class="card-body py-2">
                        ${body}
                    </div>
                </div>
            `;

            let h = `
                <style>
                    body:has(#mainForm .about-statistics),
                    body:has(#mainForm .about-statistics) .wrapper,
                    .content-wrapper:has(#mainForm .about-statistics),
                    .content:has(#mainForm .about-statistics),
                    #mainForm:has(.about-statistics) {
                        min-width: 0 !important;
                        max-width: 100%;
                        overflow-x: clip;
                    }
                    .about-statistics {
                        container-type: inline-size;
                        container-name: about-statistics;
                        width: 100%;
                        max-width: 100%;
                        min-width: 0;
                        overflow-x: clip;
                        box-sizing: border-box;
                        padding: 0.5rem 0;
                    }
                    .about-statistics-grid {
                        display: grid;
                        gap: 0.5rem;
                        grid-template-columns: minmax(0, 1fr);
                        align-items: stretch;
                        width: 100%;
                        min-width: 0;
                        max-width: 100%;
                    }
                    @container about-statistics (min-width: 36rem) {
                        .about-statistics-grid {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }
                    }
                    @container about-statistics (min-width: 62rem) {
                        .about-statistics-grid {
                            grid-template-columns: repeat(4, minmax(0, 1fr));
                        }
                    }
                    .about-statistics-grid > .card {
                        height: 100%;
                        min-width: 0;
                        max-width: 100%;
                        margin-bottom: 0;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }
                    .about-statistics-grid > .card > .card-header,
                    .about-statistics-grid > .card > .card-body {
                        min-width: 0;
                        max-width: 100%;
                    }
                    .about-statistics-grid > .card > .card-body {
                        flex: 1 1 auto;
                    }
                    .about-statistics-grid .card-title {
                        min-width: 0;
                        overflow-wrap: anywhere;
                    }
                    .about-statistics-row {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto;
                        gap: 0.5rem;
                        align-items: baseline;
                        padding: 0.25rem 0;
                        min-width: 0;
                        max-width: 100%;
                    }
                    .about-statistics-label {
                        min-width: 0;
                        overflow-wrap: anywhere;
                    }
                    .about-statistics-value {
                        text-align: right;
                        white-space: nowrap;
                    }
                </style>
                <div class="about-statistics noselect">
                    <div class="about-statistics-grid">
                        ${card("info", i18n("about.flatsStats"), `
                            ${row(i18n("about.flatsTotal"), s.flats, true)}
                            ${row(i18n("about.flatsWithSubscribers"), s.flatsWithSubscribers)}
                            ${row(i18n("about.flatsWithoutSubscribers"), s.flatsWithoutSubscribers)}
                            ${row(i18n("about.blockedFlats"), s.blockedFlats)}
                        `)}
                        ${card("teal", i18n("about.equipment"), `
                            ${row(i18n("about.domophones"), s.domophones, true)}
                            ${row(i18n("about.cameras"), s.cameras, true)}
                            <hr class="my-2" />
                            ${row(i18n("about.domophonesDisabled"), s.domophonesDisabled)}
                            ${row(i18n("about.camerasDisabled"), s.camerasDisabled)}
                        `)}
                        ${card("navy", i18n("about.keys"), `
                            ${row(i18n("about.keysTotal"), s.keys, true)}
                            ${row(i18n("about.keysUniversal"), s.keysUniversal)}
                            ${row(i18n("about.keysSubscriber"), s.keysSubscriber)}
                            ${row(i18n("about.keysFlat"), s.keysFlat)}
                            ${row(i18n("about.keysEntrance"), s.keysEntrance)}
                            ${row(i18n("about.keysHouse"), s.keysHouse)}
                            ${row(i18n("about.keysCompany"), s.keysCompany)}
                        `)}
                        ${card("olive", i18n("about.devices"), `
                            ${row(i18n("about.devicesTotal"), s.devices, true)}
                            ${row(i18n("about.devicesAndroid"), s.devicesAndroid)}
                            ${row(i18n("about.devicesIos"), s.devicesIos)}
                            ${row(i18n("about.devicesWeb"), s.devicesWeb)}
                            ${row(i18n("about.devicesOther"), s.devicesOther)}
                            <hr class="my-2" />
                            ${row(i18n("about.devicesWithoutPush"), s.devicesWithoutPush)}
                            ${row(i18n("about.devicesWithoutFlats"), s.devicesWithoutFlats)}
                            ${row(i18n("about.devicesInactive", s.inactiveDeviceDays ?? 30), s.devicesInactive)}
                        `)}
                    </div>
                </div>
            `;

            $("#mainForm").html(h);
            modules.about.statistics.equalizeCards();
            $(window).off("resize.aboutStatistics").on("resize.aboutStatistics", modules.about.statistics.equalizeCards);
            loadingDone();
        }).
        fail(FAILPAGE);
    },
}).init();
