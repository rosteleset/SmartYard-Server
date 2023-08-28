({
    init() {
        moduleLoaded("task", this)

        if (AVAIL("task", "status", "GET")) {
            $(`
                        <li class="nav-item dropdown">
                            <a id="tasksMenuRight" class="nav-link text-dark" data-toggle="dropdown" title="${i18n("task.title")}" href="#">
                                <i class="fas fa-lg fa-fw fa-server"></i>
                            </a>
                            <div id="tasksMenuRightContainer" class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-3" style="max-width: none;">
                            </div>
                        </li>
                    `).insertAfter("#rightTopDynamic");

            $("#tasksMenuRight").off("click", modules.tasks.tasksMenuRight).on("click", modules.tasks.tasksMenuRight);
        }
    },

    tasksMenuRight() {
        GET("task", "status")
            .done((response) => $("#tasksMenuRightContainer").text(JSON.stringify(response)))
    }
}).init()