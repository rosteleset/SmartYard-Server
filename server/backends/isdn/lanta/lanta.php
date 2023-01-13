<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * LanTa's variant of flash calls and sms sending
         */

        require_once __DIR__ . "/../teledome/push.php";
        require_once __DIR__ . "/../teledome/flashCall.php";
        require_once __DIR__ . "/../teledome/sms.php";
        require_once __DIR__ . "/../teledome/incoming.php";

        class lanta extends isdn
        {
            use push, flashCall, sms, incoming;
        }
    }
