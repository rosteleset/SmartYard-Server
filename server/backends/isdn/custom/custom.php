<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * Example for sending sms via-custom sms-provider
         */

        require_once __DIR__ . "/../teledome/push.php";
        require_once __DIR__ . "/../teledome/flashCall.php";
        require_once __DIR__ . "/../teledome/sms.php";
        require_once __DIR__ . "/../teledome/incoming.php";

        class lanta extends isdn
        {
            use push, flashCall, incoming;

            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                $phone = $id;
                $pin = sprintf("%04d", rand(0, 9999));
                $msg = "Ваш код подтверждения: $pin";
                $login = "your_login";
                $password = "your_password";
                echo "$pin:" . trim(@file_get_contents("https://your.sms.provider/sendsms.php?login=$login&password=$password&phone=$phone&text=".urlencode($msg)));
            }
        }
    }
