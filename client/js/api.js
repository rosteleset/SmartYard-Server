function QUERY(api, method, query, fresh) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + (query?("?" + $.param(query)):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function GET(api, method, id, fresh) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + ((typeof id !== "undefined" && id !== false)?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function AJAX(type, api, method, id, query) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + ((typeof id !== "undefined" && id !== false)?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
        },
        type: type,
        contentType: "json",
        data: query?JSON.stringify(query):null,
    });
}

function POST(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function PUT(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function DELETE(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

function FAIL(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
    } else {
        error(i18n("errors.unknown"), i18n("error"), 30);
    }
}

function FAILPAGE(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
        pageError(i18n("errors." + response.responseJSON.error));
    } else {
        error(i18n("errors.unknown"), i18n("error"), 30);
        pageError();
    }
}

function AVAIL(api, method, request_method) {
    if (request_method) {
        return available && available[api] && available[api][method] && available[api][method][request_method];
    }
    if (method) {
        return available && available[api] && available[api][method];
    }
    if (api) {
        return available && available[api];
    }
}