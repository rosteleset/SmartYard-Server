<?php

namespace hw\ip\domophone\beward;

class dks15105 extends beward
{

    use separatedRfids;

    protected array $cmsModelIdMap = [
        'KKM-100S2' => 0,
        'KKM-105' => 1,
        'KKM-108' => 3,
        'KAD2501' => 2,
        'KAD2502' => 4,
    ];
}
