({
    init: function () {
        console.log("custom.loaded");
        moduleLoaded("custom", this);
    },

    GET: function (url, query) {
        return $.ajax({
            url: url + (query?("?" + $.param(query)):""),
            type: "GET",
            contentType: "json",
        });
    },

    AJAX: function (type, url, query) {
        return $.ajax({
            url: url,
            type: type,
            contentType: "json",
            data: query?JSON.stringify(query):null,
        });
    },

    POST: function (url, query) {
        return this.AJAX(arguments.callee.name.toString(), url, query);
    },
}).init();