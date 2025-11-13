({
    menuItem: false,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("mkb")) {
                this.menuItem = leftSide("fas fa-fw fa-tasks", i18n("mkb.mkb"), "?#mkb", "mkb");
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
            <div class="content-wrapper kanban mt-3" style="margin-left: 0px!important;">
                <section class="content pb-3 pl-0">
                    <div id="desk" class="h-100" style="display: flex;">
                        <div class="card card-row card-secondary kanban-col">
                            <div class="card-header col-handle">
                                <h3 class="card-title">
                                    Backlog
                                </h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-fw fa-plus-circle"></i>
                                    </a>
                                    <a href="#" class="btn btn-tool">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                            <div id="backlog" style="min-height: 100%;">
                                <div class="card-body">
                                    <div class="card card-info card-outline">
                                        <div class="card-header card-handle">
                                            <h5 class="card-title">Create Labels</h5>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-tool btn-link">#3</a>
                                                <a href="#" class="btn btn-tool">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="customCheckbox1">
                                                <label for="customCheckbox1" class="custom-control-label">Bug</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="customCheckbox2">
                                                <label for="customCheckbox2" class="custom-control-label">Feature</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="customCheckbox3">
                                                <label for="customCheckbox3" class="custom-control-label">Enhancement</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="customCheckbox4">
                                                <label for="customCheckbox4" class="custom-control-label">Documentation</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="customCheckbox5">
                                                <label for="customCheckbox5" class="custom-control-label">Examples</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-row card-primary">
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
                        <div class="card card-row card-lime">
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
                                            <p>
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
                        <div class="card card-row card-success">
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
                            <div id="done" style="min-height: 100%;">
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
                        <div class="card card-row card-success">
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
                            <div id="done" style="min-height: 100%;">
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

        new Sortable(document.getElementById("done"), {
            "handle": ".card-handle",
            "animation": 150,
            "group": "cols",
        });

        new Sortable(document.getElementById("desk"), {
            "handle": ".col-handle",
            "animation": 150,
        });

        loadingDone();
    },

/*
    search: function (search) {
        console.log(search);
    }
*/
}).init();