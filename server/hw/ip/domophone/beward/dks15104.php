<?php

namespace hw\ip\domophone\beward;

class dks15104 extends beward
{

    use separatedRfids;

    protected array $cmsModelIdMap = [
        'KKM-100S2' => 0,
        'KKM-105' => 1,
        'KAD2501' => 2,
        'KKM-108' => 3,
    ];
}
