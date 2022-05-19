<?php

    /**
     * backends authentication namespace
     */

    namespace backends\authentication {
        /**
         * authorize by local database
         */

        class internal extends authentication {

            /**
             * @param string $login login
             * @param string $password plain-text password
             * @return false|integer false if user not found or uid
             */

            public function check_auth($login, $password) {
                $sth = $this->db->prepare("select uid, password from users where login = :login and enabled = 1");
                $sth->execute([ ":login" => $login ]);
                $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                if (count($res) == 1 && password_verify($password, $res[0]["password"])) {
                    return $res[0]["uid"];
                } else {
                    return false;
                }
            }
        }
    }
