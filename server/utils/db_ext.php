<?php

    class PDO_EXT extends PDO {

        public function __construct($dsn, $username = null, $password = null, $options = null)
        {
            parent::__construct($dsn, $username, $password, $options);

            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        function trimParams($map)
        {
            $remap = [];

            if ($map) {
                foreach ($map as $key => $value) {
                    if (is_null($value)) {
                        $remap[$key] = $value;
                    } else {
                        $remap[$key] = trim($value);
                    }
                }
            }

            return $remap;
        }

        function insert($query, $params = [], $options = [])
        {
            try {
                $sth = $this->prepare($query);
                if ($sth->execute($this->trimParams($params))) {
                    try {
                        return $this->lastInsertId();
                    } catch (\Exception $e) {
                        return -1;
                    }
                } else {
                    return false;
                }
            } catch (\PDOException $e) {
                if (!in_array("silent", $options)) {
                    setLastError($e->errorInfo[2] ?: $e->getMessage());
                    error_log(print_r($e, true));
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                return false;
            }
        }

        function modify($query, $params = [], $options = [])
        {
            try {
                $sth = $this->prepare($query);
                if ($sth->execute($this->trimParams($params))) {
                    return $sth->rowCount();
                } else {
                    return false;
                }
            } catch (\PDOException $e) {
                if (!in_array("silent", $options)) {
                    setLastError($e->errorInfo[2] ?: $e->getMessage());
                    error_log(print_r($e, true));
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                return false;
            }
        }

        function modifyEx($query, $map, $params, $options = [])
        {
            $mod = false;
            try {
                foreach ($map as $db => $param) {
                    if (array_key_exists($param, $params)) {
                        $sth = $this->prepare(sprintf($query, $db, $db));
                        if ($sth->execute($this->trimParams([
                            $db => $params[$param],
                        ]))) {
                            if ($sth->rowCount()) {
                                $mod = true;
                            }
                        }
                    }
                }
                return $mod;
            } catch (\PDOException $e) {
                if (!in_array("silent", $options)) {
                    setLastError($e->errorInfo[2] ?: $e->getMessage());
                    error_log(print_r($e, true));
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                return false;
            }
        }

        function queryEx($query)
        {
            $sth = $this->prepare($query);
            if ($sth->execute()) {
                return $sth->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                return [];
            }
        }

        function get($query, $params = [], $map = [], $options = [])
        {
            try {
                if ($params) {
                    $sth = $this->prepare($query);
                    if ($sth->execute($params)) {
                        $a = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    } else {
                        return false;
                    }
                } else {
                    $a = $this->queryEx($query);
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

                if (in_array("singlify", $options)) {
                    if (count($r) === 1) {
                        return $r[0];
                    } else {
                        return false;
                    }
                }

                if (in_array("fieldlify", $options)) {
                    if (count($r) === 1) {
                        return $r[0][array_key_first($r[0])];
                    } else {
                        return false;
                    }
                }

                return $r;

            } catch (\PDOException $e) {
                if (!in_array("silent", $options)) {
                    setLastError($e->errorInfo[2] ?: $e->getMessage());
                    error_log(print_r($e, true));
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                return false;
            }
        }
    }
