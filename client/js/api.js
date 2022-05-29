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
        url: $.cookie("_server") + "/" + api + "/" + method + (id?("/" + id):""),
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

function POST(api, method, id, query) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + (id?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
        },
        type: "POST",
        contentType: "json",
        data: query?JSON.stringify(query):null,
    });
}

function PUT(api, method, id, query) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + (id?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
        },
        type: "PUT",
        contentType: "json",
        data: query?JSON.stringify(query):null,
    });
}

function DELETE(api, method, id) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + (id?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
        },
        type: "DELETE",
        contentType: "json",
    });
}

function FAIL(response) {
    if (response && response.responseJSON && response.responseJSON.error) {
        error(i18n("errors." + response.responseJSON.error), i18n("error"), 30);
    } else {
        error(i18n("errors.unknown"), "[" + i18n("users.users") + "]: " + i18n("error"), 30);
    }
}

function AVAIL(api, method, request_method) {
    if (request_method) {
        return window.available && window.available[api] && window.available[api][method] && window.available[api][method][request_method];
    }
    if (method) {
        return window.available && window.available[api] && window.available[api][method];
    }
    if (api) {
        return window.available && window.available[api];
    }
}