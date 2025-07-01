<?php

    /**
     * backends users namespace
     */

    namespace backends\users {

        /**
         * internal.db users class
         */

        class internal extends users {

            private $logins, $users, $pc = false;

            /**
             * @inheritDoc
             */

            function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);
            }

            /**
             * @inheritDoc
             */

            public function getUsers($withSessions = false, $withLast = false) {
                if (!$withSessions) {

                    $cache = $this->cacheGet("USERS");
                    if ($cache) {
                        if ($withLast) {
                            foreach ($cache as $i => $user) {
                                $user["lastLogin"] = $this->redis->get("last_login_" . md5($user["login"]));
                                $user["lastAction"] = $this->redis->get("last_action_" . md5($user["login"]));

                            }
                        }
                        return $cache;
                    }
                }

                try {
                    $users = $this->db->query("select uid, login, real_name, e_mail, phone, tg, enabled, primary_group, acronym primary_group_acronym, notification, secret from core_users left join core_groups on core_users.primary_group = core_groups.gid order by real_name, login, uid", \PDO::FETCH_ASSOC)->fetchAll();
                    $_users = [];

                    foreach ($users as $user) {
                        $_users[] = [
                            "uid" => $user["uid"],
                            "login" => $user["login"],
                            "realName" => $user["real_name"],
                            "eMail" => $user["e_mail"],
                            "phone" => $user["phone"],
                            "tg" => $user["tg"],
                            "notification" => $user["notification"],
                            "enabled" => $user["enabled"],
                            "lastLogin" => $withLast ? $this->redis->get("last_login_" . md5($user["login"])) : null,
                            "lastAction" => $withLast ? $this->redis->get("last_action_" . md5($user["login"])) : null,
                            "primaryGroup" => $user["primary_group"],
                            "primaryGroupAcronym" => $user["primary_group_acronym"],
                            "twoFA" => $user["secret"] ? 1 : 0,
                        ];
                    }

                    if ($withSessions) {
                        $a = loadBackend("authorization");

                        if ($a->allow([
                            "_login" => $this->login,
                            "_uid" => $this->uid,
                            "_path" => [
                                "api" => "accounts",
                                "method" => "user",
                            ],
                            "_request_method" => "POST",
                        ])) {
                            foreach ($_users as &$u) {
                                $u["sessions"] = [];
                                $lk = $this->redis->keys("auth_*_{$u["uid"]}");
                                foreach ($lk as $k) {
                                    $u["sessions"][] = json_decode($this->redis->get($k), true);
                                }
                                $pk = $this->redis->keys("persistent_*_{$u["uid"]}");
                                foreach ($pk as $k) {
                                    $s = json_decode($this->redis->get($k), true);
                                    $s["byPersistentToken"] = true;
                                    $u["sessions"][] = $s;
                                }
                            }
                        }
                    }

                    if (!$withSessions) {
                        $this->cacheSet("USERS", $_users);
                    }

                    return $_users;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    if (!$withSessions) {
                        $this->unCache("USERS");
                    }
                    return false;
                }
            }

            /**
             * get user by uid
             *
             * @param integer $uid uid
             *
             * @return array|false
             */

            public function getUser($uid, $withGroups = true) {
                if (!checkInt($uid)) {
                    return false;
                }

                $key = "USER:$uid:" . (int)$withGroups;

                if (@$this->users[$key]) {
                    return $this->users[$key];
                }

                $cache = $this->cacheGet($key);
                if ($cache) {
                    if ($uid >= 0) {
                        $this->users[$key] = $cache;
                    } else {
                        $this->users = $cache;
                    }
                    return $cache;
                }

                if ($uid >= 0) {
                    // ordinary user
                    try {
                        $user = $this->db->queryEx("select uid, login, real_name, e_mail, phone, tg, notification, enabled, default_route, primary_group, acronym primary_group_acronym, secret from core_users left join core_groups on core_users.primary_group = core_groups.gid where uid = $uid");

                        if (count($user)) {
                            $_user = [
                                "uid" => $user[0]["uid"],
                                "login" => $user[0]["login"],
                                "realName" => $user[0]["real_name"],
                                "eMail" => $user[0]["e_mail"],
                                "phone" => $user[0]["phone"],
                                "tg" => $user[0]["tg"],
                                "notification" => $user[0]["notification"],
                                "enabled" => $user[0]["enabled"],
                                "defaultRoute" => $user[0]["default_route"],
                                "primaryGroup" => $user[0]["primary_group"],
                                "primaryGroupAcronym" => $user[0]["primary_group_acronym"],
                                "twoFA" => $user[0]["secret"] ? 1 : 0,
                            ];

                            if ($withGroups) {
                                $groups = loadBackend("groups");

                                if ($groups !== false) {
                                    $_user["groups"] = $groups->getGroups($uid);
                                }
                            }

                            $persistent = false;
                            $_keys = $this->redis->keys("persistent_*_" . $user[0]["uid"]);
                            foreach ($_keys as $_key) {
                                $persistent = explode("_", $_key)[1];
                                break;
                            }

                            if ($persistent) {
                                $_user["persistentToken"] = $persistent;
                            }

                            $this->cacheSet($key, $_user, true);
                            $this->users[$key] = $_user;

                            return $_user;
                        } else {
                            $this->unCache($key);
                            return false;
                        }
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        $this->unCache($key);
                        return false;
                    }
                } else {
                    if ($this->pc) {
                        return;
                    }

                    $this->pc = true;

                    // force fill memory cache
                    try {
                        $users = $this->db->queryEx("select uid, login, real_name, e_mail, phone, tg, notification, enabled, default_route, primary_group, acronym primary_group_acronym, secret from core_users left join core_groups on core_users.primary_group = core_groups.gid");

                        if ($withGroups) {
                            $groups = loadBackend("groups");
                        }

                        for ($i = 0; $i < count($users); $i++) {
                            $_user = [
                                "uid" => $users[$i]["uid"],
                                "login" => $users[$i]["login"],
                                "realName" => $users[$i]["real_name"],
                                "eMail" => $users[$i]["e_mail"],
                                "phone" => $users[$i]["phone"],
                                "tg" => $users[$i]["tg"],
                                "notification" => $users[$i]["notification"],
                                "enabled" => $users[$i]["enabled"],
                                "defaultRoute" => $users[$i]["default_route"],
                                "primaryGroup" => $users[$i]["primary_group"],
                                "primaryGroupAcronym" => $users[$i]["primary_group_acronym"],
                                "twoFA" => $users[$i]["secret"] ? 1 : 0,
                            ];

                            if (@$groups) {
                                $_user["groups"] = $groups->getGroups($_user["uid"]);
                            }

                            $persistent = false;
                            $_keys = $this->redis->keys("persistent_*_" . $users[$i]["uid"]);
                            foreach ($_keys as $_key) {
                                $persistent = explode("_", $_key)[1];
                                break;
                            }

                            if ($persistent) {
                                $_user["persistentToken"] = $persistent;
                            }

                            $this->users["USER:" . (int)$users[$i]["uid"] . ":" . (int)$withGroups] = $_user;
                        }

                        $this->cacheSet($key, $this->users);

                        return true;
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        return false;
                    }
                }
            }

            /**
             * add user
             *
             * @param string $login
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             *
             * @return integer|false
             */

            public function addUser($login, $realName = null, $eMail = null, $phone = null) {
                $this->clearCache();

                $login = trim($login);
                $password = generatePassword();

                try {
                    $sth = $this->db->prepare("insert into core_users (login, password, real_name, e_mail, phone, enabled) values (:login, :password, :real_name, :e_mail, :phone, 1)");
                    if ($sth->execute([
                        ":login" => $login,
                        ":password" => password_hash($password, PASSWORD_DEFAULT),
                        ":real_name" => $realName?trim($realName):null,
                        ":e_mail" => $eMail?trim($eMail):null,
                        ":phone" => $phone?trim($phone):null,
                    ])) {
                        $uid = $this->db->lastInsertId();
                        if ($eMail) {
                            eMail($this->config, trim($eMail), "new user", "your new password is $password");
                        }
                        return $uid;
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * set password
             *
             * @param integer $uid
             * @param string $password
             *
             * @return boolean
             */

            public function setPassword($uid, $password) {
                $this->clearCache();

                if (!checkInt($uid) || !trim($password)) {
                    return false;
                }

                if ($uid === 0) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update core_users set password = :password where uid = $uid");
                    $sth->execute([
                        ":password" => password_hash($password, PASSWORD_DEFAULT),
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * delete user
             *
             * @param $uid
             *
             * @return boolean
             */

            public function deleteUser($uid) {
                $this->clearCache();

                if (!checkInt($uid)) {
                    return false;
                }

                if ($uid > 0) { // admin cannot be deleted
                    $user = $this->getUser($uid);

                    $this->redis->del("last_login_" . md5($user["login"]));
                    $this->redis->del("last_action_" . md5($user["login"]));

                    try {
                        $this->db->exec("delete from core_users where uid = $uid");

                        $_keys = $this->redis->keys("persistent_*_" . $uid);
                        foreach ($_keys as $_key) {
                            $this->redis->del($_key);
                        }

                        $groups = loadBackend("groups");
                        if ($groups) {
                            $groups->deleteUser($uid);
                        }
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        return false;
                    }
                    return true;
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function modifyUser($uid, $realName = '', $eMail = '', $phone = '', $tg = '', $notification = 'tgEmail', $enabled = true, $defaultRoute = '', $persistentToken = false, $primaryGroup = -1) {
                $this->clearCache();

                if (!checkInt($uid)) {
                    return false;
                }

                if (!in_array($notification, [ "none", "tgEmail", "emailTg", "tg", "email" ])) {
                    return false;
                }

                $user = $this->getUser($uid);

                try {
                    $a = loadBackend("authorization");

                    if ($a->mAllow("accounts", "groupUsers", "PUT")) {
                        $sth = $this->db->prepare("update core_users set real_name = :real_name, e_mail = :e_mail, phone = :phone, tg = :tg, notification = :notification, enabled = :enabled, default_route = :default_route, primary_group = :primary_group where uid = $uid");
                    } else {
                        $sth = $this->db->prepare("update core_users set real_name = :real_name, e_mail = :e_mail, phone = :phone, tg = :tg, notification = :notification, enabled = :enabled, default_route = :default_route where uid = $uid");
                    }

                    if ($persistentToken && strlen(trim($persistentToken)) === 32 && $uid && $enabled) {
                        $this->redis->set("persistent_" . trim($persistentToken) . "_" . $uid, json_encode([
                            "uid" => $uid,
                            "login" => $this->db->get("select login from core_users where uid = $uid", false, false, [ "fieldlify" ]),
                            "started" => time(),
                        ]));
                    } else {
                        $_keys = $this->redis->keys("persistent_*_" . $uid);
                        foreach ($_keys as $_key) {
                            $this->redis->del($_key);
                        }
                    }

                    if (!$enabled) {
                        $_keys = $this->redis->keys("auth_*_" . $uid);
                        foreach ($_keys as $_key) {
                            $this->redis->del($_key);
                        }
                    }

                    if ($a->mAllow("accounts", "groupUsers", "PUT")) {
                        return $sth->execute([
                            ":real_name" => trim($realName),
                            ":e_mail" => trim($eMail)?trim($eMail):null,
                            ":phone" => trim($phone),
                            ":tg" => trim($tg),
                            ":notification" => trim($notification),
                            ":enabled" => $enabled ? "1" : "0",
                            ":default_route" => trim($defaultRoute),
                            ":primary_group" => (int)$primaryGroup,
                        ]);
                    } else {
                        return $sth->execute([
                            ":real_name" => trim($realName),
                            ":e_mail" => trim($eMail)?trim($eMail):null,
                            ":phone" => trim($phone),
                            ":tg" => trim($tg),
                            ":notification" => trim($notification),
                            ":enabled" => $enabled ? "1" : "0",
                            ":default_route" => trim($defaultRoute),
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * get uid by e-mail
             *
             * @param string $eMail e-mail
             *
             * @return false|integer
             */

            public function getUidByEMail($eMail) {
                try {
                    $sth = $this->db->prepare("select uid from core_users where e_mail = :e_mail");
                    if ($sth->execute([ ":e_mail" => $eMail ])) {
                        $users = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        if (count($users)) {
                            return (int)$users[0]["uid"];
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            function getLoginByUid($uid) {
                if (@$this->users[$uid]) {
                    return $this->users[$uid]["login"];
                }

                if (@$this->logins[$uid]) {
                    return $this->logins[$uid] = $login;
                }

                $login = $this->db->get("select login from core_users where uid = :uid",
                    [
                        "uid" => $uid
                    ],
                    false,
                    [
                        "fieldlify"
                    ]
                );

                $this->logins[$uid] = $login;
            }

            /**
             * returns class capabilities
             *
             * @return mixed
             */

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }

            public function cleanup() {
                $n = 0;

                $c = [
                    "delete from core_users_rights where aid not in (select aid from core_api_methods)",
                    "delete from core_groups_rights where aid not in (select aid from core_api_methods)",
                    "delete from core_users_rights where uid not in (select uid from core_users)",
                    "delete from core_groups_rights where gid not in (select gid from core_groups)",
                    "delete from core_users_groups where uid not in (select uid from core_users)",
                    "delete from core_users_groups where gid not in (select gid from core_groups)",
                ];

                for ($i = 0; $i < count($c); $i++) {
                    $n += $this->db->modify($c[$i]);
                }

                return $n;
            }

            /**
             * @inheritDoc
             */

            public function getUidByLogin($login) {
                $key = "UIDBYLOGIN:$login";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                try {
                    $users = $this->db->get("select uid from core_users where login = :login", [
                        "login" => $login,
                    ], [
                        "uid" => "uid",
                    ]);
                    if (count($users)) {
                        $this->cacheSet($key, (int)$users[0]["uid"]);
                        return (int)$users[0]["uid"];
                    } else {
                        $this->unCache($key);
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->unCache($key);
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function putSettings($settings) {
                try {
                    $settings = $this->db->modify("update core_users set settings = :settings where login = :login", [
                        "login" => $this->login,
                        "settings" => json_encode($settings),
                    ]);
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
              * @inheritDoc
              */

            public function getSettings() {
                try {
                    $settings = $this->db->get("select settings from core_users where login = :login", [
                        "login" => $this->login,
                    ], [
                        "settings" => "settings",
                    ], [
                        "fieldlify"
                    ]);
                    if ($settings) {
                        try {
                            return json_decode($settings, true);
                        } catch (\Exception $e) {
                            return [];
                        }
                    } else {
                        return [];
                    }
                } catch (\Exception $e) {
                    return [];
                }
            }

            /**
             * @inheritDoc
             */

            public function putAvatar($uid, $avatar) {
                if (!checkInt($uid)) {
                    return false;
                }

                if ($this->uid != 0) {
                    $uid = $this->uid;
                }

                try {
                    $settings = $this->db->modify("update core_users set avatar = :avatar where uid = :uid", [
                        "uid" => $uid,
                        "avatar" => $avatar,
                    ]);
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
              * @inheritDoc
              */

            public function getAvatar($uid) {
                if ($this->uid != 0) {
                    $uid = $this->uid;
                }

                if (!checkInt($uid)) {
                    return false;
                }

                return $this->db->get("select avatar from core_users where uid = :uid", [
                    "uid" => $uid,
                ], [
                    "avatar" => "avatar",
                ], [
                    "fieldlify"
                ]);
            }

            /**
             * @inheritDoc
             */

            public function sendMessage($from, $to, $subject, $body, $type, $handler) {

            }

            /**
             * @inheritDoc
             */

            public function unreaded($uid) {

            }

            /**
             * @inheritDoc
             */

            public function readed($id) {

            }

            /**
             * @inheritDoc
             */

            public function getMessages($ids) {

            }

            /**
             * @inheritDoc
             */

            public function deleteMessages($ids) {

            }

            /**
             * @inheritDoc
             */

            public function two_fa($uid, $secret = "") {
                global $cli;

                if ($secret === "") {
                    return $this->db->get("select secret from core_users where uid = :uid", [
                        "uid" => $uid,
                    ], [
                        "secret" => "secret",
                    ], [
                        "fieldlify",
                    ]);
                }

                if (!$secret) {
                    if ($cli) {
                        return $this->db->modify("update core_users set secret = null where uid = :uid", [
                            "uid" => $uid,
                        ]);
                    } else {
                        return false;
                    }
                }

                $result = $this->db->modify("update core_users set secret = :secret where uid = :uid", [
                    "secret" => $secret,
                    "uid" => $uid,
                ]);

                if ($result) {
                    $_keys = $this->redis->keys("persistent_*_" . $uid);
                    foreach ($_keys as $_key) {
                        $this->redis->del($_key);
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["2fa"]) {
                    $usage["2fa"] = [];
                }

                $usage["2fa"]["disable-2fa"] = [
                    "description" => "Disable 2fa for specifyed user",
                    "value" => "string",
                    "placeholder" => "login",
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists($args["--disable-2fa"])) {
                    $uid = $this->getUidByLogin($args["--disable-2fa"]);

                    if ($uid === false) {
                        die("user not found\n");
                    }

                    if ($this->two_fa($uid, false)) {
                        echo "2fa disabled for user: #$uid ({$args["--disable-2fa"]})\n";
                    } else {
                        echo "failed to disable 2fa for user: #$uid ({$args["--disable-2fa"]})\n";
                    }

                    exit(0);
                }

                parent::cli($args);
            }
        }
    }
