({
    realRoute: false,

    init: function () {
        moduleLoaded("map.cameras", this);
    },

    route: function (params) {
        subTop("<a href='#'>cameras</a>");

        modules.map.cameras.realRoute(params);

        $('#mapContainer').css("height", ($(window).height() - mainFormTop - $('#subTop').height()) + "px");
    },

    allLoaded: function () {
        modules.map.cameras.realRoute = modules.map.route;
        modules.map.route = modules.map.cameras.route;
    }
}).init();    
