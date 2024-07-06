({
    menuItem: false,

    init: function () {
        if (parseInt(myself.uid) > 0) {
            if (AVAIL("notes")) {
                this.menuItem = leftSide("far fa-fw fa-sticky-note fa-flip-vertical", i18n("notes.notes"), "?#notes", "notes");
            }
        }
        moduleLoaded("notes", this);
    },

    renderNotes: function() {
        $("#mainForm").html("");
        loadingDone();
    },

    route: function (params) {
        subTop();
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("notes.notes");

        if (modules.notes.menuItem) {
            $("#" + modules.notes.menuItem).children().first().attr("href", "?#notes&_=" + Math.random());
        }

        modules.notes.renderNotes();
    },
}).init();