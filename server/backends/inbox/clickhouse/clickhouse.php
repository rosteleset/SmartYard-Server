<?php

    /**
     * backends inbox namespace
     */

    namespace backends\inbox
    {

        /**
         * clickhouse archive class
         */

        class clickhouse extends inbox {
            private $clickhouse;

            /**
             * @inheritDoc
             */

            function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host'] ?: '127.0.0.1',
                    @$config['clickhouse']['port'] ?: 8123,
                    @$config['clickhouse']['username'] ?: 'default',
                    @$config['clickhouse']['password'] ?: 'qqq',
                    @$config['clickhouse']['database'] ?: 'default'
                );
            }

            /**
             * @inheritDoc
             */

            public function sendMessage($subscriberId, $title, $msg, $action = "inbox") {
                $households = loadBackend("households");
                $subscribers = $households->getSubscribers("id", $subscriberId);

                if (!@$subscribers) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $msgId = $this->db->insert("insert into inbox (id, date, title, msg, action, expire, delivered, readed, code) values (:id, :date, :title, :msg, :action, :expire, 0, 0, null)", [
                    "id" => $devices[0]["subscriber"]["mobile"],
                    "date" => time(),
                    "title" => $title,
                    "msg" => $msg,
                    "action" => $action,
                    "expire" => time() + 7 * 60 * 60 * 60,
                ]);

                if (!$msgId) {
                    setLastError("cantStoreMessage");
                    return false;
                }

                $devices = $households->getDevices("subscriber", $subscriberId);

                if ($devices && count($devices)) {
                    $isdn = loadBackend("isdn");
                    $unreaded = $this->unreaded($subscriberId);

                    foreach ($devices as $device) {
                        if ($isdn && checkInt($device["platform"]) && checkInt($device["tokenType"]) && $device["pushToken"]) {
                            if (!(($action == "inbox" && (int)$device["pushDisable"]) || ($action == "money" && (int)$device["moneyDisable"]))) {
                                if (!$result = $isdn->push([
                                    "token" => $device["pushToken"],
                                    "type" => ((int)$device["platform"] === 1) ? 0 : $device["tokenType"], // force FCM for Apple for text messages
                                    "timestamp" => time(),
                                    "ttl" => 30,
                                    "platform" => [ "android", "ios", "web" ][(int)$device["platform"]],
                                    "title" => $title,
                                    "msg" => $msg,
                                    "badge" => $unreaded,
                                    "sound" => "default",
                                    "pushAction" => $action,
                                    "messageId" => $msgId,
                                ])) {
                                    setLastError("pushCantBeSent");
                                }
                            }
                        } else {
                            setLastError("pushCantBeSent");
                        }
                    }
                }

                return $msgId;
            }

            /**
             * @inheritDoc
             */

            public function getMessages($subscriberId, $by, $params) {
                $q = "";

                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                $households = loadBackend("households");
                $subscribers = $households->getSubscribers("id", $subscriberId);

                if (!@$subscribers) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $date_from = (int)$params["dateFrom"];
                $date_to = (int)$params["dateTo"];
                $msg_id = (int)$params;
                $id = $subscribers[0]["mobile"];

                switch ($by) {
                    case "dates":
                        $q = "select * from inbox where id = '$id' and date <= $date_to and date >= $date_from";
                        break;

                    case "id":
                        $q = "select * from inbox where id = '$id' and msg_id = $msg_id";
                        break;
                }

                if ($q) {
                    $oper = $this->db->get($q, false, [
                        "msg_id" => "msgId",
                        "id" => "id",
                        "date" => "date",
                        "title" => "title",
                        "msg" => "msg",
                        "action" => "action",
                        "expire" => "expire",
                        "push_message_id" => "pushMessageId",
                        "delivered" => "delivered",
                        "readed" => "readed",
                        "code" => "code",
                    ]);

                    $arc = array_map(
                        function ($item) {
                            return [
                                "msgId" => $item["msg_id"],
                                "id" => $item["id"],
                                "date" => $item["date"],
                                "title" => $item["title"],
                                "msg" => $item["msg"],
                                "action" => $item["action"],
                                "code" => $item["code"],
                            ];
                        },
                        $this->clickhouse->select($q)
                    );

                    $msgs = array_merge($oper, $arc);

                    usort($msgs, function ($a, $b) { return $b["date"] - $a["date"]; });

                    return $msgs;
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function markMessageAsReaded($subscriberId, $msgId = false) {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                $households = loadBackend("households");
                $devices = $households->getDevices("subscriber", $subscriberId);

                if (!@$devices) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $id = $devices[0]["subscriber"]["mobile"];

                if ($msgId) {
                    return $this->db->modify("update inbox set readed = 1 where readed = 0 and msg_id = :msg_id and id = :id", [
                        "id" => $id,
                        "msg_id" => $msgId,
                    ]);
                } else {
                    return $this->db->modify("update inbox set readed = 1 where readed = 0 and id = :id", [
                        "id" => $id,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */

            public function markMessageAsDelivered($subscriberId, $msgId = false) {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                $households = loadBackend("households");
                $devices = $households->getDevices("subscriber", $subscriberId);

                if (!@$devices) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $id = $devices[0]["subscriber"]["mobile"];

                if ($msgId) {
                    return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and msg_id = :msg_id and id = :id", [
                        "id" => $id,
                        "msg_id" => $msgId,
                    ]);
                } else {
                    return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and id = :id", [
                        "id" => $id,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */

            public function unreaded($subscriberId) {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                $households = loadBackend("households");
                $devices = $households->getDevices("subscriber", $subscriberId);

                if (!@$devices) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $id = $devices[0]["subscriber"]["mobile"];

                return $this->db->get("select count(*) as unreaded from inbox where id = :id and readed = 0",
                    [
                        "id" => $id,
                    ],
                    [
                        "unreaded" => "unreaded",
                    ],
                    [
                        "fieldlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */

            public function undelivered($subscriberId) {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                $households = loadBackend("households");
                $devices = $households->getDevices("subscriber", $subscriberId);

                if (!@$devices) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                $id = $devices[0]["subscriber"]["mobile"];

                return $this->db->get("select count(*) as undelivered from inbox where id = :id and delivered = 0",
                    [
                        "id" => $id,
                    ],
                    [
                        "undelivered" => "undelivered",
                    ],
                    [
                        "fieldlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == '5min') {
                    $i = true;

                    $readed = $this->db->get("select msg_id, id, date, title, msg, action, code from inbox where expire < :now or readed = 1",
                        [
                            "now" => time(),
                        ],
                        [
                            "msg_id" => "msg_id",
                            "id" => "id",
                            "date" => "date",
                            "title" => "title",
                            "msg" => "msg",
                            "action" => "action",
                            "code" => "code",
                        ]
                    );

                    foreach ($readed as $msg) {
                        $i = $this->clickhouse->insert("inbox", [
                            [
                                "msg_id" => $msg["msg_id"],
                                "id" => $msg["id"],
                                "date" => $msg["date"],
                                "title" => $msg["title"],
                                "msg" => $msg["msg"],
                                "action" => $msg["action"],
                                "code" => $msg["code"],
                            ]
                        ]);

                        if ($i) {
                            $this->db->modify("delete from inbox where msg_id = :msg_id", [
                                "msg_id" => $msg["msg_id"],
                            ]);
                        } else {
                            break;
                        }
                    }

                    return $i;
                } else {
                    return true;
                }
            }
        }
    }
