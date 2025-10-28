/**
 * Checks the availability of an API endpoint, method, and optionally a request method.
 *
 * @param {string} api - The name of the API to check.
 * @param {string} [method] - The method within the API to check.
 * @param {string} [request_method] - The HTTP request method (e.g., 'GET', 'POST') to check.
 *
 * @returns {*} The availability status of the specified API/method/request_method, or undefined if not available.
 */

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

/**
 * Sends an AJAX GET request to the specified API endpoint with optional query parameters and headers.
 *
 * @param {string} api - The API endpoint to call.
 * @param {string} method - The method or action to perform on the API.
 * @param {Object} [query] - Optional query parameters to include in the request.
 * @param {boolean} [fresh] - If true, adds a cache-busting parameter and a refresh header.
 *
 * @returns {jqXHR} A jQuery jqXHR object representing the AJAX request.
 */

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
            xhr.setRequestHeader("Accept-Language", l);
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

/**
 * Sends an AJAX GET request to a specified API endpoint with optional query parameters and headers.
 *
 * @param {string} api - The API name or endpoint.
 * @param {string} method - The method or action to be called on the API.
 * @param {string|number} id - The identifier for the resource.
 * @param {Object} [query] - Optional query parameters to include in the request.
 * @param {boolean} [fresh] - If true, adds a cache-busting parameter and a custom header to force a fresh response.
 *
 * @returns {jqXHR} A jQuery jqXHR object representing the AJAX request.
 */

function QUERYID(api, method, id, query, fresh) {
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
        url: lStore("_server") + "/" + encodeURIComponent(api) + "/" + encodeURIComponent(method) + "/" + encodeURIComponent(id) + (query ? ("?" + $.param(query)) : ""),
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            xhr.setRequestHeader("Accept-Language", l);
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

/**
 * Sends a GET request to the specified API endpoint with optional parameters.
 *
 * @param {string} api - The API resource name.
 * @param {string} method - The API method to call.
 * @param {string|number|undefined|false} id - Optional identifier to append to the URL. If undefined or false, it is omitted.
 * @param {boolean} [fresh] - If true, adds a cache-busting query parameter and a custom header to force refresh.
 *
 * @returns {jqXHR} A jQuery jqXHR object representing the AJAX request.
 */

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
    return $.ajax({
        url: url,
        beforeSend: xhr => {
            xhr.setRequestHeader("Authorization", "Bearer " + lStore("_token"));
            xhr.setRequestHeader("Accept-Language", l);
            if (fresh) {
                xhr.setRequestHeader("X-Api-Refresh", "1");
            }
        },
        type: "GET",
        contentType: "json",
    });
}

/**
 * Sends an AJAX request to the specified API endpoint with authorization and language headers.
 *
 * @param {string} type - The HTTP request method (e.g., "GET", "POST", "PUT", "DELETE").
 * @param {string} api - The API resource name.
 * @param {string} method - The API method or action to call.
 * @param {string|number|boolean} [id] - Optional identifier to append to the endpoint URL.
 * @param {Object} [query] - Optional data to send with the request (will be JSON-stringified).
 *
 * @returns {jqXHR} A jQuery jqXHR object representing the AJAX request.
 */

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
            xhr.setRequestHeader("Accept-Language", l);
        },
        type: type,
        contentType: "json",
        data: query?JSON.stringify(query):null,
    });
}

/**
 * Sends a POST request using the AJAX function.
 *
 * @param {string} api - The API endpoint to send the request to.
 * @param {string} method - The method or action to perform.
 * @param {string|number} id - The identifier for the resource.
 * @param {Object} query - The query parameters or data to send with the request.
 *
 * @returns {Promise<any>} The result of the AJAX request.
 */

function POST(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

/**
 * Sends a PUT request using the AJAX function.
 *
 * @param {string} api - The API endpoint to send the request to.
 * @param {string} method - The method or resource to be accessed or modified.
 * @param {string|number} id - The identifier for the resource.
 * @param {Object} query - The query parameters or data to be sent with the request.
 *
 * @returns {*} The result of the AJAX call.
 */

function PUT(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}

/**
 * Sends a DELETE request to the specified API endpoint.
 *
 * @param {string} api - The base API URL or endpoint.
 * @param {string} method - The specific API method or resource to target.
 * @param {string|number} id - The identifier for the resource to delete.
 * @param {Object} [query] - Optional query parameters to include in the request.
 *
 * @returns {Promise<any>} The result of the AJAX call.
 */

function DELETE(api, method, id, query) {
    return AJAX(arguments.callee.name.toString(), api, method, id, query);
}
