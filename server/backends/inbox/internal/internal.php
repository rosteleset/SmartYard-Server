<?php

    /**
     * backends inbox namespace
     */

    namespace backends\inbox
    {

        /**
         * internal.db inbox class
         */
        class internal extends inbox
        {

            /**
             * @inheritDoc
             */
            public function sendMessage($id, $title, $msg, $action = "inbox")
            {
                $subscriber = $this->db->get("select id, platform, push_token, push_token_type from houses_subscribers_mobile where id = :id", false, [
                    "id" => "id",
                    "platform" => "platform",
                    "push_token" => "token",
                    "push_token_type" => "tokenType"
                ], [ "singlify" ]);

                if ($subscriber) {
                    $msgId = $this->db->insert("insert into inbox (id, date, title, msg, action, expire, readed, code) values (:id, :date, :title, :msg, :action, :expire, 0, null)", [
                        "id" => $subscriber["id"],
                        "date" => $this->db->now(),
                        "title" => $title,
                        "msg" => $msg,
                        "action" => $action,
                        "expire" => time() + 7 * 60 * 60 * 60,
                    ]);

                    $unreaded = $this->db->get("select count(*) as unreaded from inbox where id = :id and readed = 0", [
                        "id" => $subscriber["id"],
                    ], [
                        "unreaded"
                    ], [
                        "fieldlify"
                    ]);

                    if (!$msgId) {
                        setLastError("cantStoreMessage");
                        return false;
                    }

                    $isdn = loadBackend("isdn");
                    if ($isdn) {
                        return $isdn->push([
                            "token" => $subscriber["token"],
                            "type" => $subscriber["tokenType"],
                            "timestamp" => time(),
                            "ttl" => 30,
                            "platform" => (int)$subscriber["platform"]?"ios":"android",
                            "title" => $title,
                            "body" => $msg,
                            "badge" => $unreaded,
                            "sound" => "default",
                            "action" => $action,
                        ]);
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
            public function getMessages($subscriberId, $dateFrom, $dateTo)
            {
                $id = $this->db->get("select id from houses_subscribers_mobile where house_subscriber_id = :id", [
                    "id" => $subscriberId,
                ], false, [ "singlify" ]);

                return $this->db->get("select * from inbox where id = :id and date < :date_to and date >= :date_from", [
                    "id" => $id,
                    "date_from" => $dateFrom,
                    "date_to" => $dateTo,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function msgMonths($subscriberId)
            {
                $months = $this->db->get("select month from (select substr(date, 1, 7) as month from inbox where id in (select id from houses_subscribers_mobile where house_subscriber_id = :id)) group by month order by month", [
                    "id" => $subscriberId,
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
            public function markMessageAsReaded($subscriberId, $msgId)
            {
                return $this->db->modify("update inbox set readed = 1 where msg_id = :msg_id and (select id from houses_subscribers_mobile where house_subscriber_id = :id)", [
                    "id" => $subscriberId,
                    "msg_id" => $msgId,
                ]);
            }
        }
    }
