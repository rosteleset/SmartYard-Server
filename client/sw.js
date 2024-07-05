var version = Math.random();

function deparam(query) {
    if (query) {
        if (query[0] == "?") {
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

        for (let i = 0 ; i < nvp.length ; i++) {
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

if (location.search) {
    version = deparam(location.search).ver;
    if (!version) {
        version = Math.random();
    }
}

console.log("swVersion: " + version);

const forceVersioning = [
    "app.js",
    "index.css",
];

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    const pathname = url.pathname.split("/");

    if (forceVersioning.indexOf(pathname[pathname.length - 1]) >= 0 && !url.search && version) {
        event.respondWith(Response.redirect(url.href + "?ver=" + version, 302));
    }
});
