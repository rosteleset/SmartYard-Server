<?php

    /**
     * backends users namespace
     */

    namespace backends\users {

        use backends\backend;

        /**
         * base users class
         */

        abstract class users extends backend {

            /**
             * get list of all users
             *
             * @return array
             */

            abstract public function getUsers();

            /**
             * get user by uid
             *
             * @param integer $uid uid
             *
             * @return array
             */

            abstract public function getUser($uid);

            /**
             * get uid by e-mail
             *
             * @param string $eMail e-mail
             *
             * @return false|integer
             */

            abstract public function getUidByEMail($eMail);

            /**
             * @param $login
             * @return mixed
             */
            abstract public function getUidByLogin($login);

            /**
             * add user
             *
             * @param string $login
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             *
             * @return integer
             */

            abstract public function addUser($login, $realName = '', $eMail = '', $phone = '');

            /**
             * set password
             *
             * @param integer $uid
             * @param string $password
             *
             * @return mixed
             */

            abstract public function setPassword($uid, $password);

            /**
             * delete user
             *
             * @param $uid
             *
             * @return boolean
             */

            abstract public function deleteUser($uid);

            /**
             * modify user data
             *
             * @param integer $uid
             * @param string $realName
             * @param string $eMail
             * @param string $phone
             * @param boolean $enabled
             *
             * @return boolean
             */

            abstract public function modifyUser($uid, $realName = '', $eMail = '', $phone = '', $enabled = true, $defaultRoute = '#');
        }
    }