({
    menuItem: false,

    init: function () {
        this.menuItem = leftSide("fas fa-fw fa-tachometer-alt", i18n("about.systemInfo"), "?#about.systemInfo", "about");
        $("#" + this.menuItem).after($("#" + modules.about.menuItem));
        moduleLoaded("about.systemInfo", this);
    },

    route: function () {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("about.systemInfo");

        if (modules.about.systemInfo.menuItem) {
            $("#" + modules.about.systemInfo.menuItem).children().first().attr("href", "?#about.systemInfo&_=" + Math.random());
        }

        GET("server", "systemInfo", false, true).
        done(r => {
            let h = "", disk = "", network = "", processes = "";

            for (let i in r.systemInfo.disk) {
                let c = "";
                if (r.systemInfo.disk[i].Usage > "75%") {
                    c = "bg-warning";
                }
                if (r.systemInfo.disk[i].Usage > "90%") {
                    c = "bg-danger";
                }
                disk += `
                    <div class="rbt-acc-row" style="border: solid thin #dfdfdf; border-radius: 3px;">
                        <div class="pointer p-2 disk ${c}" data-disk="${i}" style="border-radius: 3px; position: relative;">${i}<br />${r.systemInfo.disk[i].Device}<div class="p-2 pt-35" style="position: absolute; right: 0px; top: 0px;">${r.systemInfo.disk[i].Usage}</div></div>
                        <div class="disk-details p-3" data-disk="${i}" style="display: none;">
                            <hr class="pt-0 mt-0" />
                            <div class="row">
                                <div class="col">${i18n("about.total")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.disk[i].Total}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.used")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.disk[i].Used}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.available")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.disk[i].Available}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.usage")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.disk[i].Usage}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            for (let i in r.systemInfo.network) {
                network += `
                    <div class="rbt-acc-row" style="border: solid thin #dfdfdf; border-radius: 3px;">
                        <div class="pointer p-2 network" data-network="${i}" style="border-radius: 3px;">${i}</div>
                        <div class="network-details p-3" data-network="${i}" style="display: none;">
                            <hr class="pt-0 mt-0" />
                            <div class="row">
                                <div class="col">${i18n("about.ip")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.network[i]["IP Address"]}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.mac")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.network[i]["MAC Address"]}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.rx")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.network[i]["RX Data"]}</div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">${i18n("about.tx")}</div>
                                <div class="col" style="text-align: right;">${r.systemInfo.network[i]["TX Data"]}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            n = 0;
            processes += "<table style='width: 100%'>";

            processes += `
                <tr>
                    <th class="p-2">${i18n("about.pid")}</th>
                    <th class="p-2">${i18n("about.cpu")}</th>
                    <th class="p-2">${i18n("about.memory")}</th>
                    <th class="p-2">${i18n("about.command")}</th>
                <tr/>
            `;

            for (let i in r.systemInfo.processes) {
                processes += `
                    <tr>
                        <td class="p-2">${r.systemInfo.processes[i].PID}</td>
                        <td class="p-2">${r.systemInfo.processes[i].CPU}</td>
                        <td class="p-2">${r.systemInfo.processes[i].Memory}</td>
                        <td class="p-2">${r.systemInfo.processes[i].Command}</td>
                    <tr/>
                `;
                n++;
                if (n > 25) break;
            }

            processes += "</table>";

            h += `
                <div class="container-fluid mt-2 noselect">
                    <div class="row">
                        <div class="col rbt-grid-col">
                            <div class="card card-info card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.systemInfo")}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">${i18n("about.hostname")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.basic.Hostname}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.os")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.basic.OS}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.php")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.basic["PHP Version"]}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.software")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.basic["Server Software"]}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.uptime")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.uptime}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col rbt-grid-col">
                            <div class="card card-teal card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.cpu")}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">${i18n("about.model")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.cpu.Model}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.cores")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.cpu["Cores"]}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.usage")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.cpu["CPU Usage"]}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col rbt-grid-col">
                            <div class="card card-olive card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.memory")}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">${i18n("about.total")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.memory.Total}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.used")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.memory.Used}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.available")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.memory.Available}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.usage")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.memory.Usage}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col rbt-grid-col">
                            <div class="card card-indigo card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.load")}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">${i18n("about.1min")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.load["1min"]}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.5min")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.load["5min"]}</div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col">${i18n("about.15min")}</div>
                                        <div class="col" style="text-align: right;">${r.systemInfo.load["15min"]}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col rbt-grid-col">
                            <div class="card card-lime card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.disk")}</h5>
                                </div>
                                <div class="card-body">
                                    ${disk}
                                </div>
                            </div>
                        </div>
                        <div class="col rbt-grid-col">
                            <div class="card card-fuchsia card-outline">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.network")}</h5>
                                </div>
                                <div class="card-body">
                                    ${network}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col rbt-grid-col">
                            <div class="card card-navy card-outline mb-2">
                                <div class="card-header">
                                    <h5 class="card-title text-bold">${i18n("about.processes")}</h5>
                                </div>
                                <div class="card-body">
                                    ${processes}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $("#mainForm").html(h);

            $(".disk").off("click").on("click", function () {
                let h = $(this).hasClass("text-bold");
                let d = $(this).attr("data-disk");

                $(".disk").removeClass("text-bold");
                $(".disk-details").hide();

                if (!h) {
                    $(`.disk[data-disk="${d}"]`).addClass("text-bold");
                    $(`.disk-details[data-disk="${d}"]`).show();
                }
            });

            $(".network").off("click").on("click", function () {
                let h = $(this).hasClass("text-bold");
                let d = $(this).attr("data-network");

                $(".network").removeClass("text-bold");
                $(".network-details").hide();

                if (!h) {
                    $(`.network[data-network="${d}"]`).addClass("text-bold");
                    $(`.network-details[data-network="${d}"]`).show();
                }
            });

            loadingDone();
        }).
        fail(FAILPAGE);
    },
}).init();