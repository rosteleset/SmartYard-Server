<?php

    /**
     * backends authentication namespace
     */

    namespace backends\authentication {

        /**
         * authenticate by local database with autoadd users
         */

        class external extends authentication {

            private $already = false;

            /**
             * @param string $login login
             * @param string $password plain-text password
             * @return false|integer false if user not found or uid
             */

            public function check_auth($login, $password) {
                $sth = $this->db->prepare("select uid, password from core_users where login = :login and enabled = 1");
                $sth->execute([ ":login" => $login ]);
                $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                if (count($res) == 1 && password_verify($password, $res[0]["password"])) {
                    return $res[0]["uid"];
                } else {
                    if ($this->already) {
                        return false;
                    }
                    $users = loadBackend("users");
                    if ($users->getUidByLogin($login) !== false) {
                        return false;
                    }
                    $this->already = true;
                    if (@$this->config["backends"]["authentication"]["checkauth"]) {
                        $url = $this->config["backends"]["authentication"]["checkauth"];
                        $url = str_replace("%%login", urlencode($login), $url);
                        $url = str_replace("%%password", urlencode($password), $url);
                        try {
                            $ok = trim(@file_get_contents($url));
                            if (@$this->config["backends"]["authentication"]["extended"]) {
                                try {
                                    $ext = json_decode($ok, true);
                                } catch (\Exception $e) {
                                    $ext = false;
                                }
                            }
                            if (strtolower($ok) === "ok" || (@$this->config["backends"]["authentication"]["extended"] && is_array($ext))) {
                                $uid = $users->addUser($login, $login, $login);
                                if ($uid) {
                                    $users->setPassword($uid, $password);
                                    if (@$this->config["backends"]["authentication"]["default_group"]) {
                                        $groups = loadBackend("groups");
                                        if ($groups) {
                                            $gid = $groups->getGroupByAcronym($this->config["backends"]["authentication"]["default_group"]);
                                            if ($gid) {
                                                $groups->addUserToGroup($uid, $gid);
                                            }
                                        }
                                    }
                                    if ($ext) {
                                        $users->modifyUser($uid, $ext["real_name"], $ext["email"], $ext["phone"], $ext["tg_id"]);
                                    }
                                    return $this->check_auth($login, $password);
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
                    return false;
                }
            }
        }
    }
