<?php

    /**
     * backends groups namespace
     */

    namespace backends\groups {

        use PHPMailer\PHPMailer\Exception;

        /**
         * internal groups class
         */

        class internal extends groups {

            /**
             * get list of all groups
             *
             * @return array|false
             */

            public function getGroups($uid = false) {
                if ($uid === false) {
                    $groups = $this->db->query("select gid, name, acronym, (select count (*) from users_groups as g1 where g1.gid = groups.gid) users from groups order by gid", \PDO::FETCH_ASSOC)->fetchAll();
                } else {
                    if (!checkInt($uid)) {
                        return false;
                    }
                    $groups = $this->db->query("select gid, name, acronym, (select count (*) from users_groups as g1 where g1.gid = groups.gid) users from groups where gid in (select gid from users_groups where uid = $uid) order by gid", \PDO::FETCH_ASSOC)->fetchAll();
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
                if (!checkInt($gid)) {
                    return false;
                }

                $groups = $this->db->query("select gid, name, acronym from groups where gid = $gid", \PDO::FETCH_ASSOC)->fetchAll();

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
             *
             * @return boolean
             */

            public function modifyGroup($gid, $acronym, $name) {
                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update groups set acronym = :acronym, name = :name where gid = $gid");
                    return $sth->execute([
                        ":acronym" => trim($acronym),
                        ":name" => trim($name),
                    ]);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
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
                    $sth = $this->db->prepare("insert into groups (acronym, name) values (:acronym, :name)");
                    if (!$sth->execute([
                        ":acronym" => $acronym,
                        ":name" => trim($name),
                    ])) {
                        return false;
                    }

                    $sth = $this->db->prepare("select gid from groups where acronym = :acronym");
                    if ($sth->execute([ ":acronym" => $acronym, ])) {
                        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        if (count($res) == 1) {
                            return $res[0]["gid"];
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } catch (Exception $e) {
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
                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from groups where gid = $gid");
                    $this->db->exec("delete from users_groups where gid = $gid");
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
                return true;
            }

            /**
             * list of all users in group
             *
             * @return array
             */

            public function getUsers($gid) {
                if (!checkInt($gid)) {
                    return false;
                }

                $uids = $this->db->query("select uid from users_groups where gid = $gid", \PDO::FETCH_ASSOC)->fetchAll();

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
                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from users_groups where gid = $gid");
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into users_groups (uid, gid) values (:uid, :gid)");
                } catch (Exception $e) {
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

                return true;
            }

            public function deleteUser($uid) {
                if (!checkInt($gid)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from users_groups where uid = $uid");
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
