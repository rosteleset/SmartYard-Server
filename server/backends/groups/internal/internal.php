<?php

    /**
     * backends groups namespace
     */

    namespace backends\groups {

        /**
         * internal.db groups class
         */

        class internal extends groups {

            private $groupsByUid, $allGroups;

            /**
             * get list of all groups
             *
             * @return array|false
             */

            public function getGroups($uid = false) {
                $key = $uid ? "GROUPSBY:$uid" : "GROUPS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if ($uid === false) {
                    if ($this->allGroups) {
                        return $this->allGroups;
                    }
                    $_groups = $this->db->queryEx("select gid, name, acronym, (select count(*) from (select uid from (select uid from core_users_groups g1 where g1.gid=g2.gid union select admin from core_groups g3 where g3.gid=g2.gid and admin is not null union select uid from core_users u1 where u1.primary_group=g2.gid) as t2 group by uid) as t3) as users, admin, login as \"adminLogin\" from core_groups as g2 left join core_users on g2.admin = core_users.uid order by name, acronym, gid");
                    $this->allGroups = $_groups;
                } else {
                    if (!checkInt($uid)) {
                        return false;
                    }
                    if (@$this->groupsByUid[$uid]) {
                        return $this->groupsByUid[$uid];
                    }
                    $_groups = $this->db->queryEx("select gid, name, acronym, (select count(*) from (select uid from (select uid from core_users_groups g1 where g1.gid=g2.gid union select admin from core_groups g3 where g3.gid=g2.gid and admin is not null union select uid from core_users u1 where u1.primary_group=g2.gid) as t2 group by uid) as t3) as users, admin, login as \"adminLogin\" from core_groups as g2 left join core_users on g2.admin = core_users.uid where gid in (select gid from core_users_groups where uid = $uid) or gid in (select primary_group from core_users where uid = $uid) or admin = $uid order by name, acronym, gid");
                    $this->groupsByUid[$uid] = $_groups;
                }

                $this->cacheSet($key, $_groups);
                return $_groups;
            }

            /**
             * get group by gid
             *
             * @param integer $gid gid
             *
             * @return array|false
             */

            public function getGroup($gid) {
                $key = "GROUP:$gid";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if (!checkInt($gid)) {
                    return false;
                }

                $groups = $this->db->query("select gid, name, acronym, admin, login as \"adminLogin\" from core_groups left join core_users on core_groups.admin = core_users.uid where gid = $gid", \PDO::FETCH_ASSOC)->fetchAll();

                if (count($groups)) {
                    $this->cacheSet($key, $groups[0]);
                    return $groups[0];
                } else {
                    $this->unCache($key);
                    return false;
                }
            }

            /**
             * @param integer $gid gid
             * @param string $acronym
             * @param string $name group name
             * @param integer $admin uid
             *
             * @return boolean
             */

            public function modifyGroup($gid, $acronym, $name, $admin) {
                $this->clearCache();

                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update core_groups set acronym = :acronym, name = :name, admin = :admin where gid = $gid");
                    return $sth->execute([
                        ":acronym" => trim($acronym),
                        ":name" => trim($name),
                        ":admin" => (int)$admin,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * create group
             *
             * @param string $acronym
             * @param string $name
             *
             * @return integer|false
             */

            public function addGroup($acronym, $name) {
                $this->clearCache();

                $acronym = trim($acronym);

                try {
                    $sth = $this->db->prepare("insert into core_groups (acronym, name) values (:acronym, :name)");
                    if ($sth->execute([
                        ":acronym" => $acronym,
                        ":name" => trim($name),
                    ])) {
                        return $this->db->lastInsertId();
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * delete group
             *
             * @param integer $gid
             *
             * @return boolean
             */

            public function deleteGroup($gid) {
                $this->clearCache();

                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from core_groups where gid = $gid");
                    $this->db->exec("delete from core_users_groups where gid = $gid");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
                return true;
            }

            /**
             * list of all users in group
             *
             * @return false|array
             */

            public function getUsers($gid) {
                $key = "USERS:$gid";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if (!checkInt($gid)) {
                    return false;
                }

                $uids = $this->db->query("select uid from (select uid from core_users_groups where gid = $gid union select admin from core_groups where gid = $gid union select uid from core_users where primary_group = $gid) as t1 group by uid", \PDO::FETCH_ASSOC)->fetchAll();

                $_users = [];
                foreach ($uids as $uid) {
                    $_users[] = $uid["uid"];
                }

                $this->cacheSet($key, $_users);
                return $_users;
            }

            /**
             * modify users in group
             *
             * @return boolean
             */

            public function setUsers($gid, $uids) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into core_users_groups (uid, gid) values (:uid, :gid)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from core_users_groups where gid = $gid");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                foreach ($uids as $uid) {
                    if (!checkInt($uid)) {
                        return false;
                    }

                    if (!$sth->execute([
                        ":uid" => $uid,
                        ":gid" => $gid,
                    ])) {
                        return false;
                    }
                }

                clearCache(true);

                return true;
            }

            /**
             * modify user groups
             *
             * @return boolean
             */

            public function setGroups($uid, $gids) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($uid)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into core_users_groups (uid, gid) values (:uid, :gid)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from core_users_groups where uid = $uid");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                foreach ($gids as $gid) {
                    if (!checkInt($gid)) {
                        return false;
                    }

                    if (!$sth->execute([
                        ":uid" => $uid,
                        ":gid" => $gid,
                    ])) {
                        return false;
                    }
                }

                clearCache($uid);

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteUser($uid) {
                $this->clearCache();

                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from core_users_groups where uid = $uid");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

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
                    $del = $this->db->prepare($c[$i]);
                    $del->execute();
                    $n += $del->rowCount();
                }

                return $n;
            }

            /**
             * @inheritDoc
             */

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }

            /**
             * @inheritDoc
             */

            public function getGroupByAcronym($acronym) {
                return $this->db->get("select gid from core_groups where acronym = :acronym", [
                    "acronym" => $acronym,
                ], [
                    "gid" => "gid",
                ], [
                    "fieldlify"
                ]);
            }

            /**
             * @inheritDoc
             */

            public function addUserToGroup($uid, $gid) {
                $this->clearCache();

                if (!checkInt($uid) || !checkInt($gid)) {
                    return false;
                }

                return $this->db->insert("insert into core_users_groups (uid, gid) values ($uid, $gid)");
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "5min") {
                    $this->cleanup();
                }

                return true;
            }
        }
    }
