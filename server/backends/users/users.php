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
             *
             * @return mixed
             */

            abstract public function getUidByLogin($login);

            /**
             * @param $uid
             *
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

            protected function sendTg($tg, $subject, $message, $token) {
                if ($tg && $token) {
                    try {
                        $tg = @json_decode(file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?parse_mode=HTML&chat_id=" . urlencode($tg) . "&text=" . urlencode($subject . "\n\n" . $message)), true);
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

            protected function sendEmail($login, $email, $subject, $message, $config) {
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

            abstract public function notify($uid, $subject, $message);

            /**
             * none
             */

            abstract protected function realNotify();

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
             * @return mixed
             */

            abstract public function sudoOn($uid, $password);

            /**
             * @return mixed
             */

            abstract public function sudoOn2fa($uid, $token, $code);
            /**
             * @return mixed
             */

            public function sudoOff() {
                $this->redis->del("SUDO:" . $this->login);

                return true;
            }

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