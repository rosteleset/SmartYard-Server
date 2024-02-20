({
    realRoute: false,

    init: function () {
        moduleLoaded("map.cameras", this);
    },

    route: function (params) {
        subTop("<a href='#'>cameras</a>");
        modules.map.cameras.realRoute(params);
    },

    allLoaded: function () {
        modules.map.cameras.realRoute = modules.map.route;
        modules.map.route = modules.map.cameras.route;
    }
}).init();    
