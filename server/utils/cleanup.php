<?php

    function cleanup() {
        global $db;

        $n = 0;

        $c = [
            "delete from users_rights where aid not in (select aid from api_methods)",
            "delete from groups_rights where aid not in (select aid from api_methods)",
            "delete from users_rights where uid not in (select uid from users)",
            "delete from groups_rights where gid not in (select gid from groups)",
            "delete from users_groups where uid not in (select uid from users)",
            "delete from users_groups where gid not in (select uid from groups)",
        ];

        for ($i = 0; $i < count($c); $i++) {
            $del = $db->prepare($c[$i]);
            $del->execute();
            $n += $del->rowCount();
        }

        echo "$n rows cleaned\n";
    }