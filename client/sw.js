var version = false;
var cache = false;

const forceVersioningResources = [
    "index.css",
    "app.js",
    "form.js",
    "greetings.js",
    "i18n.js",
    "loader.js",
    "menu.js",
    "phpjs.js",
    "pwgen.js",
    "rest.js",
    "rs232.js",
    "table.js",
    "upload.js",
    "utils.js",
    "widgets.js",
];

const cacheFirstResources = [
    ".js",
    ".css",
    ".png",
    ".svg",
    ".woff2",
    ".json",
    ".html",
];

const directProtocols = [
    "chrome-extension:",
    "tg:",
];

if (location.search) {
    version = deparam(location.search).ver;
    if (!version) {
        version = Math.random();
    }
};

function deparam(query) {
    if (query) {
        if (query[0] == "?") {
            query = query.substring(1);
        }

        if (query[0] == "&") {
            query = query.substring(1);
        }

        let setValue = function (root, path, value) {
            if (path.length > 1) {
                let  dir = path.shift();
                if (typeof root[dir] == 'undefined') {
                    root[dir] = path[0] == '' ? [] : {};
                }
                arguments.callee(root[dir], path, value);
            } else {
                if (root instanceof Array) {
                    root.push(value);
                } else {
                    root[path] = value;
                }
            }
        };

        let nvp = query.split('&');
        let data = {};

        for (let i = 0; i < nvp.length; i++) {
            let pair = nvp[i].split('=');
            let name = decodeURIComponent(pair[0]);
            let value = decodeURIComponent(pair[1]);

            let path = name.match(/(^[^\[]+)(\[.*\]$)?/);
            let first = path[1];

            if (path[2]) {
                path = path[2].match(/(?=\[(.*)\]$)/)[1].split('][')
            } else {
                path = [];
            }
            path.unshift(first);
            setValue(data, path, value);
        }

        return data;
    } else {
        return {};
    }
}

function endsWith(str, ends) {
    let value = false;
    value = ends.some(element => {
        return str.endsWith(element);
    });
    return value;
}

async function deleteCache(key) {
    await caches.delete(key);
}

async function deleteOldCaches() {
    let cacheKeepList = [ version ];
    let keyList = await caches.keys();
    let cachesToDelete = keyList.filter(key => !cacheKeepList.includes(key));
    await Promise.all(cachesToDelete.map(deleteCache));
}

async function putInCache(request, response) {
    if (!cache) {
        cache = await caches.open(version);
    }
    await cache.put(request, response.clone());
    return response;
}

async function cacheFirst(request) {
    let responseFromCache = await caches.match(request);
    if (responseFromCache) {
        if (responseFromCache.url) {
            let url;
            try {
                url = new URL(responseFromCache.url);
            } catch (e) {
                url = false;
            }
            if (url && url.search) {
                let search = deparam(url.search);
                if (search._force_cache) {
                    let ttl = parseInt(search._force_cache);
                    if (ttl > 0 && Date.parse(responseFromCache.headers.get("date")) + ttl < Date.now()) {
                        return putInCache(request, await fetch(request));
                    }
                }
            }
        }
        return responseFromCache;
    }
    return putInCache(request, await fetch(request));
}

self.addEventListener("activate", event => {
    event.waitUntil(deleteOldCaches());
})

self.addEventListener('fetch', event => {
    let url = new URL(event.request.url);

    if (directProtocols.indexOf(url.protocol) < 0) {
        let pathname = url.pathname.split("/");

        if (!url.search) {
            if (forceVersioningResources.indexOf(pathname[pathname.length - 1]) >= 0) {
                event.respondWith(Response.redirect(url.href + "?ver=" + version, 302));
            } else {
                if (endsWith(event.request.url, cacheFirstResources)) {
                    event.respondWith(cacheFirst(event.request));
                }
            }
        } else {
            if (url.search && parseInt(deparam(url.search).ver) === parseInt(version) && endsWith(url.pathname, cacheFirstResources)) {
                event.respondWith(cacheFirst(event.request));
            } else {
                if (url.search && deparam(url.search)._force_cache) {
                    event.respondWith(cacheFirst(event.request));
                }
            }
        }
    }
})
