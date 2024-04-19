({
    menuItem: false,

    init: function () {
        if (parseInt(myself.uid)) {
            this.menuItem = leftSide("fas fa-fw fa-monument", i18n("tt.last"), "?#tt.last", "tt");
        }
        moduleLoaded("tt.last", this);
    },

    route: function (params) {
        if (modules.tt.last.menuItem) {
            $("#" + modules.tt.last.menuItem).children().first().attr("href", "?#tt.last&_=" + Math.random());
        }

        subTop();

        GET("tt", "tt", false, true).
        done(modules.tt.tt).
        done(() => {
            if (parseInt(myself.uid)) {
                $("#altForm").hide();
                $("#mainForm").html("").show();
                GET("tt", "journalLast").done(console.log);
                loadingDone();
            } else {
                window.location.href = "?#tt.settings";
            }
        }).
        fail(FAIL).
        fail(loadingDone);
    },
}).init();