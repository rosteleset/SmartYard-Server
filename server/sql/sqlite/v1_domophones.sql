-- panels
CREATE TABLE domophones
(
    domophone_id integer not null primary key autoincrement,
    enabled integer,
    model text not null,
    ip text,
    port integer,
    credentials text,                                                                                                   -- plaintext:login:password, token:token, or something else
    caller_id text,
    comment text
);
CREATE INDEX domophones_ip on domophones(ip);
