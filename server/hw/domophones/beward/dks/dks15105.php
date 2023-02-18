<?php

    namespace hw\domophones {

        require_once 'dks.php';
        require_once 'separated_rfids.php';

        class dks15105 extends dks {

            use separated_rfids;

            protected array $cms_models = [
                'KKM-100S2' => 0,
                'KKM-105' => 1,
                'KKM-108' => 3,
                'KAD2501' => 2,
                'KAD2502' => 4,
            ];

        }
    }
