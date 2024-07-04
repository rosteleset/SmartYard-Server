var jQuery = {};
var version = false;

importScripts('js/phpjs.js');
importScripts('js/idbkvstore.min.js');
importScripts('js/lstore.js');
importScripts('js/cookie.js');

lStore(() => {
    version = lStore("_version");
    console.log("swVersion: " + version);
});

const forceVersioning = [
    "app.js",
];

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    const pathname = url.pathname.split("/");

    if (forceVersioning.indexOf(pathname[pathname.length - 1]) >= 0 && !url.search && version) {
        console.log("forceVersioningc: " + url.href);
        event.respondWith(fetch(url.href + "?ver=" + version));
    }
});
