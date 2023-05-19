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
             * get list of all users
             *
             * @return array
             */

            abstract public function getUsers();

            /**
             * get user by uid
             *
             * @param integer $uid uid
             *
             * @return array
             */

            abstract public function getUser($uid);

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
             *
             * @return boolean
             */

            abstract public function modifyUser($uid, $realName = '', $eMail = '', $phone = '', $tg = '', $notification = 'tgEmail', $enabled = true, $defaultRoute = '', $persistentToken = false);

            /**
             * @param string $login
             * @param string $subject
             * @param string $message
             */
            public function notify($login, $subject, $message) {
                $uid = $this->getUidByLogin($login);

                if (!$uid) {
                    return false;
                }

                $user = $this->getUser($uid);

                if (!$user) {
                    return false;
                }

                if (!in_array($user["notification"], [ "tgEmail", "emailTg", "tg", "email" ])) {
                    return false;
                }

                if ($user["notification"] == "tg" && (!$user["tg"] || !@$this->config["telegram"]["bot"])) {
                    return false;
                }

                if ($user["notification"] == "email" && (!$user["eMail"] || !$this->config["email"])) {
                    return false;
                }

                $message = trim($message);
                $subject = trim($subject);

                if (!$message) {
                    return false;
                }

                function sendTg($tg, $subject, $message, $token) {
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

                function sendEmail($email, $subject, $message, $config) {
                    try {
                        if ($email && $config) {
                            return eMail($config, $email, $subject ? : "-", $message) === true;
                        } else {
                            return false;
                        }
                    } catch (\Exception $e) {
                        return false;
                    }
                }

                if ($user["notification"] == "tg") {
                    return sendTg($user["tg"], $subject, $message, @$this->config["telegram"]["bot"]);
                }

                if ($user["notification"] == "tgEmail") {
                    return sendTg(@$user["tg"], $subject, $message, @$this->config["telegram"]["bot"]) || sendEmail(@$user["eMail"], $subject, $message, $this->config);
                }

                if ($user["notification"] == "email") {
                    return sendEmail($user["eMail"], $subject, $message, $this->config);
                }

                if ($user["notification"] == "emailTg") {
                    return sendEmail(@$user["eMail"], $subject, $message, $this->config) || sendTg(@$user["tg"], $subject, $message, @$this->config["telegram"]["bot"]);
                }
            } 
        }
    }