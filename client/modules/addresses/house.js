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
                            then(response => {
                                loadingDone();
                                success(response);
                            }).
                            fail(response => {
                                FAIL(response);
                                loadingDone();
                                failure(response);
                            }).
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
                    done(result => {
                        GET("addresses", "house", result.houseId).
                        done(result => {
                            console.log(result);
                            message(i18n("addresses.houseWasAdded"));
                            if (result && result.house && (result.house.streetId || result.house.settlementId)) {
                                if (result.house.streetId) {
                                    location.href = "#addresses&show=street&streetId=" + result.house.streetId;
                                } else {
                                    location.href = "#addresses&show=settlement&settlementId=" + result.house.settlementId;
                                }
                            } else {
                                error(i18n("errors.unknown"));
                                loadingDone();
                            }
                        }).
                        fail(FAIL).
                        fail(loadingDone);
                    }).
                    fail(FAIL).
                    fail(loadingDone);
                }
            },
        }).show();
    },
}).init();
