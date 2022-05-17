function GET(api, method, id) {
    return $.ajax({
        url: $.cookie("_server") + "/" + api + "/" + method + (id?("/" + id):""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + $.cookie("_token"));
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