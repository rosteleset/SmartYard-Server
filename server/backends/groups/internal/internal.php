<?php

    /**
     * backends groups namespace
     */

    namespace backends\groups {

        /**
         * internal.db groups class
         */

        class internal extends groups {

            /**
             * get list of all groups
             *
             * @return array|false
             */

            public function getGroups($uid = false) {
                if ($uid === false) {
                    $groups = $this->db->query("select gid, name, acronym, (select count (*) from core_users_groups as g1 where g1.gid = core_groups.gid) users, admin, login as \"adminLogin\" from core_groups left join core_users on core_groups.admin = core_users.uid order by gid", \PDO::FETCH_ASSOC)->fetchAll();
                } else {
                    if (!check_int($uid)) {
                        return false;
                    }
                    $groups = $this->db->query("select gid, name, acronym, (select count (*) from core_users_groups as g1 where g1.gid = core_groups.gid) users, admin, login as \"adminLogin\" from core_groups left join core_users on core_groups.admin = core_users.uid where gid in (select gid from core_users_groups where uid = $uid) or gid in (select primary_group from core_users where uid = $uid) or admin = $uid order by gid", \PDO::FETCH_ASSOC)->fetchAll();
                }

                return $groups;
            }

            /**
             * get group by gid
             *
             * @param integer $gid gid
             *
             * @return array|false
             */

            public function getGroup($gid) {
                if (!check_int($gid)) {
                    return false;
                }

                $groups = $this->db->query("select gid, name, acronym, admin, login as \"adminLogin\" from core_groups left join core_users on core_groups.admin = core_users.uid where gid = $gid", \PDO::FETCH_ASSOC)->fetchAll();

                if (count($groups)) {
                    return $groups[0];
                } else {
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
                if (!check_int($gid)) {
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
                if (!check_int($gid)) {
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
                if (!check_int($gid)) {
                    return false;
                }

                $uids = $this->db->query("select uid from core_users_groups where gid = $gid", \PDO::FETCH_ASSOC)->fetchAll();

                $users = [];
                foreach ($uids as $uid) {
                    $users[] = $uid["uid"];
                }

                return $users;
            }

            /**
             * modify users in group
             *
             * @return boolean
             */

            public function setUsers($gid, $uids) {
                // TODO: add transaction, commint, rollback

                if (!check_int($gid)) {
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
                    if (!check_int($uid)) {
                        return false;
                    }

                    if (!$sth->execute([
                        ":uid" => $uid,
                        ":gid" => $gid,
                    ])) {
                        return false;
                    }
                }

                return true;
            }

            public function deleteUser($uid) {
                if (!check_int($gid)) {
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

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }

            /**
             * @inheritDoc
             */
            public function getGroupByAcronym($acronym)
            {
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
            public function addUserToGroup($uid, $gid)
            {
                if (!check_int($uid) || !check_int($gid)) {
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
