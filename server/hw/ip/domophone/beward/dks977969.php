<?php

namespace hw\ip\domophone\beward;

class dks977969 extends beward
{

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
