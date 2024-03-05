-- vars
CREATE TABLE core_vars
(
    var_id serial primary key,
    var_name character varying not null,
    var_value character varying
);
CREATE INDEX core_vars_id on core_vars(var_id);
CREATE INDEX core_vars_var_name on core_vars(var_name);
INSERT INTO core_vars (var_name, var_value) values ('dbVersion', '0');

-- users
CREATE TABLE core_users
(
    uid serial primary key,
    login character varying not null,
    password character varying not null,
    enabled integer,
    real_name character varying,
    e_mail character varying,
    phone character varying,
    tg character varying,
    notification character varying default 'tgEmail',
    default_route character varying,
    last_login integer,
    primary_group integer
);
CREATE UNIQUE INDEX core_users_login on core_users(login);
CREATE INDEX core_users_real_name on core_users(real_name);
CREATE UNIQUE INDEX core_users_e_mail on core_users(e_mail);
CREATE INDEX core_users_phone on core_users(phone);

-- admin - admin && user - user
INSERT INTO core_users (uid, login, password, real_name, enabled) values (0, 'admin', '$2y$10$rU6/RIgJi5ojfuvibG5yHO/Gv5WnclTK6Rc8u.b9mdONHkVMnhJpy', 'admin', 1);
INSERT INTO core_users (login, password, real_name, enabled) values ('user', '$2y$10$hA0uXz.PaoKrycZP4AQwAe4WrW7PeEyXegMftWLAaClbQTDHb.MnC', 'user', 1);

-- groups
CREATE TABLE core_groups
(
    gid serial primary key,
    acronym character varying not null,
    name character varying not null,
    admin integer
);
CREATE UNIQUE INDEX core_groups_acronym on core_groups(acronym);
CREATE UNIQUE INDEX core_groups_name on core_groups(name);

-- users group
INSERT INTO core_groups (acronym, name) values ('users', 'users');

-- users <-> groups
CREATE TABLE core_users_groups
(
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX core_users_groups_uniq on core_users_groups(uid, gid);
CREATE INDEX core_users_groups_uid on core_users_groups(uid);
CREATE INDEX core_users_groups_gid on core_users_groups(gid);

-- user to users group
INSERT INTO core_users_groups (uid, gid) values (1, 1);

-- list of all api methods
CREATE TABLE core_api_methods
(
    aid character varying not null primary key,
    api character varying not null,
    method character varying not null,
    request_method character varying not null,
    permissions_same character varying
);
CREATE UNIQUE INDEX core_api_methods_uniq on core_api_methods(api, method, request_method);

-- users rights (access to api methods)
CREATE TABLE core_users_rights
(
    uid integer not null,
    aid character varying not null,
    allow integer
);
CREATE UNIQUE INDEX core_users_rights_uniq on core_users_rights(uid, aid);

-- groups rights (access to api methods)
CREATE TABLE core_groups_rights
(
    gid integer not null,
    aid character varying not null,
    allow integer
);
CREATE UNIQUE INDEX core_groups_rights_uniq on core_groups_rights(gid, aid);

-- methods always availabe for all
CREATE TABLE core_api_methods_common
(
    aid character varying not null primary key
);

-- methods available if _uid === _id
CREATE TABLE core_api_methods_personal
(
    aid character varying not null primary key
);

-- methods authorized by backend (tasktracker, for example)
CREATE TABLE core_api_methods_by_backend
(
    aid character varying not null primary key,
    backend character varying
);

-- running processes
CREATE TABLE core_running_processes
(
    running_process_id serial primary key,
    pid integer,
    ppid integer,
    start integer,
    process character varying,
    params character varying,
    done integer,
    result character varying,
    expire integer
);
