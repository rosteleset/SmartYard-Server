({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.house", this);
    },

    houseMagick: function () {
        cardForm({
            title: i18n("addresses.address"),
            footer: true,
            borderless: true,
            topApply: true,
            size: "lg",
            apply: i18n("add"),
            fields: [
                {
                    id: "address",
                    type: "select2",
                    title: i18n("addresses.address"),
                    placeholder: i18n("addresses.address"),
                    ajax: {
                        delay: 1000,
                        transport: function (params, success, failure) {
                            loadingStart();
                            QUERY("geo", "suggestions", {
                                search: params.data.term,
                            }).
                            then(success).
                            fail(failure).
                            fail(FAIL).
                            always(loadingDone);
                        },
                        processResults: function (data, params) {
                            let suggestions = [];
                            for (let i in data.suggestions) {
                                if (parseInt(data.suggestions[i].data.fias_level) === 8) {
                                    suggestions.push({
                                        id: data.suggestions[i].data.house_fias_id,
                                        text: data.suggestions[i].value,
                                    });
                                }
                            }
                            return {
                                results: suggestions,
                            };
                        },
                    },
                    validate: v => {
                        return !!v;
                    }
                },
            ],
            callback: function (result) {
                if (result && result.address) {
                    loadingStart();
                    POST("addresses", "house", false, {
                        magick: result.address,
                    }).
                    done(() => {
                        message(i18n("addresses.houseWasAdded"));
                    }).
                    fail(FAIL).
                    always(modules["addresses"].renderRegions);
                }
            },
        }).show();
    }
}).init();
