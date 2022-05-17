({
    init: function () {
        if (window.available["accounts"] && window.available["accounts"]["groups"]) {
            $("#leftside-menu").append(`
            <li class="nav-item" title="${i18n("groups.groups")}">
                <a href="#groups" class="nav-link">
                    <i class="fas fa-fw fa-users nav-icon"></i>
                    <p>${i18n("groups.groups")}</p>
                </a>
            </li>
        `);
        }
        moduleLoaded("groups", this);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("groups.groups");
        $("#mainForm").html(i18n("groups.groups"));
        console.log(params);
        loadingDone();
    }
}).init();