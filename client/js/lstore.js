var lStoreEngine = false;
var lStoreData = {};

function lStore(key, val) {
    if (!lStoreEngine) {
        let wdb;

        let t = guid();

        try {
            wdb = new IdbKvStore("rbt");

            wdb.on("add", function (change) {
                lStoreData[change.key] = change.value;
            });

            wdb.on("set", function (change) {
                lStoreData[change.key] = change.value;
            });

            wdb.on("remove", function (change) {
                delete lStoreData[change.key];
            });

            wdb.set("test", t);

            if (!IdbKvStore.BROADCAST_SUPPORT) {
                throw true;
            }

            wdb.remove("test");
        } catch (e) {
            wdb = false;
        }

        if (wdb) {
            lStoreEngine = wdb;
        } else {
            jQuery.cookie("test", t, { insecure: config.insecureCookie });

            if (jQuery.cookie("test") != t) {
                if (typeof error == "function") {
                    error(i18n("errors.cantStoreCookie"), i18n("error"), 30);
                } else {
                    console.log("cantStoreCookie");

                }
                return false;
            }

            jQuery.cookie("test", null);

            lStoreEngine = "cookie";
        }
    }

    if (key && typeof key !== "function") {
        if (typeof val != "undefined") {
            if (lStoreEngine === "cookie") {
                if (val === null) {
                    jQuery.cookie(key, val);
                } else {
                    jQuery.cookie(key, JSON.stringify(val), { expires: 3650, insecure: config.insecureCookie });
                }
            } else {
                if (val === null) {
                    delete lStoreData[key];
                    lStoreEngine.remove(key);
                } else {
                    lStoreData[key] = val;
                    lStoreEngine.set(key, val);
                }
            }
            return true;
        } else {
            if (lStoreEngine === "cookie") {
                try {
                    return JSON.parse(jQuery.cookie(key));
                } catch (e) {
                    jQuery.cookie(key, null);
                    return null;
                }
            } else {
                return lStoreData[key];
            }
        }
    } else {
        if (lStoreEngine === "cookie") {
            if (typeof key === "function") {
                key();
            }
        } else {
            lStoreEngine.json((err, kv) => {
                if (!err && kv) {
                    lStoreData = kv;
                }
                if (typeof key === "function") {
                    key();
                }
            });
        }
        return true;
    }
}
