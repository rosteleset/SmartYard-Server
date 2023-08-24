({
    init: function () {
        if (AVAIL("tasks", "queues", "GET")) {
            $(`
                <li class="nav-item">
                    <span class="nav-link text-primary" role="button" style="cursor: pointer" title="${i18n("tasks.title")}" id="taskMenuRight">
                        <i class="fas fa-lg fa-fw fa-server"></i>
                    </span>
                </li>
            `).insertAfter("#rightTopDynamic");

            moduleLoaded("tasks", this)
        }
    }
}).init()