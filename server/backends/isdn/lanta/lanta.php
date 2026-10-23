<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn {

        /**
         * Flash calls and SMS integration
         */

        require_once __DIR__ . "/../.traits/push.php";
        require_once __DIR__ . "/../.traits/sms.php";
        require_once __DIR__ . "/../.traits/incoming.php";

        class lanta extends isdn {
            use push, sms, incoming;
        }
    }
