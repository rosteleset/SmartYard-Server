<?php

function addUserRole($pid, $login, $role_id): void
{
    global $db;
    $sql = "select uid from core_users where login = '$login'";
    $uid = $db->get($sql)[0]['uid'];

    $sql = "select project_role_id from tt_projects_roles where project_id = $pid and role_id = $role_id and uid = $uid";
    $r = $db->get($sql);
    if (!$r) {
        $sql = "insert into tt_projects_roles(project_id, role_id, uid) values($pid, $role_id, $uid) on conflict do nothing";
        $db->exec($sql);
    }
}

function addGroupRole($pid, $name, $role_id): void
{
    global $db;
    $sql = "select gid from core_groups where name = '$name'";
    $gid = $db->get($sql)[0]['gid'];

    $sql = "select project_role_id from tt_projects_roles where project_id = $pid and role_id = $role_id and gid = $gid";
    $r = $db->get($sql);
    if (!$r) {
        $sql = "insert into tt_projects_roles(project_id, role_id, gid) values($pid, $role_id, $gid) on conflict do nothing";
        $db->exec($sql);
    }
}

function addFilter($pid, $name): void
{
    global $db;
    $sql = "select project_filter_id from tt_projects_filters where project_id = $pid and filter = '$name'";
    $r = $db->get($sql);
    if (!$r) {
        $sql = "insert into tt_projects_filters(project_id, filter) values ($pid, '$name') on conflict do nothing";
        $db->exec($sql);
    }
}

/**
 * @throws Exception
 */
function installTTMobileTemplate(): void
{
    global $config, $db;
    $driver = explode(":", $config["db"]["dsn"])[0];
    if ($driver !== "pgsql")
        throw new Exception("This template can only be installed on a PostgreSQL database.");

    $sql = @file_get_contents("sql/tt_mobile_template/tt_mobile_template.sql");
    if ($sql === false) {
        throw new Exception("Error reading *.sql file: " . error_get_last()['message']);
    }
    $db->exec($sql);

    $workflow_body = @file_get_contents("sql/tt_mobile_template/tt_mobile_workflow.lua");
    if ($workflow_body === false)
        throw new Exception("Error reading *.lua file: " . error_get_last()['message']);

    $filter_body = @file_get_contents("sql/tt_mobile_template/tt_filter_all.lua");
    if ($filter_body === false)
        throw new Exception("Error reading *.lua file: " . error_get_last()['message']);

    $tt = loadBackend("tt");
    $tt->putWorkflow("Mobile", $workflow_body);
    $tt->putFilter("all", $filter_body);

    $users = loadBackend("users");
    $login = "app_user";

    $uid = $users->getUidByLogin($login);
    if ($uid === false)
    {
        $uid = $users->addUser($login);
    }
    $user = $users->getUser($uid);
    $token = $user['persistentToken'];
    if (!$token) {
        $token = md5(GUIDv4());
        $users->modifyUser($uid, $persistentToken = $token);
    }

    // Project Mobile roles
    $sql = "select project_id from tt_projects where project = 'Mobile'";
    $r = $db->get($sql);
    $pid = $r[0]['project_id'];

    $sql = "select role_id from tt_roles where name = 'manager.senior'";
    $r = $db->get($sql);
    $user_role = $r[0]['role_id'];
    addGroupRole($pid, 'call-center', $user_role);
    addGroupRole($pid, 'office', $user_role);
    addGroupRole($pid, 'cctv-managers', $user_role);
    addUserRole($pid, $login, $user_role);

    // Project Mobile filters
    addFilter($pid, 'all');

    echo "TT mobile template installed successfully.\n";
}
