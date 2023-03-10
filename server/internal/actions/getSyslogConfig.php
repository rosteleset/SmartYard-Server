<?php

        /** Return syslog config
        * TODO: - need modify "syslog_servers" config section,
        *         added used clickhouse server similar to other backends
        */

        $payload = ['clickhouse'=>[
            'host'=> $config['backends']['plog']['host'],
            'port'=>$config['backends']['plog']['port'],
            'database'=>$config['backends']['plog']['database'],
            'username'=>$config['backends']['plog']['username'],
            'password'=>$config['backends']['plog']['password'],
            ],
            'hw' =>  $config['syslog_servers']];

        response(200, $payload);

