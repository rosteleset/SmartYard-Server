({
    init: function () {
        // add section to left vertical menu
        $("#leftside-menu").append(`
            <li class="nav-item" title="${i18n("address.addresses")}">
                <a href="#address" class="nav-link">
                    <i class="fas fa-fw fa-home nav-icon"></i>
                    <p>${i18n("address.addresses")}</p>
                </a>
            </li>
        `);

        // add icon-button to top-right menu
        $("#topMenuRight").prepend(`
            <li class="nav-item">
                <span class="nav-link text-primary" role="button" style="cursor: pointer" title="${i18n("address.addresses")}" id="addressMenuRight">
                    <i class="fas fa-lg fa-fw fa-home"></i>
                </span>
            </li>
        `);

        // and add handler to onclick
        $("#addressMenuRight").off("click").on("click", () => {
            modules["address"].menuRight();
        });

        // load sub module
        loadSubModules("address", [ "countries" ], () => {
            moduleLoaded("address", this);
        })
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("address.addresses");
        $("#mainForm").html(i18n("address.addresses"));

        // add menu item to left top menu
        $("#topMenuLeftDynamic").html(`
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">${i18n("address.addresses")}</a>
            </li>
        `);

        loadingDone();
    },

    // just for example
    menuRight: function () {
        mAlert(i18n("address.addresses"));
    },

    // if search function is defined, search string will be displayed
    search: function (str) {
        console.log("address: " + str);
    },
}).init();