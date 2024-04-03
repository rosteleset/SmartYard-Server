// "fake" module, required for adding some i18n translations and API defenitions
// for real CDR backend it must be realized at custom server backend extension
({
    init: function () {
        moduleLoaded("cdr", this);
    },
}).init();