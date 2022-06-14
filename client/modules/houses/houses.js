({
    init: function () {
        moduleLoaded("houses", this);
    },

    house: function (houseId, address_house) {

        function render(house) {
            $("#mainForm").html(``);
        }

        if (address_house) {
            subTop(address_house.houseFull);
        } else {
            subTop("#" + houseId);
        }
        GET("houses", "house", houseId, true).
        fail(response => {
            // ?
        }).
        done(response => {
            render(response.house);
        });
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("houses.houses");

        if (AVAIL("addresses", "house", "GET")) {
            GET("addresses", "house", params.houseId).
            fail(FAIL).
            done(result => {
                modules["houses"].house(params.houseId, result.house);
            }).
            fail(() => {
                modules["houses"].house(params.houseId);
            });
        } else {
            modules["houses"].house(params.houseId);
        }

        loadingDone();
    },
}).init();