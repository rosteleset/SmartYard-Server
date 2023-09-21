<?php

    /**
     * backends authorization namespace
     */

    namespace backends\authorization {

        /**
         * authorize by local database
         */

        class internal extends authorization {

            /**
             * allow all
             *
             * @param object $params all params passed to api handlers
             * @return boolean allow or not
             */

            public function allow($params) {
                if ($params["_path"]["api"] === "authentication" && $params["_path"]["method"] === "login") {
                    return true;
                }

                if (!checkInt($params["_uid"])) {
                    return false;
                }

                $rights_api = $params["_path"]["api"];
                $rights_method = $params["_path"]["method"];
                $rights_request_method = $params["_request_method"];

                try {
                    $sth = $this->db->prepare("
                        select
                            api,
                            method,
                            request_method
                        from
                            core_api_methods as m1
                        where aid in (
                            select
                                permissions_same
                            from
                                core_api_methods as m2
                            where
                                api = :api and
                                method = :method and
                                request_method = :request_method
                        )
                    ");

                    if ($sth->execute([
                        ":api" => $params["_path"]["api"],
                        ":method" => $params["_path"]["method"],
                        ":request_method" => $params["_request_method"],
                    ])) {
                        $s = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        if ($s && $s[0]) {
                            $rights_api = $s[0]["api"];
                            $rights_method = $s[0]["method"];
                            $rights_request_method = $s[0]["request_method"];
                        }
                    }

                    $sth = $this->db->prepare("
                        select
                            backend
                        from
                            core_api_methods_by_backend
                        where aid in (
                            select
                                aid
                            from
                                core_api_methods
                            where
                                api = :api and
                                method = :method and
                                request_method = :request_method
                        )
                    ");

                    if ($sth->execute([
                        ":api" => $rights_api,
                        ":method" => $rights_method,
                        ":request_method" => $rights_request_method,
                    ])) {
                        $b = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        if ($b && $b[0] && $b[0]["backend"]) {
                            $_params = $params;
                            $_params["_path"]["api"] = $rights_api;
                            $_params["_path"]["method"] = $rights_method;
                            $_params["_request_method"] = $rights_request_method;

                            return loadBackend($b[0]["backend"])->allow($_params);
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                if ($params["_uid"] === 0) {
                    return true;
                }

                try {
                    $sth = $this->db->prepare("
                        select count(*) as allow from core_api_methods where aid in (
                            select aid from (
                                select aid from core_api_methods where aid in (
                                    select aid from core_groups_rights where allow = 1 and (
                                        gid in (select gid from core_users_groups where uid = :uid)
                                        or
                                        gid in (select primary_group from core_users where uid = :uid)
                                        or
                                        gid in (select gid from core_groups where admin = :uid)
                                    )
                                ) or aid in (select aid from core_api_methods_common)
                            ) as t1 where
                                aid not in (select aid from core_groups_rights where allow = 0 and gid in (select gid from core_users_groups where uid = :uid)) and
                                aid not in (select aid from core_users_rights where allow = 0 and uid = :uid)
                            union
                                select aid from core_api_methods where aid in (select aid from core_users_rights where allow = 1 and uid = :uid)
                        ) and api = :api and method = :method and request_method = :request_method"
                    );

                    if ($sth->execute([
                        ":uid" => $params["_uid"],
                        ":api" => $rights_api,
                        ":method" => $rights_method,
                        ":request_method" => $rights_request_method,
                    ])) {
                        $m = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        if ($m && $m[0] && $m[0]["allow"]) {
                            return true;
                        }
                        if (@$params["_id"] == $params["_uid"]) {
                            $sth = $this->db->prepare("
                                select 
                                    count(*) as allow
                                from
                                    core_api_methods_personal
                                where
                                    aid in (
                                        select
                                            aid
                                        from
                                            core_api_methods    
                                        where
                                            api = :api and
                                            method = :method and
                                            request_method = :request_method
                                    )
                            ");
                            if ($sth->execute([
                                ":api" => $rights_api,
                                ":method" => $rights_method,
                                ":request_method" => $rights_request_method,
                            ])) {
                                $m = $sth->fetchAll(\PDO::FETCH_ASSOC);
                                if ($m && $m[0] && $m[0]["allow"]) {
                                    return true;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return array
             */

            public function allowedMethods($uid) {
                $key = "ALLOWED:$uid";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if (!checkInt($uid)) {
                    return false;
                }

                if ($uid === 0) {
                    return $this->methods();
                } else {
                    $_m = [];
                    try {
                        $sth = $this->db->prepare("
                            select * from core_api_methods where aid in (
                                select aid from (
                                    select aid from core_api_methods where aid in (
                                        select aid from core_groups_rights where allow = 1 and (
                                            gid in (select gid from core_users_groups where uid = :uid)
                                            or
                                            gid in (select primary_group from core_users where uid = :uid)
                                            or
                                            gid in (select gid from core_groups where admin = :uid)
                                        )
                                    ) or 
                                    aid in (select aid from core_api_methods_common) or
                                    aid in (select aid from core_api_methods_personal) or   
                                    -- will be passed to backend
                                    aid in (select aid from core_api_methods_by_backend)
                                ) as t1 where
                                    aid not in (select aid from core_groups_rights where allow = 0 and gid in (select gid from core_users_groups where uid = :uid)) and
                                    aid not in (select aid from core_users_rights where allow = 0 and uid = :uid)
                                union
                                    select aid from core_api_methods where aid in (select aid from core_users_rights where allow = 1 and uid = :uid)
                            ) and coalesce(permissions_same, '') = ''"
                        );

                        if ($sth->execute([
                            ":uid" => $uid,
                        ])) {
                            $all = $sth->fetchAll(\PDO::FETCH_ASSOC);
                            $r = [];
                            foreach ($all as $a) {
                                $_m[$a['api']][$a['method']][$a['request_method']] = $a['aid'];
                                $r[$a['aid']] = true;
                            }

                            $same = $this->db->query("
                                select
                                    aid,
                                    api,
                                    method,
                                    request_method,
                                    permissions_same
                                from
                                    core_api_methods
                                where
                                    permissions_same is not null
                            ", \PDO::FETCH_ASSOC)->fetchAll();

                            foreach ($same as $a) {
                                if (@$r[$a["permissions_same"]]) {
                                    $_m[$a['api']][$a['method']][$a['request_method']] = $a['aid'];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        $this->unCache($key);
                        return false;
                    }

                    $this->cacheSet($key, $_m);
                    return $_m;
                }
            }

            /**
             * @return array
             */

            public function getRights() {
                $users = $this->db->query("select uid, aid, allow from core_users_rights order by uid, aid", \PDO::FETCH_ASSOC)->fetchAll();
                $groups = $this->db->query("select gid, aid, allow from core_groups_rights order by gid, aid", \PDO::FETCH_ASSOC)->fetchAll();

                return [
                    "users" => $users,
                    "groups" => $groups,
                ];
            }

            /**
             * add, modify or delete user or group access to api method
             *
             * @param boolean $user user or group
             * @param integer $id uid or gid
             * @param string $api
             * @param string $method
             * @param string[] $allow
             * @param string[] $deny
             *
             * @return boolean
             */


            public function setRights($user, $id, $api, $method, $allow, $deny) {
                $this->clearCache();

                if (!checkInt($id)) {
                    return false;
                }

                if (!is_array($allow)) {
                    $allow = [ $allow ];
                }

                if (!is_array($deny)) {
                    $deny = [ $deny ];
                }

                $tn = $user?"core_users_rights":"core_groups_rights";
                $ci = $user?"uid":"gid";

                try {
                    $sth = $this->db->prepare("delete from $tn where aid in (select aid from core_api_methods where api = :api and method = :method)");
                    $sth->execute([
                        ":api" => $api,
                        ":method" => $method,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $sthI = $this->db->prepare("insert into $tn ($ci, aid, allow) values (:id, :aid, :allow)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                foreach ($allow as $aid) {
                    try {
                        $sthI->execute([
                            ":id" => $id,
                            ":aid" => $aid,
                            ":allow" => 1,
                        ]);
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        return false;
                    }
                }

                foreach ($deny as $aid) {
                    try {
                        $sthI->execute([
                            ":id" => $id,
                            ":aid" => $aid,
                            ":allow" => 0,
                        ]);
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        return false;
                    }
                }

                return true;
            }

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
        }
    }
