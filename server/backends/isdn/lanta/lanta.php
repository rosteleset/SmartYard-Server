<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * LanTa's variant of flash calls and sms sending
         */

        require_once __DIR__ . "/../../../traits/backends/isdn/push.php";
        require_once __DIR__ . "/../../../traits/backends/isdn/sms.php";
        require_once __DIR__ . "/../../../traits/backends/isdn/incoming.php";

        class lanta extends isdn
        {
            use push, sms, incoming;
        }
    }
