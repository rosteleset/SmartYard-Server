<?php

    /**
     * backends inbox namespace
     */

    namespace backends\inbox
    {

        /**
         * clickhouse archive class
         */
        class clickhouse extends inbox
        {
            private $clickhouse;

            /**
             * @inheritDoc
             */
            function __construct($config, $db, $redis, $login = false)
            {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host']?:'127.0.0.1',
                    @$config['clickhouse']['port']?:8123,
                    @$config['clickhouse']['username']?:'default',
                    @$config['clickhouse']['password']?:'qqq',
                    @$config['clickhouse']['database']?:'default'
                );
            }

            /**
             * @inheritDoc
             */
            public function sendMessage($subscriberId, $title, $msg, $action = "inbox")
            {
                $subscriber = $this->db->get("select id, platform, push_token, push_token_type from houses_subscribers_mobile where house_subscriber_id = :house_subscriber_id", [
                    "house_subscriber_id" => $subscriberId,
                ], [
                    "id" => "id",
                    "platform" => "platform",
                    "push_token" => "token",
                    "push_token_type" => "tokenType"
                ], [ "singlify" ]);

                if (!checkInt($subscriber["platform"]) || !checkInt($subscriber["tokenType"]) || !$subscriber["id"] || !$subscriber["token"]) {
                    setLastError("mobileSubscriberNotRegistered");
                    return false;
                }

                if ($subscriber) {
                    $msgId = $this->db->insert("insert into inbox (id, house_subscriber_id, date, title, msg, action, expire, delivered, readed, code) values (:id, :house_subscriber_id, :date, :title, :msg, :action, :expire, 0, 0, null)", [
                        "id" => $subscriber["id"],
                        "house_subscriber_id" => $subscriberId,
                        "date" => time(),
                        "title" => $title,
                        "msg" => $msg,
                        "action" => $action,
                        "expire" => time() + 7 * 60 * 60 * 60,
                    ]);

                    $unreaded = $this->unreaded($subscriberId);

                    if (!$msgId) {
                        setLastError("cantStoreMessage");
                        return false;
                    }

                    $isdn = loadBackend("isdn");
                    if ($isdn) {
                        $result = $isdn->push([
                            "token" => $subscriber["token"],
                            "type" => $subscriber["tokenType"],
                            "timestamp" => time(),
                            "ttl" => 30,
                            "platform" => (int)$subscriber["platform"]?"ios":"android",
                            "title" => $title,
                            "msg" => $msg,
                            "badge" => $unreaded,
                            "sound" => "default",
                            "pushAction" => $action,
                        ]);
                        $result = explode(":", $result);
                        if ($this->db->modify("update inbox set code = :code, push_message_id = :push_message_id where msg_id = :msg_id", [
                            "msg_id" => $msgId,
                            "code" => $result[0],
                            "push_message_id" => $result[0]?:"unknown",
                        ])) {
                            return $msgId;
                        } else {
                            setLastError("errorSendingPush: " . $result[1]?$result[1]:$result[0]);
                            return false;
                        }
                    } else {
                        setLastError("pushCantBeSent");
                        return false;
                    }
                } else {
                    setLastError("subscriberNotFound");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function getMessages($subscriberId, $by, $params)
            {
                $w = "";
                $q = [];

                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                switch ($by) {
                    case "dates":
                        $w = "where house_subscriber_id = :id and date < :date_to and date >= :date_from";
                        $q = [
                            "id" => $subscriberId,
                            "date_from" => $params["dateFrom"],
                            "date_to" => $params["dateTo"],
                        ];
                        break;
                    case "id":
                        $w = "where house_subscriber_id = :id and msg_id = :msg_id";
                        $q = [
                            "id" => $subscriberId,
                            "msg_id" => $params,
                        ];
                        break;
                }

                return $this->db->get("select * from inbox $w", $q, [
                    "msg_id" => "msgId",
                    "house_subscriber_id" => "subscriberId",
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
            }

            /**
             * @inheritDoc
             */
            public function msgMonths($subscriberId)
            {
                $months = $this->db->get("select month from (select substr(date, 1, 7) as month from inbox where house_subscriber_id = :house_subscriber_id) group by month order by month", [
                    "house_subscriber_id" => $subscriberId,
                ]);

                $r = [];

                foreach ($months as $month) {
                    $r[] = $month["month"];
                }

                return $r;
            }

            /**
             * @inheritDoc
             */
            public function markMessageAsReaded($subscriberId, $msgId = false)
            {
                if ($msgId) {
                    return $this->db->modify("update inbox set readed = 1 where readed = 0 and msg_id = :msg_id and house_subscriber_id = :house_subscriber_id", [
                        "house_subscriber_id" => $subscriberId,
                        "msg_id" => $msgId,
                    ]);
                } else {
                    return $this->db->modify("update inbox set readed = 1 where readed = 0 and house_subscriber_id = :house_subscriber_id", [
                        "house_subscriber_id" => $subscriberId,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */
            public function markMessageAsDelivered($subscriberId, $msgId = false)
            {
                if ($msgId) {
                    return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and msg_id = :msg_id and house_subscriber_id = :house_subscriber_id", [
                        "house_subscriber_id" => $subscriberId,
                        "msg_id" => $msgId,
                    ]);
                } else {
                    return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and house_subscriber_id = :house_subscriber_id", [
                        "house_subscriber_id" => $subscriberId,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */
            public function unreaded($subscriberId)
            {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                return $this->db->get("select count(*) as unreaded from inbox where house_subscriber_id = :house_subscriber_id and readed = 0", [
                    "house_subscriber_id" => $subscriberId,
                ],
                    [
                        "unreaded" => "unreaded",
                    ],
                    [
                        "fieldlify"
                    ]);
            }

            /**
             * @inheritDoc
             */
            public function undelivered($subscriberId)
            {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidSubscriberId");
                    return false;
                }

                return $this->db->get("select count(*) as undelivered from inbox where house_subscriber_id = :house_subscriber_id and delivered = 0", [
                    "house_subscriber_id" => $subscriberId,
                ],
                    [
                        "undelivered" => "undelivered",
                    ],
                    [
                        "fieldlify"
                    ]);
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                if ($part == '5min') {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
