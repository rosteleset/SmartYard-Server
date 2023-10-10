<?php

namespace hw\ip\domophone\beward;

class dks15134 extends beward
{

    use separatedRfids;

    protected array $cmsModelIdMap = [
        'KKM-100S2' => 0,
        'KKM-105' => 1,
        'KAD2501' => 2,
        'KKM-108' => 3,
    ];

    public function configureUserAccount(string $password)
    {
        parent::configureUserAccount($password);

        $this->apiCall('cgi-bin/pwdgrp_cgi', [
            'action' => 'update',
            'username' => 'user1',
            'password' => $password,
        ]);
    }
}
