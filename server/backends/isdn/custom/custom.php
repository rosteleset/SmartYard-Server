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
       
        class custom extends isdn
        {
            use push;
            
            /**
             * @inheritDoc
             */
            function flashCall($id)
            {
                // write your own implementation if you will use it!
                return false;
            }

            /**
             * @inheritDoc
             */
            function getCode($id)
            {
                // write your own implementation if you will use it!
                return false;
            }
            
            /**
             * @inheritDoc
             */
            function confirmNumbers()
            {
                // write your own implementation if you will use it!
                return [
                ];
            }

            /**
             * @inheritDoc
             */
            function checkIncoming($id)
            {
                // write your own implementation if you will use it!
                return false;
            }
            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                // Example implementation of backend for sms-code sending via external sms-provider
                $phone = $id;
                $pin = sprintf("%04d", rand(0, 9999));
                $msg = "Ваш код подтверждения: $pin";
                $login = "your_login";
                $password = "your_password";
                return "$pin:" . trim(@file_get_contents("https://your.sms.provider/sendsms.php?login=$login&password=$password&phone=$phone&text=".urlencode($msg)));
            }
        }
    }
