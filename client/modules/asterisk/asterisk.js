({
    init: function () {
        // add icon-button to top-right menu
        $("#topMenuRight").prepend(`
            <li class="nav-item">
                <span class="nav-link text-secondary" role="button" style="cursor: pointer" title="${i18n("asterisk.asterisk")}" id="asteriskMenuRight">
                    <i class="fas fa-lg fa-fw fa-asterisk"></i>
                </span>
            </li>
        `);
        moduleLoaded("address", this);
    },
}).init();