function AVAIL(api, method, request_method) {
    if (request_method) {
        request_method = request_method.toUpperCase();
    }
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

function QUERY(api, method, query, fresh) {
    let l = lStore("_lang");
    if (!l) {
        l = config.defaultLanguage;
    }
    if (!l) {
        l = "ru";
    }
    if (fresh) {
        if (!query) {
            query = {};
        }
        query["_"] = Math.random();
    }
    return $.ajax({
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + (query ? ("?" + $.param(query)) : ""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            xhr.setRequestHeader("Lang", l);
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function GET(api, method, id, fresh) {
    let l = lStore("_lang");
    if (!l) {
        l = config.defaultLanguage;
    }
    if (!l) {
        l = "ru";
    }
    let url = lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + ((typeof id !== "undefined" && id !== false) ? ("/" + encodeURIComponent(id)) : "");
    if (fresh) {
        url += "?_=" + Math.random();
    }
    console.log(url);
    return $.ajax({
        url: url,
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            xhr.setRequestHeader("Lang", l);
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

function AJAX(type, api, method, id, query) {
    let l = lStore("_lang");
    if (!l) {
        l = config.defaultLanguage;
    }
    if (!l) {
        l = "ru";
    }
    return $.ajax({
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + ((typeof id !== "undefined" && id !== false) ? ("/" + encodeURIComponent(id)) : ""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            xhr.setRequestHeader("Lang", l);
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
