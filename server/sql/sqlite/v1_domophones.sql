-- panels
CREATE TABLE domophones
(
    domophone_id integer not null primary key autoincrement,
    enabled integer,
    model text not null,
    version text not null,
    cms text,                                                                                                           -- for visualization only
    ip text,
    credentials text,                                                                                                   -- plaintext:login:password, token:token, or something else
    caller_id text,
    comments text,
    locks_disabled integer,
    cms_levels text
);
CREATE INDEX domophones_ip on domophones(ip);

-- domophones apartments -> cms
CREATE TABLE domophones_cmses
(
    domophone_id integer not null,
    apartment integer not null,                                                                                         -- flat number
    cms text not null,
    dozen integer not null,
    unit text not null
);
CREATE UNIQUE INDEX domophones_cmses_uniq on domophones_cmses(domophone_id, cms, dozen, unit);

