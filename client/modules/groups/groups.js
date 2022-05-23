({
    startPage: 1,

    init: function () {
        if (window.available["accounts"] && window.available["accounts"]["groups"]) {
            leftSide("fas fa-fw fa-users", i18n("groups.groups"), "#groups");
        }
        moduleLoaded("groups", this);
    },

    addGroup: function () {
        console.log("add");
    },

    /*
        main form (users) render function
     */

    render: function () {
        loadingStart();

        GET("accounts", "groups", false, true).done(response => {
            cardTable({
                addButton: {
                    title: i18n("groups.addGroup"),
                    click: window.modules["groups"].addGroup,
                },
                title: i18n("groups.groups"),
                filter: true,
                startPage: window.modules["groups"].startPage,
                columns: [
                    {
                        title: i18n("groups.gid"),
                    },
                    {
                        title: i18n("groups.acronym"),
                    },
                    {
                        title: i18n("groups.name"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i = 0; i < response.groups.length; i++) {
                        rows.push({
                            uid: response.groups[i].gid.toString(),
                            cols: [
                                {
                                    data: response.groups[i].gid,
                                    click: window.modules["groups"].modifyGroup,
                                },
                                {
                                    data: response.groups[i].acronym,
                                    click: window.modules["groups"].modifyGroup,
                                    nowrap: true,
                                },
                                {
                                    data: response.groups[i].name,
                                    nowrap: true,
                                },
                            ],
                        });
                    }

                    return rows;
                },
                target: "#mainForm",
                pageChange: page => {
                    window.modules["groups"].startPage = page;
                },
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    route: function (params) {
        document.title = i18n("windowTitle") + " :: " + i18n("groups.groups");

        window.modules["groups"].render();
    }
}).init();