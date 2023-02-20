<?php

    namespace hw\domophones {

        require_once 'dks.php';

        class dks977969 extends dks {

            public function configure_user_account(string $password) {
                parent::configure_user_account($password);

                $this->api_call('cgi-bin/pwdgrp_cgi', [
                    'action' => 'update',
                    'username' => 'user1',
                    'password' => $password,
                ]);
            }
        }
    }
