var jQuery = {};
var version = Math.random();

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
    "index.css",
];

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    const pathname = url.pathname.split("/");

    if (forceVersioning.indexOf(pathname[pathname.length - 1]) >= 0 && !url.search && version) {
        console.log("forceVersioningc: " + url.href);

//        event.respondWith(fetch(url.href + "?swVer=" + version));
        event.respondWith(Response.redirect(url.href + "?swVer=" + version, 302));
}
});
