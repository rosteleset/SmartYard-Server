<?php

    /**
     * backends users namespace
     */

    namespace backends\users {

        use backends\backend;

        /**
         * base users class
         */

        abstract class users extends backend {

            /**
             * @var object $clickhouse clickhouse db
             */

            protected $clickhouse;

            /**
             * @param $config
             * @param $db
             * @param $redis
             */

            function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host'] ?: '127.0.0.1',
                    @$config['clickhouse']['port'] ?: 8123,
                    @$config['clickhouse']['username'] ?: 'default',
                    @$config['clickhouse']['password'] ?: 'qqq',
                    @$config['clickhouse']['database'] ?: 'default'
                );
            }

            /**
             * get list of all users
             *
             * @param boolean $withSessions
             * @param boolean $withLast
             *
             * @return array
             */

            abstract public function getUsers($withSessions = false, $withLast = false);

            /**
             * get user by uid
             *
             * @param integer $uid uid
             * @param integer $withGroups withGroups
             *
             * @return array
             */

            abstract public function getUser($uid, $withGroups = true);

            /**
             * get uid by e-mail
             *
             * @param string $eMail e-mail
             *
             * @return false|integer
             */

            abstract public function getUidByEMail($eMail);

            /**
             * @param $login
             * @return mixed
             */

            abstract public function getUidByLogin($login);

            /**
             * @param $uid
             * @return mixed
             */

            abstract function getLoginByUid($uid);

            /**
             * add user
             *
             * @param string $login
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             *
             * @return integer
             */

            abstract public function addUser($login, $realName = null, $eMail = null, $phone = null);

            /**
             * set password
             *
             * @param integer $uid
             * @param string $password
             *
             * @return mixed
             */

            abstract public function setPassword($uid, $password);

            /**
             * delete user
             *
             * @param $uid
             *
             * @return boolean
             */

            abstract public function deleteUser($uid);

            /**
             * modify user data
             *
             * @param integer $uid
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             * @param string $tg
             * @param string $notification
             * @param boolean $enabled
             * @param string $defaultRoute
             * @param mixed $persistentToken
             * @param integer $primaryGroup
             * @param integer $serviceAccount
             * @param integer $sudo
             *
             * @return boolean
             */

            abstract public function modifyUser($uid, $realName = '', $eMail = '', $phone = '', $tg = '', $notification = 'tgEmail', $enabled = true, $defaultRoute = '', $persistentToken = false, $primaryGroup = -1, $serviceAccount = 0, $sudo = 0);

            /**
             * enable or disable user
             *
             * @param integer $uid
             * @param boolean $enabled
             *
             * @return boolean
             */

            abstract public function enableUser($uid, $enabled = true);

            /**
             * modify user data
             *
             * @param integer $uid
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             * @param string $tg
             * @param string $notification
             * @param string $defaultRoute
             * @param mixed $persistentToken
             *
             * @return boolean
             */

            abstract public function userPersonal($uid, $realName = '', $eMail = '', $phone = '', $tg = '', $notification = 'tgEmail', $defaultRoute = '', $persistentToken = false);

            /**
             * @param string $tg
             * @param string $subject
             * @param string $message
             * @param string $token
             *
             * @return mixed
             */

            private function sendTg($tg, $subject, $message, $token) {
                if ($tg && $token) {
                    try {
                        $tg = @json_decode(file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id=" . urlencode($tg) . "&text=" . urlencode($subject . "\n\n" . $message)), true);
                        return $tg && @$tg["ok"];
                    } catch (\Exception $e) {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            /**
             * @param string $login
             * @param string $email
             * @param string $subject
             * @param string $message
             * @param string $config
             *
             * @return mixed
             */

            private function sendEmail($login, $email, $subject, $message, $config) {
                try {
                    if ($email && $config && $login != $email) {
                        return eMail($config, $email, $subject ?: "-", $message) === true;
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * @param string $uid
             * @param string $subject
             * @param string $message
             *
             * @return mixed
             */

            public function notify($uid, $subject, $message) {
                if (!checkInt($uid)) {
                    return false;
                }

                return $this->db->insert("insert into core_users_notifications_queue (login, uid, subject, message) values (:login, :uid, :subject, :message)", [
                    "login" => $this->login,
                    "uid" => $uid,
                    "subject" => $subject,
                    "message" => $message,
                ]);
            }

            /**
             * none
             */

            private function realNotify() {
                $notifications = $this->db->get("select * from core_users_notifications_queue", false, [
                    "notification_id" => "id",
                    "login" => "login",
                    "uid" => "uid",
                    "subject" => "subject",
                    "message" => "message",
                ]);

                foreach ($notifications as $notification) {
                    $login = $notification["login"];
                    $uid = $notification["uid"];
                    $subject = $notification["subject"];
                    $message = $notification["message"];

                    $user = $this->getUser($uid);

                    if (!$user) {
                        $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                            "notification_id" => $notification["id"],
                        ]);
                        continue;
                    }

                    if (!in_array($user["notification"], [ "tgEmail", "emailTg", "tg", "email" ])) {
                        $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                            "notification_id" => $notification["id"],
                        ]);
                        continue;
                    }

                    if ($user["notification"] == "tg" && (!$user["tg"] || !@$this->config["telegram"]["bot"])) {
                        $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                            "notification_id" => $notification["id"],
                        ]);
                        continue;
                    }

                    if ($user["notification"] == "email" && (!$user["eMail"] || !$this->config["email"])) {
                        $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                            "notification_id" => $notification["id"],
                        ]);
                        continue;
                    }

                    $message = trim($message);
                    $subject = trim($subject);

                    if (!$message) {
                        $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                            "notification_id" => $notification["id"],
                        ]);
                        continue;
                    }

                    $id = false;

                    if ($user["notification"] == "tg") {
                        if ($this->sendTg(@$user["tg"], $subject, $message, @$this->config["telegram"]["bot"])) {
                            $id = $user["tg"];
                        }
                    } else

                    if ($user["notification"] == "tgEmail") {
                        if ($this->sendTg(@$user["tg"], $subject, $message, @$this->config["telegram"]["bot"])) {
                            $id = $user["tg"];
                        } else {
                            if ($this->sendEmail(@$user["login"], @$user["eMail"], $subject, $message, $this->config)) {
                                $id = $user["eMail"];
                            }
                        }
                    } else

                    if ($user["notification"] == "email") {
                        if ($this->sendEmail(@$user["login"], @$user["eMail"], $subject, $message, $this->config)) {
                            $id = $user["eMail"];
                        }
                    } else

                    if ($user["notification"] == "emailTg") {
                        if ($this->sendEmail(@$user["login"], @$user["eMail"], $subject, $message, $this->config)) {
                            $id = $user["eMail"];
                        } else {
                            if ($this->sendTg(@$user["tg"], $subject, $message, @$this->config["telegram"]["bot"])) {
                                $id = $user["tg"];
                            }
                        }
                    }

                    if ($id) {
                        $this->clickhouse->insert("nlog", [ [ "date" => time(), "login" => $login, "to" => $user["login"], "uid" => $uid, "id" => $id, "subject" => $subject, "message" => $message, "target" => $user["notification"] ] ]);
                    } else {
                        $this->clickhouse->insert("nlog", [ [ "date" => time(), "login" => $login, "to" => $user["login"], "uid" => $uid, "id" => "none", "subject" => $subject, "message" => $message, "target" => $user["notification"] ] ]);
                    }

                    $this->db->modify("delete from core_users_notifications_queue where notification_id = :notification_id", [
                        "notification_id" => $notification["id"],
                    ]);
                }
            }

            /**
             * @return mixed
             */

            abstract public function getSettings();

            /**
             * @param string $settings
             */

            abstract public function putSettings($settings);

            /**
             * @param integer $id
             *
             * @return mixed
             */

            abstract public function getAvatar($uid);

            /**
             * @param integer $id
             * @param string $settings
             */

            abstract public function putAvatar($uid, $avatar);

            /**
             * @param string $from
             * @param string $to
             * @param string $subject
             * @param string $body
             * @param string $type
             * @param string $handler
             *
             * @return mixed
             */

            abstract public function sendMessage($from, $to, $subject, $body, $type, $handler);

            /**
             * @param string $login
             *
             * @return mixed
             */

            abstract public function unreaded($uid);

            /**
             * @param string $id
             *
             * @return mixed
             */

            abstract public function readed($id);

            /**
             * @param array $ids
             *
             * @return mixed
             */

            abstract public function getMessages($ids);

            /**
             * @param array $ids
             *
             * @return mixed
             */

            abstract public function deleteMessages($ids);

            /**
             * @param integer $uid
             * @param string $secret
             *
             * @return mixed
             */

            abstract public function twoFa($uid, $secret = "");

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "minutely") {
                    $this->realNotify();
                }

                return true;
            }
        }
    }