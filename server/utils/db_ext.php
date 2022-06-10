<?php

    class PDO_EXT extends PDO {

        function insert($query, $params = []) {
            error_log(print_r($params, true));
            try {
                $sth = $this->prepare($query);
                if ($sth->execute($params)) {
                    return $this->lastInsertId();
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        function modify($query, $params = []) {
            try {
                $sth = $this->prepare($query);
                if ($sth->execute($params)) {
                    return $sth->rowCount();
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        function get($query, $params = [], $map = [], $singlify = false) {
            try {
                if ($params) {
                    $sth = $this->prepare("select uid from core_users where e_mail = :e_mail");
                    if ($sth->execute($params)) {
                        $a = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    } else {
                        return false;
                    }
                } else {
                    $a = $this->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                }

                $r = [];

                if ($map) {
                    foreach ($a as $f) {
                        $x = [];
                        foreach ($map as $k => $l) {
                            $x[$l] = $f[$k];
                        }
                        $r[] = $x;
                    }
                } else {
                    $r = $a;
                }

                if ($singlify) {
                    if (count($r) === 1) {
                        return $r[0];
                    } else {
                        return false;
                    }
                } else {
                    return $r;
                }

            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }
    }
