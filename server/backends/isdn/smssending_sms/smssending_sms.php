<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * internal.db subscribers class
         */
        class smssending_sms extends isdn
        {

            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                function smc_send_sms($phone, $msg) {
                    $phone[0] = '7';
                    $ret = trim(@file_get_contents("https://xml.smstec.ru/requests/sendsms.php?login=$login&password=$password&originator=$originator&phone=$phone&text=".urlencode($msg)));
                    if (stripos($ret, 'error') !== false) {
                        echo "$ret\n";
                        return 'smc_err_' . $ret;
                    } else {
                        return 'smc_' . $ret;
                    }
                }
            }

            /**
             * @inheritDoc
             */
            function checkCode($id, $code)
            {
                // TODO: Implement checkCode() method.
            }
        }
    }
