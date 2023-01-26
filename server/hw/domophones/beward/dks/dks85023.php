<?php

namespace hw\domophones {

    require_once 'dks.php';
    require_once 'separated_rfids.php';

    class dks85023 extends dks {

        use separated_rfids;

        protected $cms_models = [
            'KKM-100S2' => 0,
            'KKM-105' => 1,
            'KAD2501' => 2,
        ];

    }

}