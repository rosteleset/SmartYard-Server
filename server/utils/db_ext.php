<?php

    /**
     * Class PDO_EXT
     *
     * Extends the built-in PDO class to provide additional functionality or custom behavior
     * for database interactions.
     *
     * @package utils
     * @extends PDO
     */

    class PDO_EXT extends PDO {

        private $dsn;

        /**
         * Constructs a new instance of the class and establishes a database connection.
         *
         * @param string      $_dsn      The Data Source Name, or DSN, containing the information required to connect to the database.
         * @param string|null $username  The user name for the DSN string. Optional.
         * @param string|null $password  The password for the DSN string. Optional.
         * @param array|null  $options   An array of driver-specific connection options. Optional.
         */

        public function __construct($_dsn, $username = null, $password = null, $options = null) {
            $this->dsn = $_dsn;

            parent::__construct($_dsn, $username, $password, $options);

            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            switch ($this->parseDsn()["protocol"]) {
                case "sqlite":
                    $this->sqliteCreateFunction('mb_strtoupper', 'mb_strtoupper', 1);
                    $this->sqliteCreateFunction('mb_levenshtein', 'mb_levenshtein', 2);
                    break;
            }
        }

        /**
         * Parses the Data Source Name (DSN) string and returns its components.
         *
         * This function extracts and returns the relevant parts of a DSN, such as
         * the database type, host, port, database name, username, and password.
         *
         * @return array An associative array containing the parsed DSN components.
         */

        function parseDsn() {
            $dsn = trim($this->dsn);

            if (strpos($dsn, ':') === false) {
                die("the dsn is invalid, it does not have scheme separator \":\"\n");
            }

            list($prefix, $dsnWithoutPrefix) = preg_split('#\s*:\s*#', $dsn, 2);

            $protocol = $prefix;

            if (preg_match('/^[a-z\d]+$/', strtolower($prefix)) == false) {
                die("the dsn is invalid, prefix contains illegal symbols\n");
            }

            $dsnElements = preg_split('#\s*\;\s*#', $dsnWithoutPrefix);

            $elements = [];
            foreach ($dsnElements as $element) {
                if (strpos($dsnWithoutPrefix, '=') !== false) {
                    list($key, $value) = preg_split('#\s*=\s*#', $element, 2);
                    $elements[$key] = $value;
                } else {
                    $elements = [
                        $dsnWithoutPrefix,
                    ];
                }
            }

            return [
                "protocol" => $protocol,
                "params" => $elements,
            ];
        }

        /**
         * Trims whitespace from all string values in the provided associative array.
         *
         * @param array $map The associative array whose string values will be trimmed.
         * @return array The array with all string values trimmed of whitespace.
         */

        function trimParams($map) {
            $remap = [];

            if ($map) {
                if (array_is_list($map)) {
                    foreach ($map as $value) {
                        if (is_null($value)) {
                            $remap[] = $value;
                        } else {
                            $remap[] = trim($value);
                        }
                    }
                } else {
                    foreach ($map as $key => $value) {
                        if (is_null($value)) {
                            $remap[$key] = $value;
                        } else {
                            $remap[$key] = trim($value);
                        }
                    }
                }
            }

            return $remap;
        }

        /**
         * Inserts a new record into the database using the provided SQL query and parameters.
         *
         * @param string $query   The SQL INSERT query to execute.
         * @param array  $params  Optional. An associative array of parameters to bind to the query.
         * @param array  $options Optional. Additional options for the insert operation.
         *
         * @return mixed The result of the insert operation, typically the inserted record's ID or a status indicator.
         */

        function insert($query, $params = [], $options = []) {
            global $cli, $cli_error;

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
                    if ($cli && $cli_error) {
                        error_log(print_r($e, true));
                        error_log($query);
                    }
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                if ($cli && $cli_error) {
                    error_log(print_r($e, true));
                    error_log($query);
                }
                return false;
            }
        }

        /**
         * Executes a database modification query (such as INSERT, UPDATE, or DELETE).
         *
         * @param string $query   The SQL query to execute.
         * @param array  $params  Optional. An array of parameters to bind to the query.
         * @param array  $options Optional. Additional options for query execution.
         *
         * @return mixed The result of the query execution, typically the number of affected rows or a status indicator.
         */

        function modify($query, $params = [], $options = []) {
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
                    error_log($query);
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                error_log($query);
                return false;
            }
        }

        /**
         * Executes a database modification query with extended options.
         *
         * @param string $query   The SQL query string to execute.
         * @param array  $map     An associative array mapping parameter names to values.
         * @param array  $params  Additional parameters for the query execution.
         * @param array  $options Optional settings to modify the execution behavior.
         *
         * @return mixed The result of the query execution, format depends on implementation.
         */

        function modifyEx($query, $map, $params, $options = []) {
            $mod = 0;
            try {
                foreach ($map as $db => $param) {
                    if (array_key_exists($param, $params)) {
                        $sth = $this->prepare(sprintf($query, $db, $db));
                        if ($sth->execute($this->trimParams([
                            $db => $params[$param],
                        ]))) {
                            $mod += $sth->rowCount();
                        }
                    }
                }
                return $mod;
            } catch (\PDOException $e) {
                if (!in_array("silent", $options)) {
                    setLastError($e->errorInfo[2] ?: $e->getMessage());
                    error_log(print_r($e, true));
                    error_log($query);
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                error_log($query);
                return false;
            }
        }

        /**
         * Executes the given SQL query and returns the result.
         *
         * @param string $query The SQL query to execute.
         * @return mixed The result of the query execution, or false on failure.
         */

        function queryEx($query) {
            $sth = $this->prepare($query);
            if ($sth->execute()) {
                return $sth->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                return [];
            }
        }

        /**
         * Executes a database query and retrieves results.
         *
         * @param string $query   The SQL query to execute.
         * @param array  $params  Optional. Parameters to bind to the query. Default is an empty array.
         * @param array  $map     Optional. Mapping rules for the result set. Default is an empty array.
         * @param array  $options Optional. Additional options for query execution. Default is an empty array.
         *
         * @return mixed The result of the query, format depends on implementation.
         */

        function get($query, $params = [], $map = [], $options = []) {
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
                    error_log($query);
                }
                return false;
            } catch (\Exception $e) {
                setLastError($e->getMessage());
                error_log(print_r($e, true));
                error_log($query);
                return false;
            }
        }
    }
