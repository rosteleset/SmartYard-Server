({
    init() {
        moduleLoaded("tasks", this)

        if (AVAIL("tasks", "queues", "GET")) {
            $(`
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark tasks" data-toggle="dropdown" title="${i18n("tasks.title")}" href="#">
                        <i class="fas fa-lg fa-fw fa-server"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-width: none;">
                        <p>Статус текущих задач</p>
                        <div class="dropdown-divider"></div>
                    </div>
                </li>
            `).insertAfter("#rightTopDynamic");
        }
    }
}).init()