({
    client: false,
    subscribers: false,

    init: function () {
        moduleLoaded("mqtt", this);

        if (AVAIL("mqtt", "config")) {
            GET("mqtt", "config").
            done(response => {
                modules.mqtt.client = mqtt.connect(response.config.ws, {
                    keepalive: 0,
                    username: response.config.username,
                    password: response.config.password,
                });

                modules.mqtt.client.on("message", (msg, payload) => {
                    if (modules.mqtt.subscribers && modules.mqtt.subscribers[msg]) {
                        for (let i in modules.mqtt.subscribers[msg]) {
                            modules.mqtt.subscribers[msg][i](msg, JSON.parse(payload.toString()));
                        }
                    }
                });

                modules.mqtt.client.on("connect", () => {
                    if (modules.mqtt.subscribers) {
                        for (let t in modules.mqtt.subscribers) {
                            modules.mqtt.client.subscribe(t);
                        }
                        if (modules.mqtt.subscribers["_connect"]) {
                            for (let i in modules.mqtt.subscribers["_connect"]) {
                                modules.mqtt.subscribers["_connect"][i]("_connect");
                            }
                        }
                    }
                });

                modules.mqtt.subscribe("redis/expire", (topic, payload) => {
                    if (payload.key == "SUDO:" + myself.login) {
                        loadingStart();
                        setTimeout(() => {
                            window.onhashchange = hashChange;
                            window.location.reload();
                        }, 150);
                    }
                });
            }).
            fail(FAIL);
        }
    },

    subscribe: function (topic, callback) {
        if (!modules.mqtt.subscribers) {
            modules.mqtt.subscribers = {};
        }

        if (!modules.mqtt.subscribers[topic]) {
            modules.mqtt.subscribers[topic] = [];
        }

        if (modules.mqtt.subscribers[topic].indexOf(callback) < 0) {
            modules.mqtt.subscribers[topic].push(callback);
        }

        if (modules.mqtt.client) {
            modules.mqtt.client.subscribe(topic);
        }
    },
}).init();