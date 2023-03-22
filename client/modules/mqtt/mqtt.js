({
    client: false,
    subscribers: false,

    init: function () {
        moduleLoaded("mqtt", this);

        if (AVAIL("mqtt", "config")) {
            GET("mqtt", "config").
            done(response => {
                modules.mqtt.client = mqtt.connect(response.config.ws, {
                    username: response.config.username,
                    password: response.config.password,
                });

                modules.mqtt.client.on("message", (msg, payload) => {
                    if (modules.mqtt.subscribers[msg]) {
                        for (let i in modules.mqtt.subscribers[msg]) {
                            modules.mqtt.subscribers[msg][i](msg, JSON.parse(payload.toString()))
                        }
                    }
                });

                modules.mqtt.client.on("connect", () => {
                    for (let t in modules.mqtt.subscribers) {
                        modules.mqtt.client.subscribe(t);
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