<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * internal.db subscribers class
         */
        class smssending extends isdn
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

            /**
             * @inheritDoc
             */
            function flashCall($id)
            {
                // TODO: Implement sendCode() method.
                /*

                SendMessage
                SendMessage_Response

                https://xml.smstec.ru/api/v3/easysms/connect_id/Request_Name/

{
    "Header":{
        "login":"example_login",
        "password":"example_password"
    },
    "Payload":{
        "message":{
            "client_message_id":"cmsg_734965624",
            "messenger_type":"Flash Call",
            "recipient":"79001001010",
        }
    }
}

{
    "Header":{
        "webhook_type":"flashcall"
    },
    "Payload":{
        "message_id":"uuid",
        "client_message_id":"msg_8375663",
        "time":"",
        "unix_time":"",
        "state":"102"
        "code":"102102"
    }
}

/*
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($msg));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//        curl_setopt($curl, CURLOPT_USERPWD, "<-- -->:<-- -->");
        curl_setopt($curl, CURLOPT_URL, "https://a2p-api.megalabs.ru/sms/v1/sms");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_VERBOSE, false);

        $time_start = time();
        $result_raw = curl_exec($curl);
        $result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result = json_decode($result_raw, true);
        curl_close($curl);

*/
            }

            /**
             * @inheritDoc
             */
            function checkCode($id, $code)
            {
                // TODO: Implement checkCode() method.
            }

            /**
             * @inheritDoc
             */
            function sendSMS($id, $text)
            {
                // TODO: Implement sendSMS() method.
            }

            /**
             * @inheritDoc
             */
            function getConfirmNumbers()
            {
                // TODO: Implement getConfirmNumbers() method.
            }

            /**
             * @inheritDoc
             */
            function registerToConfirm($mobile)
            {
                // TODO: Implement registerToConfirm() method.
            }

            /**
             * @inheritDoc
             */
            function isConfirmed($mobile)
            {
                // TODO: Implement isConfirmed() method.
            }
        }
    }
