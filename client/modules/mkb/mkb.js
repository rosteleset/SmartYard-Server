({
    menuItem: false,
    md: false,
    c: false,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("mkb")) {
                this.menuItem = leftSide("fas fa-fw fa-layer-group", i18n("mkb.mkb"), "?#mkb", "productivity");
            }

        }
        moduleLoaded("mkb", this);
    },

    allLoaded: function () {
        modules.mkb.md = new remarkable.Remarkable({
            html: true,
            quotes: '“”‘’',

            highlight: function (str, language) {
                if (language && hljs.getLanguage(language)) {
                    try {
                        let h = hljs.highlight(str, { language }).value;
                        return h;
                    } catch (err) {
                        console.log(err);
                    }
                }

                try {
                    return hljs.highlightAuto(str).value;
                } catch (err) {
                    console.log(err);
                }

                return ''; // use external default escaping
            }
        });

        modules.mkb.md.core.ruler.enable([
            'abbr'
        ]);

        modules.mkb.md.block.ruler.enable([
            'footnote',
            'deflist'
        ]);

        modules.mkb.md.inline.ruler.enable([
            'footnote_inline',
            'ins',
            'mark',
            'sub',
            'sup'
        ]);
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("mkb.mkb");

        if (modules.mkb.menuItem) {
            $("#" + modules.mkb.menuItem).children().first().attr("href", navigateUrl("mkb"));
        }

        if (parseInt(myself.uid) && AVAIL("mkb")) {
            $("#leftTopDynamic").html(`
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-success text-bold addDeck">${i18n("mkb.addDeck")}</span></li>
                <li class="nav-item d-none d-sm-inline-block"><span class="hoverable pointer nav-link text-primary text-bold addColumn">${i18n("mkb.addColumn")}</span></li>
            `);
        }

        $(".createDeck").off("click").on("click", () => {
            modules.notes.createDeck();
        });

        let h = `
            <div class="content-wrapper kanban pt-3" style="margin-left: 0px!important; margin-top: 0px!important;">
                <section class="content pb-3 pl-0 pr-0">
                    <div id="desk" class="h-100 kanban-desk" style="display: flex;">
                        <div class="card card-row card-secondary kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                    Backlog
                                </h3>
                                <div class="card-tools">
                                    <span class="btn btn-tool"><i class="far fa-fw fa-clipboard"></i></span>
                                    <span class="btn btn-tool"><i class="fas fa-fw fa-plus-circle"></i></span>
                                    <span class="btn btn-tool"><i class="fas fa-fw fa-edit"></i></span>
                                </div>
                            </div>
                            <div id="backlog" style="min-height: 100%;">
                                <div class="card-body card-no-scroll">
                                    <div class="card card-info card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">
                                                <span class="btn btn-tool btn-checkbox pl-0" data-checked="0"><i class="far fa-circle"></i></span>
                                                <span class="btn btn-tool">#1</span>
                                                <span class="btn btn-tool text-danger">5дн</span>
                                            </h5>
                                            <div class="card-tools">
                                                <span class="dropdown card-calendar">
                                                    <span class="btn btn-tool text-info dropdown-toggle dropdown-toggle-no-icon pb-0" data-toggle="dropdown" aria-expanded="false" data-flip="true" style="margin-bottom: -8px;">
                                                        <i class="far fa-fw fa-calendar-alt"></i>
                                                        <ul class="dropdown-menu">
                                                            <li id="dropdown-calendar"></li>
                                                        </ul>
                                                    </span>
                                                </span>
                                                <span class="btn btn-tool text-primary"><i class="fas fa-fw fa-link"></i></span>
                                                <span class="btn btn-tool"><i class="fas fa-fw fa-edit"></i></span>
                                                <span class="btn btn-tool btn-min-max"><i class="fas fa-fw fa-minus"></i></span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-bold">Subject</div>
                                            <hr class="min-max" />
                                            <div class="subtasks pb-2 min-max">
                                                <div>
                                                    <span id="customCheckbox1" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="0"><i class="far fa-circle"></i></span>
                                                    <span data-for="customCheckbox1" class="btn-checkbox-label text-no-bold">Bug</span>
                                                </div>
                                                <div>
                                                    <span id="customCheckbox2" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="0"><i class="far fa-circle"></i></span>
                                                    <span data-for="customCheckbox2" class="btn-checkbox-label text-no-bold">Feature</span>
                                                </div>
                                                <div>
                                                    <span id="customCheckbox3" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="0"><i class="far fa-circle"></i></span>
                                                    <span data-for="customCheckbox3" class="btn-checkbox-label text-no-bold">Enhancement</span>
                                                </div>
                                                <div>
                                                    <span id="customCheckbox4" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="0"><i class="far fa-circle"></i></span>
                                                    <span data-for="customCheckbox4" class="btn-checkbox-label text-no-bold">Documentation</span>
                                                </div>
                                                <div>
                                                    <span id="customCheckbox5" class="pl-0 pr-1 btn btn-tool btn-checkbox" data-checked="0"><i class="far fa-circle"></i></span>
                                                    <span data-for="customCheckbox5" class="btn-checkbox-label text-no-bold">Examples</span>
                                                </div>
                                            </div>
                                            <div class="pointer subtasks-progress pt-1 pb-1 min-max">
                                                <div class="progress">
                                                    <div class="progress-bar progress-bar-danger progress-bar-striped" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                                                </div>
                                            </div>
                                            <div class="pt-3 pb-1 min-max hidden">
                                                <div class="progress">
                                                    <div class="progress-bar progress-bar-danger progress-bar-striped" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                                                </div>
                                            </div>
                                            <hr class="min-max" />
                                            <div class="min-max">
                                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
                                                Aenean commodo ligula eget dolor. Aenean massa.
                                                Cum sociis natoque penatibus et magnis dis parturient montes,
                                                nascetur ridiculus mus.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-row card-primary kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                To Do
                                </h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                            <div id="todo" style="min-height: 100%;">
                                <div class="card-body">
                                    <div class="card card-primary card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">Create first milestone</h5>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-tool btn-link">#5</a>
                                                <a href="#" class="btn btn-tool">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-row card-lime kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                In Progress
                                </h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                            <div id="inprogress" style="min-height: 100%;">
                                <div class="card-body">
                                    <div class="card card-red card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">Update Readme</h5>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-tool btn-link">#2</a>
                                                <a href="#" class="btn btn-tool">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">
                                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
                                                Aenean commodo ligula eget dolor. Aenean massa.
                                                Cum sociis natoque penatibus et magnis dis parturient montes,
                                                nascetur ridiculus mus.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-row card-success kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                Done
                                </h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                            <div id="done1" style="min-height: 100%;">
                                <div class="card-body">
                                    <div class="card card-primary card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">Create repo</h5>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-tool btn-link">#1</a>
                                                <a href="#" class="btn btn-tool">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-row card-success kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                Done
                                </h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                            <div id="done2" style="min-height: 100%;">
                                <div class="card-body card-no-scroll">
                                    <div class="card card-primary card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">Create repo</h5>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-tool btn-link">#1</a>
                                                <a href="#" class="btn btn-tool">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        `;

        $("#mainForm").html(h);

        new Sortable(document.getElementById("backlog"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("todo"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("inprogress"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("done1"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("done2"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("desk"), {
            "handle": ".col-handle",
            "animation": 150,
        });

        $(".subtasks-progress").off("click").on("click", () => {
            $(".subtasks").toggle();
        });

        $(".btn-checkbox").off("click").on("click", function () {
            let btn = $(this);
            if (btn.attr("data-checked") == "1") {
                btn.attr("data-checked", "0").removeClass("text-success").children().first().addClass("far fa-circle").removeClass("fas fa-check-circle");
            } else {
                btn.attr("data-checked", "1").addClass("text-success").children().first().removeClass("far fa-circle").addClass("fas fa-check-circle");
            }
        });

        $(".btn-checkbox-label").off("click").on("click", function () {
            $("#" + $(this).attr("data-for")).trigger("click");
        });

        $("#dropdown-calendar").off("click").on("click", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("dragstart").on("dragstart", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("pointerdown").on("pointerdown", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("mousedown").on("mousedown", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("touchstart").on("touchstart", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("dragover").on("dragover", e => {
            e.stopPropagation();
        });

        $("#dropdown-calendar").off("dragenter").on("dragenter", e => {
            e.stopPropagation();
        });

        $(".card-calendar").off("show.bs.dropdown").on("show.bs.dropdown", function () {
            let i = md5(guid());

            $("#dropdown-calendar").html(`<span id='c${i}'></span>`);

            modules.mkb.c = new VanillaCalendarPro.Calendar("#c" + i, {
                locale: 'ru',
                selectedMonth: 6,
                selectedYear: 2024,
                selectedDates: [ '2024-07-22' ],
                selectionTimeMode: 24,
                selectedTime: '12:15',
            });

            modules.mkb.c.init();

            // modules.mkb.c.context.selectedTime
            // modules.mkb.c.context.selectedDates
        });

        $(".btn-min-max").off("click").on("click", function () {
            if ($(".btn-min-max").children().first().hasClass("fa-minus")) {
                $(".btn-min-max").children().first().removeClass("fa-minus").addClass("fa-plus");
            } else {
                $(".btn-min-max").children().first().addClass("fa-minus").removeClass("fa-plus");
            }
            $(".min-max").toggle();
        });

        loadingDone();
    },

/*
    search: function (search) {
        console.log(search);
    }
*/
}).init();