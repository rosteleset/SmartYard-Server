<?php

    function i18n($msg, ...$args) {
        try {
            t = explode(".", msg);
            if (t.length > 2) {
                let t_ = [];
                t_[0] = t.shift();
                t_[1] = t.join(".");
                t = t_;
            }
            let loc;
            if (t.length === 2) {
                loc = lang[t[0]][t[1]];
            } else {
                loc = lang[t[0]];
            }
            if (loc) {
                if (typeof loc === "object" && Array.isArray(loc)) {
                    loc = nl2br(loc.join("\n"));
                }
                loc = sprintf(loc, ...args);
            }
            if (!loc) {
                if (t[0] === "errors") {
                    return t[1];
                } else {
                    return msg;
                }
            }
            return loc;
        } catch (_) {
            return msg;
        }
    }
